<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\TreatmentCatalog;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\OdontogramSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TreatmentPlanController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        Gate::authorize('editClinical', $patient);

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'status' => ['nullable', 'string', 'in:borrador,aprobado,en_curso,completado,cancelado'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $plan = TreatmentPlan::create([
            'clinic_id' => $patient->clinic_id,
            'patient_id' => $patient->id,
            'created_by' => $user->id,
            'title' => $validated['title'],
            'status' => $validated['status'] ?? 'borrador',
            'discount_amount' => $validated['discount_amount'] ?? 0,
            'valid_until' => $validated['valid_until'] ?? now()->addDays(30)->toDateString(),
            'notes' => $validated['notes'] ?? null,
            'approved_at' => ($validated['status'] ?? 'borrador') === 'aprobado' ? now() : null,
        ]);

        AuditLogger::log('create', $plan, "Creó plan de tratamiento '{$plan->title}' para {$patient->full_name}", [], $request);

        return back()->with('success', 'Plan de tratamiento creado.');
    }

    public function update(Request $request, Patient $patient, TreatmentPlan $plan): RedirectResponse
    {
        if ($plan->patient_id !== $patient->id) {
            abort(404);
        }

        Gate::authorize('editClinical', $patient);

        $validated = $request->validate([
            'status' => ['sometimes', 'string', 'in:borrador,aprobado,en_curso,completado,cancelado'],
            'discount_amount' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (($validated['status'] ?? null) === 'aprobado' && $plan->approved_at === null) {
            $validated['approved_at'] = now();
        }

        $plan->update($validated);

        AuditLogger::log('update', $plan, "Actualizó plan '{$plan->title}' a estado {$plan->status}", [], $request);

        return back()->with('success', 'Plan de tratamiento actualizado.');
    }

    public function storeItem(Request $request, Patient $patient, TreatmentPlan $plan): RedirectResponse
    {
        if ($plan->patient_id !== $patient->id) {
            abort(404);
        }

        Gate::authorize('editClinical', $patient);

        $validated = $request->validate([
            'treatment_catalog_id' => ['nullable', 'integer', 'exists:treatment_catalogs,id'],
            'tooth_code' => ['nullable', 'string', 'max:10'],
            'surface' => ['nullable', 'string', 'max:10'],
            'description' => ['required', 'string', 'max:255'],
            'phase' => ['required', 'string', 'max:60'],
            'priority' => ['required', 'string', 'in:alta,media,baja'],
            'quantity' => ['required', 'integer', 'min:1', 'max:32'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (! empty($validated['treatment_catalog_id'])) {
            $catalog = TreatmentCatalog::query()->find((int) $validated['treatment_catalog_id']);
            if ($catalog !== null && $catalog->clinic_id !== $patient->clinic_id) {
                abort(403);
            }
        }

        $item = TreatmentPlanItem::create([
            'clinic_id' => $patient->clinic_id,
            'treatment_plan_id' => $plan->id,
            ...$validated,
            'discount' => $validated['discount'] ?? 0,
            'status' => 'pendiente',
        ]);

        AuditLogger::log('create', $item, "Agregó procedimiento '{$item->description}' al plan {$plan->title}", [], $request);

        return back()->with('success', 'Procedimiento agregado al plan.');
    }

    public function updateItem(Request $request, Patient $patient, TreatmentPlanItem $item): RedirectResponse
    {
        $item->loadMissing('plan');
        if ($item->plan === null || $item->plan->patient_id !== $patient->id) {
            abort(404);
        }

        Gate::authorize('editClinical', $patient);

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pendiente,en_curso,realizado'],
        ]);

        $wasDone = $item->status === 'realizado';
        $isNowDone = $validated['status'] === 'realizado';

        $item->update([
            'status' => $validated['status'],
            'completed_at' => $isNowDone ? now() : null,
            'completed_by' => $isNowDone ? $user->id : null,
        ]);

        if (! $wasDone && $isNowDone) {
            OdontogramSyncService::markToothFindingAsDone($item, $user);
        }

        AuditLogger::log('update', $item, "Cambió estado de '{$item->description}' a {$item->status}", [], $request);

        return back()->with('success', $isNowDone ? 'Tratamiento marcado como realizado y odontograma actualizado.' : 'Estado del tratamiento actualizado.');
    }

    public function destroy(Request $request, Patient $patient, TreatmentPlan $plan): RedirectResponse
    {
        if ($plan->patient_id !== $patient->id) {
            abort(404);
        }

        Gate::authorize('editClinical', $patient);

        $title = $plan->title;
        $clinicId = $plan->clinic_id;
        $plan->delete();

        AuditLogger::log('delete', TreatmentPlan::class, "Eliminó plan de tratamiento '{$title}' de {$patient->full_name}", [], $request, $clinicId);

        return back()->with('success', 'Plan de tratamiento eliminado.');
    }

    public function destroyItem(Request $request, Patient $patient, TreatmentPlanItem $item): RedirectResponse
    {
        $item->loadMissing('plan');
        if ($item->plan === null || $item->plan->patient_id !== $patient->id) {
            abort(404);
        }

        Gate::authorize('editClinical', $patient);

        $desc = $item->description;
        $clinicId = $item->clinic_id;
        $item->delete();

        AuditLogger::log('delete', TreatmentPlanItem::class, "Eliminó ítem '{$desc}' del plan de {$patient->full_name}", [], $request, $clinicId);

        return back()->with('success', 'Procedimiento eliminado del plan.');
    }
}
