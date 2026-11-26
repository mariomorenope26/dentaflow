<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MedicalHistoryController extends Controller
{
    public function update(Request $request, Patient $patient): RedirectResponse
    {
        Gate::authorize('editClinical', $patient);

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'chief_complaint' => ['nullable', 'string', 'max:2000'],
            'current_illness' => ['nullable', 'string', 'max:3000'],
            'medical_history_notes' => ['nullable', 'string', 'max:3000'],
            'dental_history_notes' => ['nullable', 'string', 'max:3000'],
            'allergies' => ['nullable', 'string', 'max:1000'],
            'current_medications' => ['nullable', 'string', 'max:1000'],
            'chronic_conditions' => ['nullable', 'array'],
            'chronic_conditions.*' => ['string', 'max:100'],
            'family_history' => ['nullable', 'string', 'max:2000'],
            'review_of_systems' => ['nullable', 'array'],
            'habits' => ['nullable', 'array'],
            'habits.*' => ['string', 'max:100'],
            'is_pregnant' => ['boolean'],
            'asa_classification' => ['nullable', 'string', 'in:ASA I,ASA II,ASA III,ASA IV,ASA V,ASA E'],
            'medical_alerts' => ['nullable', 'array'],
            'medical_alerts.*.type' => ['required', 'string', 'max:50'],
            'medical_alerts.*.label' => ['required', 'string', 'max:150'],
            'medical_alerts.*.severity' => ['required', 'string', 'in:alta,media,baja'],
            'vital_signs' => ['nullable', 'array'],
            'vital_signs.pa' => ['nullable', 'string', 'max:30'],
            'vital_signs.fc' => ['nullable', 'string', 'max:30'],
            'vital_signs.fr' => ['nullable', 'string', 'max:30'],
            'vital_signs.temp' => ['nullable', 'string', 'max:30'],
            'vital_signs.sato2' => ['nullable', 'string', 'max:30'],
            'vital_signs.weight_kg' => ['nullable', 'string', 'max:20'],
            'vital_signs.height_cm' => ['nullable', 'string', 'max:20'],
            'vital_signs.bmi' => ['nullable', 'string', 'max:20'],
            'extraoral_exam' => ['nullable', 'string', 'max:2000'],
            'intraoral_exam' => ['nullable', 'string', 'max:2000'],
            'tmj_exam' => ['nullable', 'string', 'max:2000'],
            'soft_tissues_exam' => ['nullable', 'string', 'max:2000'],
            'periodontal_exam' => ['nullable', 'string', 'max:2000'],
            'occlusion_exam' => ['nullable', 'string', 'max:2000'],
            'stomatognathic_exam' => ['nullable', 'array'],
            'presumptive_diagnosis' => ['nullable', 'string', 'max:2000'],
            'definitive_diagnosis' => ['nullable', 'string', 'max:2000'],
            'cie10_code' => ['nullable', 'string', 'max:20'],
            'expected_version' => ['nullable', 'integer'],
        ]);

        $history = MedicalHistory::firstOrNew([
            'clinic_id' => $patient->clinic_id,
            'patient_id' => $patient->id,
        ]);

        $expectedVersion = $validated['expected_version'] ?? null;
        if ($history->exists && $expectedVersion !== null && (int) $expectedVersion !== $history->version) {
            return back()->with('error', 'Conflicto de edición: la historia clínica fue modificada por otro usuario. Recargue antes de guardar.');
        }

        unset($validated['expected_version']);

        $history->fill($validated);
        $history->updated_by = $user->id;
        $history->version = ($history->version ?? 0) + 1;
        $history->save();

        AuditLogger::log('update', $history, "Actualizó historia clínica de {$patient->full_name} ({$patient->history_number})", [], $request);

        return back()->with('success', 'Historia clínica guardada correctamente.');
    }
}
