<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TreatmentPlanItem>
 */
class TreatmentPlanItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'treatment_plan_id' => TreatmentPlan::factory(),
            'description' => 'Restauración con resina',
            'phase' => 'Fase 2: Restauradora',
            'priority' => 'alta',
            'quantity' => 1,
            'unit_price' => 130.00,
            'discount' => 0,
            'status' => 'pendiente',
        ];
    }
}
