<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeReportAccess($request);
        [$start, $end] = $this->parseDateRange($request);

        $payments = Payment::query()
            ->with(['patient:id,first_name,last_name,history_number', 'receiver:id,name'])
            ->where('status', 'registrado')
            ->whereBetween('paid_at', [$start, $end])
            ->get();

        $incomeByMethod = [
            ['method' => 'Efectivo', 'total' => (float) $payments->where('payment_method', 'efectivo')->sum('amount')],
            ['method' => 'Tarjeta', 'total' => (float) $payments->where('payment_method', 'tarjeta')->sum('amount')],
            ['method' => 'Yape / Plin', 'total' => (float) $payments->where('payment_method', 'yape_plin')->sum('amount')],
            ['method' => 'Transferencia', 'total' => (float) $payments->where('payment_method', 'transferencia')->sum('amount')],
        ];

        $appointments = Appointment::query()
            ->with('dentist:id,name')
            ->where('is_waitlist', false)
            ->whereBetween('starts_at', [$start, $end])
            ->get();

        $apptTotal = $appointments->count();
        $attended = $appointments->where('status', AppointmentStatus::Atendida)->count();
        $noShow = $appointments->where('status', AppointmentStatus::NoAsistio)->count();
        $cancelled = $appointments->where('status', AppointmentStatus::Cancelada)->count();

        $topProcedures = TreatmentPlanItem::query()
            ->selectRaw('description, COUNT(*) as total_count, SUM(unit_price * quantity - discount) as total_amount')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('description')
            ->orderByDesc('total_count')
            ->limit(8)
            ->get();

        $newPatientsCount = Patient::query()
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $pendingBalances = $this->pendingPatientBalances();
        $dentistCommissions = $this->computeDentistCommissions($start, $end);

        return Inertia::render('reports/index', [
            'filters' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ],
            'summary' => [
                'total_income' => round((float) $payments->sum('amount'), 2),
                'payments_count' => $payments->count(),
                'new_patients' => $newPatientsCount,
                'appointments_total' => $apptTotal,
                'appointments_attended' => $attended,
                'appointments_no_show' => $noShow,
                'appointments_cancelled' => $cancelled,
                'no_show_rate' => $apptTotal > 0 ? round(($noShow / $apptTotal) * 100, 1) : 0.0,
                'pending_debt_total' => round(array_sum(array_column($pendingBalances, 'balance')), 2),
            ],
            'incomeByMethod' => $incomeByMethod,
            'topProcedures' => $topProcedures,
            'pendingBalances' => $pendingBalances,
            'dentistCommissions' => $dentistCommissions,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorizeReportAccess($request);
        [$start, $end] = $this->parseDateRange($request);
        $startStr = $start->toDateString();
        $endStr = $end->toDateString();

        $payments = Payment::query()
            ->with(['patient:id,first_name,last_name,history_number', 'receiver:id,name'])
            ->whereBetween('paid_at', [$start, $end])
            ->orderBy('paid_at')
            ->get();

        AuditLogger::log('export_pdf', Payment::class, "Exportó reporte de ingresos CSV ({$startStr} al {$endStr})", [], $request);

        return response()->streamDownload(function () use ($payments): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Fecha', 'Comprobante', 'Tipo', 'Paciente', 'HC', 'Concepto', 'Medio de Pago', 'Monto', 'Estado', 'Registrado por']);

            foreach ($payments as $p) {
                fputcsv($handle, [
                    $p->paid_at->format('d/m/Y H:i'),
                    $p->receipt_number,
                    strtoupper($p->document_type),
                    $p->patient->full_name ?? '-',
                    $p->patient->history_number ?? '-',
                    $p->concept,
                    $p->payment_method,
                    number_format((float) $p->amount, 2, '.', ''),
                    $p->status,
                    $p->receiver->name ?? '-',
                ]);
            }
            fclose($handle);
        }, "Reporte-Ingresos-{$startStr}-al-{$endStr}.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<int, array{patient_id: int, history_number: string, full_name: string, budgeted: float, paid: float, balance: float}>
     */
    private function pendingPatientBalances(): array
    {
        $patients = Patient::query()
            ->with(['treatmentPlans.items', 'payments'])
            ->where('is_active', true)
            ->get();

        $list = [];
        foreach ($patients as $patient) {
            $budgeted = $patient->treatmentPlans
                ->where('status', '!=', 'cancelado')
                ->sum(function (TreatmentPlan $plan): float {
                    $sub = $plan->items->sum(
                        fn ($item): float => ((float) $item->unit_price * $item->quantity) - (float) $item->discount,
                    );

                    return max(0.0, $sub - (float) $plan->discount_amount);
                });

            $paid = (float) $patient->payments->where('status', 'registrado')->sum('amount');
            $balance = round($budgeted - $paid, 2);

            if ($balance > 0.01) {
                $list[] = [
                    'patient_id' => $patient->id,
                    'history_number' => $patient->history_number,
                    'full_name' => $patient->full_name,
                    'budgeted' => round($budgeted, 2),
                    'paid' => round($paid, 2),
                    'balance' => $balance,
                ];
            }
        }

        usort($list, fn (array $a, array $b): int => $b['balance'] <=> $a['balance']);

        return $list;
    }

    /**
     * @return array<int, array{dentist_id: int, name: string, specialty: string|null, appointments_attended: int, procedures_done: int, production_total: float, commission_rate: float, commission_amount: float}>
     */
    private function computeDentistCommissions(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $dentists = User::query()
            ->whereIn('role', [UserRole::AdminConsultorio, UserRole::Odontologo])
            ->orderBy('name')
            ->get(['id', 'name', 'specialty', 'commission_rate']);

        $attendedByDentist = Appointment::query()
            ->where('status', AppointmentStatus::Atendida)
            ->whereBetween('starts_at', [$start, $end])
            ->get(['dentist_id'])
            ->groupBy('dentist_id');

        $completedItems = TreatmentPlanItem::query()
            ->where('status', 'realizado')
            ->whereBetween('completed_at', [$start, $end])
            ->get(['completed_by', 'unit_price', 'quantity', 'discount'])
            ->groupBy('completed_by');

        $rows = [];
        foreach ($dentists as $dentist) {
            $items = $completedItems->get($dentist->id) ?? collect();
            $production = round((float) $items->sum(
                fn (TreatmentPlanItem $it): float => max(0.0, ((float) $it->unit_price * $it->quantity) - (float) $it->discount),
            ), 2);
            $rate = (float) $dentist->commission_rate;
            $commission = round($production * ($rate / 100), 2);

            $rows[] = [
                'dentist_id' => $dentist->id,
                'name' => $dentist->name,
                'specialty' => $dentist->specialty,
                'appointments_attended' => ($attendedByDentist->get($dentist->id) ?? collect())->count(),
                'procedures_done' => $items->count(),
                'production_total' => $production,
                'commission_rate' => $rate,
                'commission_amount' => $commission,
            ];
        }

        usort($rows, fn (array $a, array $b): int => $b['production_total'] <=> $a['production_total']);

        return $rows;
    }

    private function authorizeReportAccess(Request $request): void
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->canManageCash() && ! $user->canEditClinicalRecords() && ! $user->canViewAuditLogs()) {
            abort(403, 'No tienes permisos para consultar reportes clínicos o financieros.');
        }
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function parseDateRange(Request $request): array
    {
        $defaultStart = CarbonImmutable::today()->startOfMonth();
        $defaultEnd = CarbonImmutable::today();

        try {
            $start = CarbonImmutable::parse((string) $request->query('start_date', $defaultStart->toDateString()))->startOfDay();
        } catch (Throwable) {
            $start = $defaultStart->startOfDay();
        }

        try {
            $end = CarbonImmutable::parse((string) $request->query('end_date', $defaultEnd->toDateString()))->endOfDay();
        } catch (Throwable) {
            $end = $defaultEnd->endOfDay();
        }

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->startOfDay(), $start->endOfDay()];
        }

        return [$start, $end];
    }
}
