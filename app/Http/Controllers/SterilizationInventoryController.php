<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Patient;
use App\Models\SterilizationCycle;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SterilizationInventoryController extends Controller
{
    public function index(Request $request): Response
    {
        $cycles = SterilizationCycle::query()
            ->with('operator:id,name')
            ->orderByDesc('sterilized_at')
            ->orderByDesc('id')
            ->limit(60)
            ->get();

        $items = InventoryItem::query()
            ->orderBy('name')
            ->get();

        $movements = InventoryMovement::query()
            ->with(['item:id,code,name,unit', 'user:id,name', 'patient:id,first_name,last_name,history_number'])
            ->orderByDesc('moved_at')
            ->orderByDesc('id')
            ->limit(40)
            ->get();

        $patients = Patient::query()
            ->where('is_active', true)
            ->orderBy('last_name')
            ->limit(150)
            ->get(['id', 'first_name', 'last_name', 'history_number']);

        $today = CarbonImmutable::today();
        $lowStockCount = $items->filter(fn (InventoryItem $i): bool => (float) $i->current_stock <= (float) $i->min_stock)->count();
        $expiringSoonCount = $items->filter(fn (InventoryItem $i): bool => $i->expires_at !== null && $i->expires_at->lessThanOrEqualTo($today->addDays(30)))->count();
        $pendingBioCount = SterilizationCycle::query()->where('biological_indicator', 'pendiente')->count();

        return Inertia::render('sterilization/index', [
            'cycles' => $cycles,
            'items' => $items,
            'movements' => $movements,
            'patients' => $patients,
            'stats' => [
                'cycles_month' => SterilizationCycle::query()->where('sterilized_at', '>=', $today->startOfMonth())->count(),
                'pending_bio' => $pendingBioCount,
                'low_stock' => $lowStockCount,
                'expiring_soon' => $expiringSoonCount,
            ],
            'initialTab' => (string) $request->query('tab', 'esterilizacion'),
        ]);
    }

    public function storeCycle(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $clinicId = $user->clinic_id ?? (int) Clinic::query()->value('id');

        $validated = $request->validate([
            'autoclave_name' => ['required', 'string', 'max:120'],
            'cycle_number' => ['required', 'string', 'max:40'],
            'sterilized_at' => ['required', 'date'],
            'temperature_c' => ['required', 'numeric', 'min:100', 'max:160'],
            'pressure_bar' => ['required', 'numeric', 'min:0.5', 'max:5'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:180'],
            'load_summary' => ['required', 'string', 'max:1000'],
            'chemical_indicator' => ['required', 'string', 'in:conforme,fallido'],
            'biological_indicator' => ['required', 'string', 'in:pendiente,negativo_conforme,positivo_alerta'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $cycle = SterilizationCycle::create([
            ...$validated,
            'clinic_id' => $clinicId,
            'operator_id' => $user->id,
        ]);

        AuditLogger::log('create', $cycle, "Registró ciclo de esterilización {$cycle->cycle_number} ({$cycle->autoclave_name})", [], $request);

        return back()->with('success', "Ciclo de esterilización {$cycle->cycle_number} registrado.");
    }

    public function updateCycle(Request $request, SterilizationCycle $cycle): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        if ($cycle->clinic_id !== $user->clinic_id) {
            abort(403);
        }

        $validated = $request->validate([
            'chemical_indicator' => ['sometimes', 'string', 'in:conforme,fallido'],
            'biological_indicator' => ['sometimes', 'string', 'in:pendiente,negativo_conforme,positivo_alerta'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $cycle->update($validated);

        AuditLogger::log('update', $cycle, "Actualizó control biológico/químico del ciclo {$cycle->cycle_number}", [], $request);

        return back()->with('success', "Ciclo {$cycle->cycle_number} actualizado.");
    }

    public function destroyCycle(Request $request, SterilizationCycle $cycle): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        if ($cycle->clinic_id !== $user->clinic_id) {
            abort(403);
        }

        $num = $cycle->cycle_number;
        $clinicId = $cycle->clinic_id;
        $cycle->delete();

        AuditLogger::log('delete', SterilizationCycle::class, "Eliminó registro de ciclo de esterilización {$num}", [], $request, $clinicId);

        return back()->with('success', "Ciclo {$num} eliminado.");
    }

    public function storeItem(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $clinicId = $user->clinic_id ?? (int) Clinic::query()->value('id');

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:160'],
            'category' => ['required', 'string', 'max:80'],
            'unit' => ['required', 'string', 'max:40'],
            'lot_number' => ['nullable', 'string', 'max:60'],
            'expires_at' => ['nullable', 'date'],
            'current_stock' => ['required', 'numeric', 'min:0', 'max:99999'],
            'min_stock' => ['required', 'numeric', 'min:0', 'max:99999'],
            'unit_cost' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'supplier' => ['nullable', 'string', 'max:140'],
        ]);

        $item = DB::transaction(function () use ($validated, $clinicId, $user): InventoryItem {
            $item = InventoryItem::create([
                ...$validated,
                'clinic_id' => $clinicId,
                'unit_cost' => $validated['unit_cost'] ?? 0,
                'is_active' => true,
            ]);

            if ((float) $validated['current_stock'] > 0) {
                InventoryMovement::create([
                    'clinic_id' => $clinicId,
                    'inventory_item_id' => $item->id,
                    'user_id' => $user->id,
                    'type' => 'entrada',
                    'quantity' => $validated['current_stock'],
                    'reason' => 'Stock inicial de registro en Kardex',
                    'moved_at' => now(),
                ]);
            }

            return $item;
        });

        AuditLogger::log('create', $item, "Registró insumo en Kardex: {$item->name} (Lote: {$item->lot_number})", [], $request);

        return back()->with('success', "Insumo {$item->name} agregado al Kardex.");
    }

    public function updateItem(Request $request, InventoryItem $item): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        if ($item->clinic_id !== $user->clinic_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:160'],
            'category' => ['sometimes', 'string', 'max:80'],
            'unit' => ['sometimes', 'string', 'max:40'],
            'lot_number' => ['nullable', 'string', 'max:60'],
            'expires_at' => ['nullable', 'date'],
            'min_stock' => ['sometimes', 'numeric', 'min:0', 'max:99999'],
            'unit_cost' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'supplier' => ['nullable', 'string', 'max:140'],
        ]);

        $item->update($validated);

        AuditLogger::log('update', $item, "Actualizó ficha de insumo {$item->name}", [], $request);

        return back()->with('success', 'Insumo actualizado.');
    }

    public function destroyItem(Request $request, InventoryItem $item): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        if ($item->clinic_id !== $user->clinic_id) {
            abort(403);
        }

        $name = $item->name;
        $clinicId = $item->clinic_id;
        $item->delete();

        AuditLogger::log('delete', InventoryItem::class, "Eliminó insumo del Kardex: {$name}", [], $request, $clinicId);

        return back()->with('success', 'Insumo eliminado del Kardex.');
    }

    public function recordMovement(Request $request, InventoryItem $item): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        if ($item->clinic_id !== $user->clinic_id) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:entrada,salida,ajuste'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:99999'],
            'reason' => ['required', 'string', 'max:255'],
            'patient_id' => ['nullable', 'integer', 'exists:patients,id'],
            'lot_number' => ['nullable', 'string', 'max:60'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $qty = (float) $validated['quantity'];
        $current = (float) $item->current_stock;

        if ($validated['type'] === 'salida' && $qty > $current) {
            return back()->with('error', "Stock insuficiente ({$current} {$item->unit} disponibles).");
        }

        DB::transaction(function () use ($item, $validated, $qty, $current, $user): void {
            $newStock = match ($validated['type']) {
                'entrada' => $current + $qty,
                'salida' => max(0.0, $current - $qty),
                default => $qty,
            };

            $updates = ['current_stock' => $newStock];
            if (! empty($validated['lot_number'])) {
                $updates['lot_number'] = $validated['lot_number'];
            }
            if (! empty($validated['expires_at'])) {
                $updates['expires_at'] = $validated['expires_at'];
            }

            $item->update($updates);

            InventoryMovement::create([
                'clinic_id' => $item->clinic_id,
                'inventory_item_id' => $item->id,
                'user_id' => $user->id,
                'patient_id' => $validated['patient_id'] ?? null,
                'type' => $validated['type'],
                'quantity' => $qty,
                'reason' => $validated['reason'],
                'moved_at' => now(),
            ]);
        });

        AuditLogger::log('update', $item, "Movimiento Kardex ({$validated['type']}) en {$item->name}: {$qty} {$item->unit}", [], $request);

        return back()->with('success', "Movimiento de Kardex registrado en {$item->name}.");
    }
}
