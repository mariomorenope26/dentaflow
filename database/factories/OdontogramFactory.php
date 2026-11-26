<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\Odontogram;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Odontogram>
 */
class OdontogramFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'patient_id' => Patient::factory(),
            'type' => 'inicial',
            'dentition' => 'adulto',
            'notation' => 'FDI',
            'recorded_at' => now(),
            'notes' => 'Odontograma inicial',
            'version' => 1,
        ];
    }
}
