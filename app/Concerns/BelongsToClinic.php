<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Clinic;
use App\Models\Scopes\ClinicScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin Model
 */
trait BelongsToClinic
{
    public static function bootBelongsToClinic(): void
    {
        static::addGlobalScope(new ClinicScope);

        static::creating(function (Model $model): void {
            if ($model->getAttribute('clinic_id') !== null) {
                return;
            }

            if (Auth::hasUser()) {
                $user = Auth::user();
                if ($user instanceof User && $user->clinic_id !== null) {
                    $model->setAttribute('clinic_id', $user->clinic_id);
                }
            }
        });
    }

    /**
     * @return BelongsTo<Clinic, $this>
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
