<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Odontogram;
use App\Models\OdontogramFinding;
use App\Models\Patient;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\OdontogramSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OdontogramController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        Gate::authorize('editClinical', $patient);

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:inicial,evolutivo'],
            'dentition' => ['required', 'string', 'in:adulto,nino,mixto'],
            'notation' => ['required', 'string', 'in:FDI,UNIVERSAL'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'clone_from_id' => ['nullable', 'integer', 'exists:odontograms,id'],
        ]);

        $odontogram = Odontogram::create([
            'clinic_id' => $patient->clinic_id,
            'patient_id' => $patient->id,
            'created_by' => $user->id,
            'type' => $validated['type'],
            'dentition' => $validated['dentition'],
            'notation' => $validated['notation'],
            'recorded_at' => now(),
            'notes' => $validated['notes'] ?? 'Nuevo registro de odontograma.',
        ]);

        if (! empty($validated['clone_from_id'])) {
            $source = Odontogram::query()
                ->with('findings')
                ->where('patient_id', $patient->id)
                ->find($validated['clone_from_id']);

            if ($source instanceof Odontogram) {
                $clonedFindings = $source->findings->map(fn (OdontogramFinding $f): array => [
                    'tooth_code' => $f->tooth_code,
                    'surface' => $f->surface,
                    'condition_code' => $f->condition_code,
                    'status' => $f->status,
                    'notes' => $f->notes,
                    'treatment_plan_item_id' => $f->treatment_plan_item_id,
                ])->all();

                OdontogramSyncService::replaceFindings($odontogram, $clonedFindings, $user);
            }
        }

        AuditLogger::log('create', $odontogram, "Creó odontograma {$odontogram->type} ({$odontogram->dentition}) para {$patient->full_name}", [], $request);

        return back()->with('success', 'Nuevo odontograma creado.');
    }

    public function update(Request $request, Patient $patient, Odontogram $odontogram): RedirectResponse
    {
        if ($odontogram->patient_id !== $patient->id) {
            abort(404);
        }

        Gate::authorize('update', $odontogram);

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'dentition' => ['required', 'string', 'in:adulto,nino,mixto'],
            'notation' => ['required', 'string', 'in:FDI,UNIVERSAL'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'expected_version' => ['nullable', 'integer'],
            'findings' => ['present', 'array'],
            'findings.*.tooth_code' => ['required', 'string', 'max:10'],
            'findings.*.surface' => ['required', 'string', 'in:O,V,L,M,D,FULL'],
            'findings.*.condition_code' => ['required', 'string', 'max:50'],
            'findings.*.status' => ['required', 'string', 'in:por_realizar,realizado,sano,ausente'],
            'findings.*.notes' => ['nullable', 'string', 'max:500'],
            'findings.*.treatment_plan_item_id' => ['nullable', 'integer'],
        ]);

        if (isset($validated['expected_version']) && (int) $validated['expected_version'] !== $odontogram->version) {
            return back()->with('error', 'El odontograma cambió en otro dispositivo. Revise los cambios antes de guardar.');
        }

        $odontogram->update([
            'dentition' => $validated['dentition'],
            'notation' => $validated['notation'],
            'notes' => $validated['notes'] ?? $odontogram->notes,
        ]);

        /** @var array<int, array{tooth_code: string, surface: string, condition_code: string, status: string, notes?: string|null, treatment_plan_item_id?: int|null}> $findings */
        $findings = $validated['findings'];
        OdontogramSyncService::replaceFindings($odontogram, $findings, $user);

        AuditLogger::log(
            'update',
            $odontogram,
            "Actualizó odontograma #{$odontogram->id} de {$patient->full_name} (".count($findings).' hallazgos)',
            ['findings_count' => count($findings)],
            $request,
        );

        return back()->with('success', 'Odontograma guardado con fecha y profesional.');
    }

    public function convertToTreatment(Request $request, Patient $patient, Odontogram $odontogram, OdontogramFinding $finding): RedirectResponse
    {
        if ($odontogram->patient_id !== $patient->id || $finding->odontogram_id !== $odontogram->id) {
            abort(404);
        }

        Gate::authorize('update', $odontogram);

        /** @var User $user */
        $user = $request->user();

        $item = OdontogramSyncService::convertFindingToTreatment($finding, $user);

        AuditLogger::log(
            'create',
            $item,
            "Convirtió hallazgo {$finding->condition_code} en pieza {$finding->tooth_code} a tratamiento propuesto",
            [],
            $request,
        );

        return back()->with('success', "Hallazgo convertido a tratamiento propuesto: {$item->description}");
    }
}
