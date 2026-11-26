<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Odontogram;
use App\Models\User;

class OdontogramPolicy
{
    private function sameClinic(User $user, Odontogram $odontogram): bool
    {
        return $user->clinic_id === $odontogram->clinic_id;
    }

    public function create(User $user): bool
    {
        return $user->canEditClinicalRecords();
    }

    public function update(User $user, Odontogram $odontogram): bool
    {
        return $this->sameClinic($user, $odontogram) && $user->canEditClinicalRecords();
    }
}
