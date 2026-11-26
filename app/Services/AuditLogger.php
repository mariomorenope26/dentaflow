<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function log(
        string $action,
        Model|string $auditable,
        string $summary,
        array $metadata = [],
        ?Request $request = null,
        ?int $clinicId = null,
    ): void {
        $user = Auth::user();
        $resolvedClinicId = $clinicId;

        if ($resolvedClinicId === null && $auditable instanceof Model && $auditable->getAttribute('clinic_id') !== null) {
            $resolvedClinicId = (int) $auditable->getAttribute('clinic_id');
        }

        if ($resolvedClinicId === null && $user instanceof User && $user->clinic_id !== null) {
            $resolvedClinicId = $user->clinic_id;
        }

        $auditableType = $auditable instanceof Model ? $auditable::class : $auditable;
        $auditableId = $auditable instanceof Model ? (int) $auditable->getKey() : null;

        AuditLog::create([
            'clinic_id' => $resolvedClinicId,
            'user_id' => $user instanceof User ? $user->id : null,
            'action' => $action,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'summary' => mb_substr($summary, 0, 255),
            'metadata' => $metadata,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent() ? mb_substr((string) $request->userAgent(), 0, 255) : null,
        ]);
    }
}
