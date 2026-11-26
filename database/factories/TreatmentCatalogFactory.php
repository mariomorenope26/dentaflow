<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\TreatmentCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TreatmentCatalog>
 */
class TreatmentCatalogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'code' => 'PROC-'.fake()->unique()->numerify('###'),
            'name' => 'Procedimiento odontológico',
            'category' => 'Operatoria',
            'default_price' => 150.00,
            'duration_minutes' => 30,
            'is_active' => true,
        ];
    }
}
