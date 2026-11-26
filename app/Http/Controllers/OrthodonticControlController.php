<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\OrthodonticControl;
use App\Models\Patient;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrthodonticControlController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        Gate::authorize('editClinical', $patient);

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'control_date' => ['required', 'date'],
            'stage' => ['required', 'string', 'max:80'],
            'upper_archwire' => ['nullable', 'string', 'max:80'],
            'lower_archwire' => ['nullable', 'string', 'max:80'],
            'elastics' => ['nullable', 'string', 'max:120'],
            'hygiene_status' => ['required', 'string', 'in:buena,regular,deficiente'],
            'clinical_notes' => ['required', 'string', 'max:3000'],
            'next_control_date' => ['nullable', 'date'],
        ]);

        $control = OrthodonticControl::create([
            'clinic_id' => $patient->clinic_id,
            'patient_id' => $patient->id,
            'dentist_id' => $user->id,
            ...$validated,
        ]);

        AuditLogger::log('create', $control, "Registró control de ortodoncia ({$control->stage}) para {$patient->full_name}", [], $request);

        return back()->with('success', 'Control de ortodoncia guardado.');
    }

    public function destroy(Request $request, Patient $patient, OrthodonticControl $control): RedirectResponse
    {
        if ($control->patient_id !== $patient->id) {
            abort(404);
        }

        Gate::authorize('editClinical', $patient);

        $control->delete();

        AuditLogger::log('delete', OrthodonticControl::class, "Eliminó control de ortodoncia de {$patient->full_name}", [], $request, $patient->clinic_id);

        return back()->with('success', 'Control de ortodoncia eliminado.');
    }
}
