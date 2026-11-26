<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\Odontogram;
use App\Models\OdontogramFinding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OdontogramFinding>
 */
class OdontogramFindingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'odontogram_id' => Odontogram::factory(),
            'tooth_code' => '16',
            'surface' => 'O',
            'condition_code' => 'caries',
            'status' => 'por_realizar',
        ];
    }
}
