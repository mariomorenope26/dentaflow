<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Periodontogram;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PeriodontogramController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        Gate::authorize('editClinical', $patient);

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'entries' => ['required', 'array'],
            'entries.*.pd' => ['required', 'array', 'size:6'],
            'entries.*.pd.*' => ['integer', 'min:0', 'max:15'],
            'entries.*.bop' => ['boolean'],
            'entries.*.plaque' => ['boolean'],
            'entries.*.recession' => ['integer', 'min:0', 'max:10'],
            'entries.*.mobility' => ['integer', 'min:0', 'max:3'],
            'entries.*.furcation' => ['integer', 'min:0', 'max:3'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        /** @var array<string, array{pd: array<int, int>, bop?: bool, plaque?: bool, recession?: int, mobility?: int, furcation?: int}> $entries */
        $entries = $validated['entries'];
        $totalTeeth = max(1, count($entries));
        $bopCount = 0;
        $plaqueCount = 0;

        foreach ($entries as $entry) {
            if (! empty($entry['bop'])) {
                $bopCount++;
            }
            if (! empty($entry['plaque'])) {
                $plaqueCount++;
            }
        }

        $bleedingIndex = round(($bopCount / $totalTeeth) * 100, 2);
        $plaqueIndex = round(($plaqueCount / $totalTeeth) * 100, 2);

        $perio = Periodontogram::create([
            'clinic_id' => $patient->clinic_id,
            'patient_id' => $patient->id,
            'created_by' => $user->id,
            'recorded_at' => now(),
            'entries' => $entries,
            'bleeding_index' => $bleedingIndex,
            'plaque_index' => $plaqueIndex,
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLogger::log('create', $perio, "Registró periodontograma de {$patient->full_name} (Sangrado: {$bleedingIndex}%)", [], $request);

        return back()->with('success', 'Periodontograma registrado correctamente.');
    }

    public function destroy(Request $request, Patient $patient, Periodontogram $periodontogram): RedirectResponse
    {
        if ($periodontogram->patient_id !== $patient->id) {
            abort(404);
        }

        Gate::authorize('editClinical', $patient);

        $periodontogram->delete();

        AuditLogger::log('delete', Periodontogram::class, "Eliminó registro de periodontograma de {$patient->full_name}", [], $request, $patient->clinic_id);

        return back()->with('success', 'Periodontograma eliminado.');
    }
}
