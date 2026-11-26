<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Clinic;
use App\Models\User;

class ClinicPolicy
{
    public function manage(User $user, Clinic $clinic): bool
    {
        return $user->clinic_id === $clinic->id && $user->canManageClinic();
    }

    public function viewAudit(User $user, Clinic $clinic): bool
    {
        return $user->clinic_id === $clinic->id && $user->canViewAuditLogs();
    }
}
