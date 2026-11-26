<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = Carbon::today()->setHour(10)->setMinute(0);

        return [
            'clinic_id' => Clinic::factory(),
            'patient_id' => Patient::factory(),
            'dentist_id' => User::factory(),
            'chair' => 'Sillón 1',
            'title' => 'Control odontológico',
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMinutes(45),
            'status' => AppointmentStatus::Confirmada,
            'is_waitlist' => false,
            'reason' => 'Evaluación periódica',
            'version' => 1,
        ];
    }
}
