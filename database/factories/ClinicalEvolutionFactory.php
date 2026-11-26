<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\ClinicalEvolution;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClinicalEvolution>
 */
class ClinicalEvolutionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'patient_id' => Patient::factory(),
            'user_id' => User::factory(),
            'attended_at' => now(),
            'procedure_summary' => 'Restauración con resina compuesta',
            'clinical_notes' => 'Procedimiento completado sin complicaciones.',
            'version' => 1,
        ];
    }
}
