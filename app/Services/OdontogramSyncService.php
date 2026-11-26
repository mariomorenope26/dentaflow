<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Odontogram;
use App\Models\OdontogramFinding;
use App\Models\Patient;
use App\Models\TreatmentCatalog;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OdontogramSyncService
{
    /**
     * Replace all findings of an odontogram atomically and bump its version.
     *
     * @param  array<int, array{
     *     tooth_code: string,
     *     surface: string,
     *     condition_code: string,
     *     status: string,
     *     notes?: string|null,
     *     treatment_plan_item_id?: int|null
     * }>  $findings
     */
    public static function replaceFindings(Odontogram $odontogram, array $findings, User $actor): Odontogram
    {
        return DB::transaction(function () use ($odontogram, $findings, $actor): Odontogram {
            $odontogram->findings()->delete();

            foreach ($findings as $item) {
                OdontogramFinding::create([
                    'clinic_id' => $odontogram->clinic_id,
                    'odontogram_id' => $odontogram->id,
                    'tooth_code' => $item['tooth_code'],
                    'surface' => $item['surface'] ?: 'FULL',
                    'condition_code' => $item['condition_code'],
                    'status' => $item['status'],
                    'notes' => $item['notes'] ?? null,
                    'recorded_by' => $actor->id,
                    'treatment_plan_item_id' => $item['treatment_plan_item_id'] ?? null,
                ]);
            }

            $odontogram->forceFill([
                'version' => $odontogram->version + 1,
            ])->save();

            return $odontogram->fresh(['findings', 'creator']) ?? $odontogram;
        });
    }

    public static function convertFindingToTreatment(OdontogramFinding $finding, User $actor): TreatmentPlanItem
    {
        return DB::transaction(function () use ($finding, $actor): TreatmentPlanItem {
            $finding->loadMissing('odontogram.patient');
            /** @var Odontogram $odontogram */
            $odontogram = $finding->odontogram;
            /** @var Patient $patient */
            $patient = $odontogram->patient;

            $plan = TreatmentPlan::query()
                ->where('patient_id', $patient->id)
                ->whereIn('status', ['borrador', 'aprobado', 'en_curso'])
                ->latest('id')
                ->first();

            if (! $plan instanceof TreatmentPlan) {
                $plan = TreatmentPlan::create([
                    'clinic_id' => $patient->clinic_id,
                    'patient_id' => $patient->id,
                    'created_by' => $actor->id,
                    'title' => 'Plan de tratamiento · '.$patient->history_number,
                    'status' => 'borrador',
                    'valid_until' => now()->addDays(30)->toDateString(),
                ]);
            }

            $catalog = TreatmentCatalog::query()
                ->where('clinic_id', $patient->clinic_id)
                ->where('odontogram_condition', $finding->condition_code)
                ->where('is_active', true)
                ->first();

            $surfaceSuffix = $finding->surface !== 'FULL' ? " ({$finding->surface})" : '';
            $conditionLabel = OdontogramCatalog::labelFor($finding->condition_code);
            $description = $catalog !== null
                ? "{$catalog->name} — Pieza {$finding->tooth_code}{$surfaceSuffix}"
                : "Tratamiento de {$conditionLabel} — Pieza {$finding->tooth_code}{$surfaceSuffix}";

            $item = TreatmentPlanItem::create([
                'clinic_id' => $patient->clinic_id,
                'treatment_plan_id' => $plan->id,
                'treatment_catalog_id' => $catalog?->id,
                'tooth_code' => $finding->tooth_code,
                'surface' => $finding->surface,
                'description' => $description,
                'phase' => $finding->condition_code === 'extraccion_indicada' ? 'Urgencia' : 'Fase 2: Restauradora',
                'priority' => $finding->condition_code === 'caries' ? 'alta' : 'media',
                'quantity' => 1,
                'unit_price' => $catalog !== null ? (float) $catalog->default_price : 120.00,
                'discount' => 0,
                'status' => 'pendiente',
            ]);

            $finding->update([
                'treatment_plan_item_id' => $item->id,
            ]);

            return $item;
        });
    }

    public static function markToothFindingAsDone(TreatmentPlanItem $item, User $actor): void
    {
        if ($item->tooth_code === null) {
            return;
        }

        $plan = $item->plan;
        if ($plan === null) {
            return;
        }

        $latestOdontogram = Odontogram::query()
            ->where('patient_id', $plan->patient_id)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->first();

        if (! $latestOdontogram instanceof Odontogram) {
            return;
        }

        $linkedFindings = OdontogramFinding::query()
            ->where('odontogram_id', $latestOdontogram->id)
            ->where(function ($query) use ($item): void {
                $query->where('treatment_plan_item_id', $item->id)
                    ->orWhere(function ($q) use ($item): void {
                        $q->where('tooth_code', $item->tooth_code);
                        if ($item->surface !== null && $item->surface !== '') {
                            $q->where('surface', $item->surface);
                        }
                    });
            })
            ->get();

        foreach ($linkedFindings as $finding) {
            $newCondition = $finding->condition_code === 'caries' ? 'obturacion' : $finding->condition_code;
            $newStatus = $finding->condition_code === 'extraccion_indicada' ? 'ausente' : 'realizado';
            $newCondition = $finding->condition_code === 'extraccion_indicada' ? 'ausente' : $newCondition;

            $finding->update([
                'condition_code' => $newCondition,
                'status' => $newStatus,
                'recorded_by' => $actor->id,
            ]);
        }
    }
}
