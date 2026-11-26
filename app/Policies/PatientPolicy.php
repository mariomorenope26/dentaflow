<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    private function sameClinic(User $user, Patient $patient): bool
    {
        return $user->clinic_id === $patient->clinic_id;
    }

    public function viewAny(User $user): bool
    {
        return $user->clinic_id !== null;
    }

    public function view(User $user, Patient $patient): bool
    {
        return $this->sameClinic($user, $patient);
    }

    public function create(User $user): bool
    {
        return $user->canManagePatients();
    }

    public function update(User $user, Patient $patient): bool
    {
        return $this->sameClinic($user, $patient) && $user->canManagePatients();
    }

    public function editClinical(User $user, Patient $patient): bool
    {
        return $this->sameClinic($user, $patient) && $user->canEditClinicalRecords();
    }

    public function exportPdf(User $user, Patient $patient): bool
    {
        return $this->sameClinic($user, $patient) && ($user->canEditClinicalRecords() || $user->canManagePatients());
    }
}
