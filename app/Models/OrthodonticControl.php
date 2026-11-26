<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $clinic_id
 * @property int $patient_id
 * @property int|null $dentist_id
 * @property Carbon $control_date
 * @property string $stage
 * @property string|null $upper_archwire
 * @property string|null $lower_archwire
 * @property string|null $elastics
 * @property string $hygiene_status
 * @property string $clinical_notes
 * @property Carbon|null $next_control_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class OrthodonticControl extends Model
{
    use BelongsToClinic;

    /** @var list<string> */
    protected $fillable = [
        'clinic_id',
        'patient_id',
        'dentist_id',
        'control_date',
        'stage',
        'upper_archwire',
        'lower_archwire',
        'elastics',
        'hygiene_status',
        'clinical_notes',
        'next_control_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'control_date' => 'datetime',
            'next_control_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function dentist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }
}
