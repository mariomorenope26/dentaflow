<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ClinicalEvolution;
use App\Models\User;

class ClinicalEvolutionPolicy
{
    private function sameClinic(User $user, ClinicalEvolution $evolution): bool
    {
        return $user->clinic_id === $evolution->clinic_id;
    }

    public function create(User $user): bool
    {
        return $user->canEditClinicalRecords();
    }

    public function update(User $user, ClinicalEvolution $evolution): bool
    {
        return $this->sameClinic($user, $evolution)
            && $user->canEditClinicalRecords()
            && ! $evolution->is_locked;
    }

    public function sign(User $user, ClinicalEvolution $evolution): bool
    {
        return $this->sameClinic($user, $evolution)
            && $user->canEditClinicalRecords()
            && ! $evolution->is_locked;
    }
}
