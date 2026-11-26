<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Clinic;
use App\Models\MedicalHistory;
use App\Models\Odontogram;
use App\Models\Patient;
use App\Models\TreatmentCatalog;
use App\Models\TreatmentPlan;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\OdontogramCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Patient::class);

        $search = trim((string) $request->query('search', ''));
        $tag = trim((string) $request->query('tag', ''));

        $query = Patient::query()
            ->with('medicalHistory:id,patient_id,medical_alerts,allergies,definitive_diagnosis')
            ->withCount(['appointments', 'clinicalEvolutions']);

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('history_number', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($tag !== '') {
            $query->whereJsonContains('tags', $tag);
        }

        $patients = $query->orderByDesc('updated_at')->paginate(15)->withQueryString();

        return Inertia::render('patients/index', [
            'patients' => $patients,
            'filters' => [
                'search' => $search,
                'tag' => $tag,
            ],
            'availableTags' => ['VIP', 'ortodoncia', 'niño', 'crónico', 'adulto'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Patient::class);

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'document_type' => ['required', 'string', 'in:DNI,CE,PASAPORTE,OTRO'],
            'document_number' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', 'string', 'in:M,F,O'],
            'blood_type' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:120'],
            'insurance_provider' => ['nullable', 'string', 'max:120'],
            'insurance_number' => ['nullable', 'string', 'max:60'],
            'guardian_name' => ['nullable', 'string', 'max:150'],
            'guardian_phone' => ['nullable', 'string', 'max:40'],
            'guardian_relationship' => ['nullable', 'string', 'max:60'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:40'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $patient = DB::transaction(function () use ($validated, $user, $request): Patient {
            $clinicId = $user->clinic_id ?? (int) Clinic::query()->value('id');

            $patient = Patient::create([
                ...$validated,
                'clinic_id' => $clinicId,
                'history_number' => Patient::nextHistoryNumber($clinicId),
                'created_by' => $user->id,
                'is_active' => true,
            ]);

            MedicalHistory::create([
                'clinic_id' => $clinicId,
                'patient_id' => $patient->id,
                'updated_by' => $user->id,
                'medical_alerts' => [],
                'chronic_conditions' => [],
                'habits' => [],
                'vital_signs' => [],
            ]);

            $isChild = $patient->age !== null && $patient->age < 12;

            Odontogram::create([
                'clinic_id' => $clinicId,
                'patient_id' => $patient->id,
                'created_by' => $user->id,
                'type' => 'inicial',
                'dentition' => $isChild ? 'nino' : 'adulto',
                'notation' => $user->clinic?->settings['odontogram_notation'] ?? 'FDI',
                'recorded_at' => now(),
                'notes' => 'Odontograma inicial creado al aperturar historia.',
            ]);

            AuditLogger::log('create', $patient, "Registró paciente {$patient->full_name} ({$patient->history_number})", [], $request);

            return $patient;
        });

        return redirect()
            ->route('patients.show', $patient)
            ->with('success', "Paciente {$patient->full_name} registrado con historia {$patient->history_number}.");
    }

    public function show(Request $request, Patient $patient): Response
    {
        Gate::authorize('view', $patient);

        $patient->load([
            'medicalHistory.updater:id,name',
            'clinicalEvolutions.author:id,name,specialty,license_number',
            'clinicalEvolutions.signer:id,name',
            'clinicalEvolutions.corrections.author:id,name',
            'odontograms.creator:id,name',
            'odontograms.findings',
            'treatmentPlans.items',
            'treatmentPlans.creator:id,name',
            'appointments.dentist:id,name,specialty',
            'payments.receiver:id,name',
            'payments.treatmentPlan:id,title',
            'clinicalDocuments.creator:id,name,license_number',
            'images.uploader:id,name',
            'periodontograms.creator:id,name',
            'orthodonticControls.dentist:id,name',
            'labOrders.dentist:id,name',
            'paymentInstallments.payment:id,receipt_number',
        ]);

        AuditLogger::log('view', $patient, "Consultó ficha clínica de {$patient->full_name} ({$patient->history_number})", [], $request);

        $dentists = User::query()
            ->where('clinic_id', $patient->clinic_id)
            ->whereIn('role', [UserRole::AdminConsultorio, UserRole::Odontologo])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'specialty']);

        $treatmentCatalog = TreatmentCatalog::query()
            ->where('clinic_id', $patient->clinic_id)
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $totalBudgeted = $patient->treatmentPlans
            ->where('status', '!=', 'cancelado')
            ->sum(function (TreatmentPlan $plan): float {
                $subtotal = $plan->items->sum(
                    fn ($item): float => ((float) $item->unit_price * $item->quantity) - (float) $item->discount,
                );

                return max(0.0, $subtotal - (float) $plan->discount_amount);
            });

        $totalPaid = (float) $patient->payments
            ->where('status', 'registrado')
            ->sum('amount');

        return Inertia::render('patients/show', [
            'patient' => $patient,
            'timeline' => $this->buildTimeline($patient),
            'dentists' => $dentists,
            'treatmentCatalog' => $treatmentCatalog,
            'odontogramConditions' => OdontogramCatalog::conditions(),
            'cie10Codes' => OdontogramCatalog::cie10Codes(),
            'evolutionTemplates' => OdontogramCatalog::evolutionTemplates(),
            'documentTemplates' => OdontogramCatalog::documentTemplates(),
            'financialSummary' => [
                'total_budgeted' => round($totalBudgeted, 2),
                'total_paid' => round($totalPaid, 2),
                'balance_due' => round(max(0.0, $totalBudgeted - $totalPaid), 2),
            ],
            'initialTab' => (string) $request->query('tab', 'resumen'),
        ]);
    }

    public function update(Request $request, Patient $patient): RedirectResponse
    {
        Gate::authorize('update', $patient);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'document_type' => ['required', 'string', 'in:DNI,CE,PASAPORTE,OTRO'],
            'document_number' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', 'string', 'in:M,F,O'],
            'blood_type' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:120'],
            'insurance_provider' => ['nullable', 'string', 'max:120'],
            'insurance_number' => ['nullable', 'string', 'max:60'],
            'guardian_name' => ['nullable', 'string', 'max:150'],
            'guardian_phone' => ['nullable', 'string', 'max:40'],
            'guardian_relationship' => ['nullable', 'string', 'max:60'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:40'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $patient->update($validated);

        AuditLogger::log('update', $patient, "Actualizó datos de filiación de {$patient->full_name}", [], $request);

        return back()->with('success', 'Ficha del paciente actualizada.');
    }

    public function destroy(Request $request, Patient $patient): RedirectResponse
    {
        Gate::authorize('update', $patient);

        $patient->load('images');
        foreach ($patient->images as $img) {
            Storage::disk('public')->delete($img->file_path);
        }

        $summary = "Eliminó paciente {$patient->full_name} ({$patient->history_number})";
        $clinicId = $patient->clinic_id;
        $patient->delete();

        AuditLogger::log('delete', Patient::class, $summary, [], $request, $clinicId);

        return redirect()->route('patients.index')->with('success', 'Paciente eliminado del sistema.');
    }

    /**
     * @return array<int, array{id: string, kind: string, date: string, title: string, detail: string, actor: ?string}>
     */
    private function buildTimeline(Patient $patient): array
    {
        $events = [];

        foreach ($patient->clinicalEvolutions as $evolution) {
            $events[] = [
                'id' => "evol-{$evolution->id}",
                'kind' => 'evolucion',
                'date' => $evolution->attended_at->toIso8601String(),
                'title' => $evolution->procedure_summary,
                'detail' => $evolution->clinical_notes,
                'actor' => $evolution->author?->name,
            ];
        }

        foreach ($patient->odontograms as $odontogram) {
            $events[] = [
                'id' => "odo-{$odontogram->id}",
                'kind' => 'odontograma',
                'date' => $odontogram->recorded_at->toIso8601String(),
                'title' => 'Odontograma '.ucfirst($odontogram->type)." ({$odontogram->findings->count()} hallazgos)",
                'detail' => $odontogram->notes ?? 'Registro odontológico actualizado.',
                'actor' => $odontogram->creator?->name,
            ];
        }

        foreach ($patient->appointments as $appointment) {
            $events[] = [
                'id' => "appt-{$appointment->id}",
                'kind' => 'cita',
                'date' => $appointment->starts_at->toIso8601String(),
                'title' => "Cita: {$appointment->title} ({$appointment->status->label()})",
                'detail' => "{$appointment->chair} · {$appointment->starts_at->format('d/m/Y H:i')}",
                'actor' => $appointment->dentist?->name,
            ];
        }

        foreach ($patient->payments->where('status', 'registrado') as $payment) {
            $events[] = [
                'id' => "pay-{$payment->id}",
                'kind' => 'pago',
                'date' => $payment->paid_at->toIso8601String(),
                'title' => "Pago {$payment->receipt_number}: S/ ".number_format((float) $payment->amount, 2),
                'detail' => "{$payment->concept} ({$payment->payment_method})",
                'actor' => $payment->receiver?->name,
            ];
        }

        foreach ($patient->clinicalDocuments as $doc) {
            $events[] = [
                'id' => "doc-{$doc->id}",
                'kind' => 'documento',
                'date' => $doc->issued_at->toIso8601String(),
                'title' => $doc->title,
                'detail' => $doc->signed_at !== null ? 'Firmado digitalmente' : ucfirst($doc->type),
                'actor' => $doc->creator?->name,
            ];
        }

        usort($events, fn (array $a, array $b): int => strcmp($b['date'], $a['date']));

        return array_slice($events, 0, 30);
    }
}
