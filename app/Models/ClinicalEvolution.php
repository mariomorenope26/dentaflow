<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToClinic;
use Database\Factories\ClinicalEvolutionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $clinic_id
 * @property int $patient_id
 * @property int $user_id
 * @property int|null $appointment_id
 * @property Carbon $attended_at
 * @property string $procedure_summary
 * @property string $clinical_notes
 * @property string|null $soap_subjective
 * @property string|null $soap_objective
 * @property string|null $soap_assessment
 * @property string|null $soap_plan
 * @property string|null $cie10_code
 * @property string|null $prescription_notes
 * @property Carbon|null $signed_at
 * @property int|null $signed_by
 * @property string|null $signature_hash
 * @property int|null $parent_evolution_id
 * @property string|null $correction_reason
 * @property int $version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ClinicalEvolution extends Model
{
    use BelongsToClinic;

    /** @use HasFactory<ClinicalEvolutionFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'clinic_id',
        'patient_id',
        'user_id',
        'appointment_id',
        'attended_at',
        'procedure_summary',
        'clinical_notes',
        'soap_subjective',
        'soap_objective',
        'soap_assessment',
        'soap_plan',
        'cie10_code',
        'prescription_notes',
        'signed_at',
        'signed_by',
        'signature_hash',
        'parent_evolution_id',
        'correction_reason',
        'version',
    ];

    /** @var list<string> */
    protected $appends = ['is_locked'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attended_at' => 'datetime',
            'signed_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function getIsLockedAttribute(): bool
    {
        return $this->signed_at !== null;
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
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    /**
     * @return BelongsTo<ClinicalEvolution, $this>
     */
    public function parentEvolution(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_evolution_id');
    }

    /**
     * @return HasMany<ClinicalEvolution, $this>
     */
    public function corrections(): HasMany
    {
        return $this->hasMany(self::class, 'parent_evolution_id')->orderBy('created_at');
    }
}
