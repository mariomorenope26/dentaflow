<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\PatientImage;
use App\Models\Payment;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\DentalAiService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Throwable;

class DentalAiController extends Controller
{
    public function patientSummary(Request $request, Patient $patient): JsonResponse
    {
        Gate::authorize('view', $patient);

        /** @var User $user */
        $user = $request->user();
        $clinic = $user->clinic ?? Clinic::current();

        $summary = DentalAiService::summarizePatient($patient, $clinic);

        return response()->json($summary);
    }

    public function parseSoap(Request $request, Patient $patient): JsonResponse
    {
        Gate::authorize('editClinical', $patient);

        $validated = $request->validate([
            'transcript' => ['required', 'string', 'min:8', 'max:4000'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $clinic = $user->clinic ?? Clinic::current();

        $parsed = DentalAiService::parseDictationToSoap((string) $validated['transcript'], $clinic);

        AuditLogger::log('create', $patient, "Usó Copiloto SOAP IA en historia de {$patient->full_name}", [], $request);

        return response()->json($parsed);
    }

    public function analyzeRadiograph(Request $request, Patient $patient, PatientImage $image): JsonResponse
    {
        if ($image->patient_id !== $patient->id) {
            abort(404);
        }

        Gate::authorize('editClinical', $patient);

        /** @var User $user */
        $user = $request->user();
        $clinic = $user->clinic ?? Clinic::current();

        $analysis = DentalAiService::analyzeRadiograph($image, $patient, $clinic);

        AuditLogger::log('update', $image, "Ejecutó asistente diagnóstico IA sobre '{$image->title}'", [], $request);

        return response()->json($analysis);
    }

    public function askReports(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->canManageCash() && ! $user->canEditClinicalRecords() && ! $user->canViewAuditLogs()) {
            abort(403, 'No tienes permisos para consultar reportes IA.');
        }

        $validated = $request->validate([
            'question' => ['required', 'string', 'min:3', 'max:500'],
            'start_date' => ['nullable', 'string'],
            'end_date' => ['nullable', 'string'],
        ]);

        try {
            $start = CarbonImmutable::parse((string) ($validated['start_date'] ?? CarbonImmutable::today()->startOfMonth()->toDateString()))->startOfDay();
        } catch (Throwable) {
            $start = CarbonImmutable::today()->startOfMonth()->startOfDay();
        }

        try {
            $end = CarbonImmutable::parse((string) ($validated['end_date'] ?? CarbonImmutable::today()->toDateString()))->endOfDay();
        } catch (Throwable) {
            $end = CarbonImmutable::today()->endOfDay();
        }

        $clinic = $user->clinic ?? Clinic::current();
        $currency = $clinic !== null ? $clinic->currency_symbol : 'S/';

        $payments = Payment::query()
            ->where('status', 'registrado')
            ->whereBetween('paid_at', [$start, $end])
            ->get();

        $incomeByMethod = [
            ['method' => 'Efectivo', 'total' => (float) $payments->where('payment_method', 'efectivo')->sum('amount')],
            ['method' => 'Tarjeta', 'total' => (float) $payments->where('payment_method', 'tarjeta')->sum('amount')],
            ['method' => 'Yape / Plin', 'total' => (float) $payments->where('payment_method', 'yape_plin')->sum('amount')],
            ['method' => 'Transferencia', 'total' => (float) $payments->where('payment_method', 'transferencia')->sum('amount')],
        ];

        $appts = Appointment::query()
            ->where('is_waitlist', false)
            ->whereBetween('starts_at', [$start, $end])
            ->get();

        $apptTotal = $appts->count();
        $noShow = $appts->where('status', AppointmentStatus::NoAsistio)->count();

        $topProcedures = TreatmentPlanItem::query()
            ->selectRaw('description, COUNT(*) as total_count, SUM(unit_price * quantity - discount) as total_amount')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('description')
            ->orderByDesc('total_count')
            ->limit(5)
            ->get()
            ->toArray();

        $pendingBalances = Patient::query()
            ->with(['treatmentPlans.items', 'payments'])
            ->where('is_active', true)
            ->get()
            ->map(function (Patient $p): array {
                $budget = $p->treatmentPlans
                    ->where('status', '!=', 'cancelado')
                    ->sum(fn (TreatmentPlan $plan): float => max(0.0, (float) $plan->items->sum(fn ($it): float => ((float) $it->unit_price * $it->quantity) - (float) $it->discount) - (float) $plan->discount_amount));
                $paid = (float) $p->payments->where('status', 'registrado')->sum('amount');

                return [
                    'patient_id' => $p->id,
                    'full_name' => $p->full_name,
                    'balance' => round(max(0.0, $budget - $paid), 2),
                ];
            })
            ->filter(fn (array $row): bool => $row['balance'] > 0.01)
            ->values()
            ->all();

        $dentists = User::query()
            ->whereIn('role', [UserRole::AdminConsultorio, UserRole::Odontologo])
            ->get(['id', 'name', 'commission_rate']);

        $completedItems = TreatmentPlanItem::query()
            ->where('status', 'realizado')
            ->whereBetween('completed_at', [$start, $end])
            ->get(['completed_by', 'unit_price', 'quantity', 'discount'])
            ->groupBy('completed_by');

        $commissions = $dentists->map(function (User $d) use ($completedItems): array {
            $items = $completedItems->get($d->id) ?? collect();
            $prod = round((float) $items->sum(fn (TreatmentPlanItem $it): float => max(0.0, ((float) $it->unit_price * $it->quantity) - (float) $it->discount)), 2);

            return [
                'name' => $d->name,
                'commission_amount' => round($prod * ((float) $d->commission_rate / 100), 2),
            ];
        })->all();

        $reportData = [
            'summary' => [
                'total_income' => round((float) $payments->sum('amount'), 2),
                'payments_count' => $payments->count(),
                'new_patients' => Patient::query()->whereBetween('created_at', [$start, $end])->count(),
                'appointments_total' => $apptTotal,
                'appointments_attended' => $appts->where('status', AppointmentStatus::Atendida)->count(),
                'appointments_no_show' => $noShow,
                'appointments_cancelled' => $appts->where('status', AppointmentStatus::Cancelada)->count(),
                'no_show_rate' => $apptTotal > 0 ? round(($noShow / $apptTotal) * 100, 1) : 0.0,
                'pending_debt_total' => round((float) array_sum(array_column($pendingBalances, 'balance')), 2),
            ],
            'incomeByMethod' => $incomeByMethod,
            'topProcedures' => $topProcedures,
            'pendingBalances' => $pendingBalances,
            'dentistCommissions' => $commissions,
        ];

        $result = DentalAiService::answerReportQuestion((string) $validated['question'], $reportData, $currency, $clinic);

        return response()->json($result);
    }
}
