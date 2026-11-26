<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'history_number' => 'HC-'.fake()->unique()->numerify('####'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'document_type' => 'DNI',
            'document_number' => fake()->numerify('########'),
            'birth_date' => fake()->dateTimeBetween('-65 years', '-5 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['F', 'M']),
            'phone' => '9'.fake()->numerify('########'),
            'email' => fake()->safeEmail(),
            'address' => fake()->streetAddress(),
            'occupation' => fake()->jobTitle(),
            'insurance_provider' => fake()->randomElement(['Particular', 'Rimac Salud', 'Pacífico Seguros']),
            'tags' => ['adulto'],
            'is_active' => true,
        ];
    }
}
