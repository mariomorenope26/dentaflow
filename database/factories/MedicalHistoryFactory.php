<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\MedicalHistory;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicalHistory>
 */
class MedicalHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'patient_id' => Patient::factory(),
            'chief_complaint' => 'Evaluación odontológica general',
            'current_illness' => 'Sin dolor agudo al momento de la consulta.',
            'allergies' => null,
            'chronic_conditions' => [],
            'habits' => [],
            'is_pregnant' => false,
            'medical_alerts' => [],
            'vital_signs' => ['pa' => '120/80', 'fc' => '72 lpm', 'temp' => '36.6 °C'],
            'presumptive_diagnosis' => 'Caries de la dentina',
            'cie10_code' => 'K02.1',
            'version' => 1,
        ];
    }
}
