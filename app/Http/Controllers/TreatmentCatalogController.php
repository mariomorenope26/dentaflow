<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\TreatmentCatalog;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\OdontogramCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TreatmentCatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $category = trim((string) $request->query('category', ''));

        $query = TreatmentCatalog::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($category !== '') {
            $query->where('category', $category);
        }

        $items = $query->orderBy('category')->orderBy('name')->get();

        $categories = TreatmentCatalog::query()
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->values();

        return Inertia::render('treatments/index', [
            'catalog' => $items,
            'categories' => $categories,
            'conditions' => OdontogramCatalog::conditions(),
            'filters' => [
                'search' => $search,
                'category' => $category,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->canEditClinicalRecords() && ! $user->canManageClinic()) {
            abort(403);
        }

        $clinicId = $user->clinic_id ?? (int) Clinic::query()->value('id');

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:160'],
            'category' => ['required', 'string', 'max:60'],
            'default_price' => ['required', 'numeric', 'min:0', 'max:99999'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'odontogram_condition' => ['nullable', 'string', 'max:50'],
        ]);

        $exists = TreatmentCatalog::query()
            ->where('clinic_id', $clinicId)
            ->where('code', $validated['code'])
            ->exists();

        if ($exists) {
            return back()->with('error', "Ya existe un procedimiento con código {$validated['code']}.");
        }

        $item = TreatmentCatalog::create([
            ...$validated,
            'clinic_id' => $clinicId,
            'is_active' => true,
        ]);

        AuditLogger::log('create', $item, "Agregó tratamiento al catálogo: {$item->name}", [], $request);

        return back()->with('success', 'Tratamiento agregado al catálogo.');
    }

    public function update(Request $request, TreatmentCatalog $treatment): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->canEditClinicalRecords() && ! $user->canManageClinic()) {
            abort(403);
        }

        if ($treatment->clinic_id !== $user->clinic_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'category' => ['required', 'string', 'max:60'],
            'default_price' => ['required', 'numeric', 'min:0', 'max:99999'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'odontogram_condition' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $treatment->update($validated);

        AuditLogger::log('update', $treatment, "Actualizó tratamiento {$treatment->name}", [], $request);

        return back()->with('success', 'Tratamiento actualizado.');
    }

    public function destroy(Request $request, TreatmentCatalog $treatment): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->canEditClinicalRecords() && ! $user->canManageClinic()) {
            abort(403);
        }

        if ($treatment->clinic_id !== $user->clinic_id) {
            abort(403);
        }

        $name = $treatment->name;
        $clinicId = $treatment->clinic_id;
        $treatment->delete();

        AuditLogger::log('delete', TreatmentCatalog::class, "Eliminó procedimiento del catálogo: {$name}", [], $request, $clinicId);

        return back()->with('success', 'Tratamiento eliminado del catálogo.');
    }
}
