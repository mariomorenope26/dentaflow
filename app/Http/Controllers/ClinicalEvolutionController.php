<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ClinicalEvolution;
use App\Models\Patient;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClinicalEvolutionController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        Gate::authorize('editClinical', $patient);

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'attended_at' => ['required', 'date'],
            'procedure_summary' => ['required', 'string', 'max:255'],
            'clinical_notes' => ['required', 'string', 'max:5000'],
            'soap_subjective' => ['nullable', 'string', 'max:3000'],
            'soap_objective' => ['nullable', 'string', 'max:3000'],
            'soap_assessment' => ['nullable', 'string', 'max:2000'],
            'soap_plan' => ['nullable', 'string', 'max:3000'],
            'cie10_code' => ['nullable', 'string', 'max:20'],
            'prescription_notes' => ['nullable', 'string', 'max:3000'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'parent_evolution_id' => ['nullable', 'integer', 'exists:clinical_evolutions,id'],
            'correction_reason' => ['nullable', 'required_with:parent_evolution_id', 'string', 'max:1000'],
            'sign_now' => ['boolean'],
        ]);

        if (! empty($validated['parent_evolution_id'])) {
            $parent = ClinicalEvolution::query()->findOrFail((int) $validated['parent_evolution_id']);
            if ($parent->patient_id !== $patient->id || $parent->clinic_id !== $patient->clinic_id) {
                abort(403);
            }
        }

        $signNow = (bool) ($validated['sign_now'] ?? false);
        $signedAt = $signNow ? now() : null;
        $sigHash = $signNow
            ? hash('sha256', implode('|', [
                (string) $patient->id,
                (string) ($patient->fhir_uuid ?? ''),
                (string) $validated['attended_at'],
                (string) $validated['procedure_summary'],
                (string) $validated['clinical_notes'],
                (string) $user->id,
                $signedAt->toIso8601String(),
            ]))
            : null;

        $evolution = ClinicalEvolution::create([
            'clinic_id' => $patient->clinic_id,
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'appointment_id' => $validated['appointment_id'] ?? null,
            'attended_at' => $validated['attended_at'],
            'procedure_summary' => $validated['procedure_summary'],
            'clinical_notes' => $validated['clinical_notes'],
            'soap_subjective' => $validated['soap_subjective'] ?? null,
            'soap_objective' => $validated['soap_objective'] ?? null,
            'soap_assessment' => $validated['soap_assessment'] ?? null,
            'soap_plan' => $validated['soap_plan'] ?? null,
            'cie10_code' => $validated['cie10_code'] ?? null,
            'prescription_notes' => $validated['prescription_notes'] ?? null,
            'parent_evolution_id' => $validated['parent_evolution_id'] ?? null,
            'correction_reason' => $validated['correction_reason'] ?? null,
            'signed_at' => $signedAt,
            'signed_by' => $signNow ? $user->id : null,
            'signature_hash' => $sigHash,
        ]);

        if (! empty($validated['appointment_id'])) {
            Appointment::query()
                ->where('id', $validated['appointment_id'])
                ->where('clinic_id', $patient->clinic_id)
                ->update(['status' => AppointmentStatus::Atendida]);
        }

        $action = ! empty($validated['parent_evolution_id']) ? 'correct' : ($signNow ? 'sign' : 'create');
        AuditLogger::log($action, $evolution, "Registró evolución clínica para {$patient->full_name}: {$evolution->procedure_summary}", [], $request);

        return back()->with('success', $signNow ? 'Nota de evolución registrada y firmada (bloqueada).' : 'Nota de evolución guardada como borrador.');
    }

    public function update(Request $request, Patient $patient, ClinicalEvolution $evolution): RedirectResponse
    {
        if ($evolution->patient_id !== $patient->id) {
            abort(404);
        }

        if ($evolution->is_locked) {
            abort(403, 'Este registro ya fue firmado y bloqueado. Debe agregar una nota de corrección.');
        }

        Gate::authorize('update', $evolution);

        $validated = $request->validate([
            'attended_at' => ['required', 'date'],
            'procedure_summary' => ['required', 'string', 'max:255'],
            'clinical_notes' => ['required', 'string', 'max:5000'],
            'soap_subjective' => ['nullable', 'string', 'max:3000'],
            'soap_objective' => ['nullable', 'string', 'max:3000'],
            'soap_assessment' => ['nullable', 'string', 'max:2000'],
            'soap_plan' => ['nullable', 'string', 'max:3000'],
            'cie10_code' => ['nullable', 'string', 'max:20'],
            'prescription_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $evolution->update([
            ...$validated,
            'version' => $evolution->version + 1,
        ]);

        AuditLogger::log('update', $evolution, "Editó borrador de evolución #{$evolution->id} para {$patient->full_name}", [], $request);

        return back()->with('success', 'Nota de evolución actualizada.');
    }

    public function sign(Request $request, Patient $patient, ClinicalEvolution $evolution): RedirectResponse
    {
        if ($evolution->patient_id !== $patient->id) {
            abort(404);
        }

        Gate::authorize('sign', $evolution);

        /** @var User $user */
        $user = $request->user();
        $signedAt = now();
        $sigHash = hash('sha256', implode('|', [
            (string) $evolution->id,
            (string) ($patient->fhir_uuid ?? ''),
            $evolution->attended_at->toIso8601String(),
            $evolution->procedure_summary,
            $evolution->clinical_notes,
            (string) $user->id,
            $signedAt->toIso8601String(),
        ]));

        $evolution->update([
            'signed_at' => $signedAt,
            'signed_by' => $user->id,
            'signature_hash' => $sigHash,
        ]);

        AuditLogger::log('sign', $evolution, "Firmó y bloqueó evolución #{$evolution->id} de {$patient->full_name}", [], $request);

        return back()->with('success', 'Evolución clínica firmada y bloqueada con trazabilidad.');
    }
}
