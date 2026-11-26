<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Clinic;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Clinic>
 */
class ClinicFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Consultorio Dental '.fake()->lastName();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 9999),
            'ruc' => '20'.fake()->numerify('#########'),
            'slogan' => 'Odontología Integral y Especialidades',
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'address' => fake()->streetAddress(),
            'primary_color' => '#0E8FA3',
            'currency_symbol' => 'S/',
            'chairs' => ['Sillón 1', 'Sillón 2'],
            'settings' => [
                'odontogram_notation' => 'FDI',
                'auto_lock_minutes' => 15,
            ],
            'is_active' => true,
        ];
    }
}
