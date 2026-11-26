<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\TreatmentPlan;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\DentalAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CrmController extends Controller
{
    /** @var list<string> */
    private const STAGES = [
        'nuevo',
        'presupuesto_pendiente',
        'en_tratamiento',
        'mantenimiento',
        'inactivo',
    ];

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->canManagePatients() && ! $user->canManageCash()) {
            abort(403, 'No tienes permisos para acceder al CRM de pacientes.');
        }

        $clinic = $user->clinic ?? Clinic::query()->firstOrFail();
        $stageFilter = trim((string) $request->query('stage', ''));
        $search = trim((string) $request->query('search', ''));

        $patients = Patient::query()
            ->where('is_active', true)
            ->with([
                'treatmentPlans.items',
                'payments',
                'appointments',
                'paymentInstallments',
            ])
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($sub) use ($search): void {
                    $sub->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (Patient $patient): array {
                $pendingBudget = (float) $patient->treatmentPlans
                    ->whereIn('status', ['borrador', 'presentado'])
                    ->sum(function (TreatmentPlan $plan): float {
                        $sub = $plan->items->sum(fn ($it): float => ((float) $it->unit_price * $it->quantity) - (float) $it->discount);

                        return max(0.0, $sub - (float) $plan->discount_amount);
                    });

                $totalBudget = (float) $patient->treatmentPlans
                    ->where('status', '!=', 'cancelado')
                    ->sum(function (TreatmentPlan $plan): float {
                        $sub = $plan->items->sum(fn ($it): float => ((float) $it->unit_price * $it->quantity) - (float) $it->discount);

                        return max(0.0, $sub - (float) $plan->discount_amount);
                    });

                $paid = (float) $patient->payments->where('status', 'registrado')->sum('amount');
                $balance = round(max(0.0, $totalBudget - $paid), 2);

                $lastVisit = $patient->appointments
                    ->where('status', AppointmentStatus::Atendida)
                    ->sortByDesc('starts_at')
                    ->first();

                $daysSinceVisit = $lastVisit !== null
                    ? (int) $lastVisit->starts_at->diffInDays(now())
                    : (int) ($patient->created_at?->diffInDays(now()) ?? 0);

                $overdueCount = $patient->paymentInstallments
                    ->whereIn('status', ['pendiente', 'parcial'])
                    ->filter(fn ($i): bool => $i->due_date->isPast())
                    ->count();

                $suggestedCampaign = match (true) {
                    $overdueCount > 0 || $balance > 0.01 => 'morosidad',
                    $pendingBudget > 0.01 || $patient->crm_stage === 'presupuesto_pendiente' => 'presupuesto',
                    $daysSinceVisit >= 180 || $patient->crm_stage === 'mantenimiento' => 'recall_6m',
                    $patient->crm_stage === 'inactivo' => 'reactivacion',
                    default => 'post_atencion',
                };

                return [
                    'id' => $patient->id,
                    'history_number' => $patient->history_number,
                    'full_name' => $patient->full_name,
                    'phone' => $patient->phone,
                    'email' => $patient->email,
                    'crm_stage' => $patient->crm_stage ?: 'en_tratamiento',
                    'last_contacted_at' => $patient->last_contacted_at?->toIso8601String(),
                    'crm_notes' => $patient->crm_notes,
                    'pending_budget' => round($pendingBudget, 2),
                    'pending_balance' => $balance,
                    'days_since_visit' => $daysSinceVisit,
                    'overdue_count' => $overdueCount,
                    'suggested_campaign' => $suggestedCampaign,
                ];
            });

        $stageCounts = [
            'nuevo' => $patients->where('crm_stage', 'nuevo')->count(),
            'presupuesto_pendiente' => $patients->where('crm_stage', 'presupuesto_pendiente')->count(),
            'en_tratamiento' => $patients->where('crm_stage', 'en_tratamiento')->count(),
            'mantenimiento' => $patients->where('crm_stage', 'mantenimiento')->count(),
            'inactivo' => $patients->where('crm_stage', 'inactivo')->count(),
        ];

        $filtered = $stageFilter !== ''
            ? $patients->where('crm_stage', $stageFilter)->values()->all()
            : $patients->values()->all();

        return Inertia::render('crm/index', [
            'patients' => $filtered,
            'stageCounts' => $stageCounts,
            'currencySymbol' => $clinic->currency_symbol,
            'onlineBookingUrl' => $clinic->online_booking_enabled ? url('/reservar-cita') : null,
            'filters' => [
                'stage' => $stageFilter,
                'search' => $search,
            ],
        ]);
    }

    public function updatePatientStage(Request $request, Patient $patient): RedirectResponse
    {
        Gate::authorize('update', $patient);

        $validated = $request->validate([
            'crm_stage' => ['sometimes', 'string', Rule::in(self::STAGES)],
            'crm_notes' => ['nullable', 'string', 'max:1000'],
            'mark_contacted' => ['sometimes', 'boolean'],
        ]);

        $updates = [];
        if (isset($validated['crm_stage'])) {
            $updates['crm_stage'] = $validated['crm_stage'];
        }
        if (array_key_exists('crm_notes', $validated)) {
            $updates['crm_notes'] = $validated['crm_notes'];
        }
        if (! empty($validated['mark_contacted'])) {
            $updates['last_contacted_at'] = now();
        }

        $patient->update($updates);

        AuditLogger::log('update', $patient, "Actualizó seguimiento CRM de {$patient->full_name} ({$patient->crm_stage})", [], $request);

        return back()->with('success', 'Seguimiento CRM actualizado.');
    }

    public function draftWhatsapp(Request $request, Patient $patient): JsonResponse
    {
        Gate::authorize('view', $patient);

        /** @var User $user */
        $user = $request->user();
        $clinic = $user->clinic ?? Clinic::query()->firstOrFail();

        $validated = $request->validate([
            'campaign_type' => ['required', 'string', 'in:presupuesto,morosidad,recall_6m,reactivacion,post_atencion'],
        ]);

        $patient->loadMissing(['treatmentPlans.items', 'payments', 'appointments']);

        $pendingBudget = (float) $patient->treatmentPlans
            ->whereIn('status', ['borrador', 'presentado'])
            ->sum(fn (TreatmentPlan $plan): float => max(0.0, (float) $plan->items->sum(fn ($it): float => ((float) $it->unit_price * $it->quantity) - (float) $it->discount) - (float) $plan->discount_amount));

        $totalBudget = (float) $patient->treatmentPlans
            ->where('status', '!=', 'cancelado')
            ->sum(fn (TreatmentPlan $plan): float => max(0.0, (float) $plan->items->sum(fn ($it): float => ((float) $it->unit_price * $it->quantity) - (float) $it->discount) - (float) $plan->discount_amount));

        $paid = (float) $patient->payments->where('status', 'registrado')->sum('amount');
        $lastVisit = $patient->appointments->where('status', AppointmentStatus::Atendida)->sortByDesc('starts_at')->first();

        $draft = DentalAiService::draftWhatsappFollowup(
            $patient,
            (string) $validated['campaign_type'],
            $clinic,
            [
                'pending_budget' => round($pendingBudget, 2),
                'pending_balance' => round(max(0.0, $totalBudget - $paid), 2),
                'days_since_visit' => $lastVisit !== null ? (int) $lastVisit->starts_at->diffInDays(now()) : null,
            ],
        );

        $patient->update(['last_contacted_at' => now()]);

        AuditLogger::log('update', $patient, "Generó mensaje CRM WhatsApp IA ({$validated['campaign_type']}) para {$patient->full_name}", [], $request);

        return response()->json($draft);
    }
}
