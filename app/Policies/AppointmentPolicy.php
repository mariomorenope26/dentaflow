<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    private function sameClinic(User $user, Appointment $appointment): bool
    {
        return $user->clinic_id === $appointment->clinic_id;
    }

    public function viewAny(User $user): bool
    {
        return $user->clinic_id !== null;
    }

    public function create(User $user): bool
    {
        return $user->canManageAppointments();
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $this->sameClinic($user, $appointment) && $user->canManageAppointments();
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $this->sameClinic($user, $appointment) && $user->canManageAppointments();
    }
}
