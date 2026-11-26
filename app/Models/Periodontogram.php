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
 * @property int|null $created_by
 * @property Carbon $recorded_at
 * @property array<string, array<string, mixed>> $entries
 * @property string $bleeding_index
 * @property string $plaque_index
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Periodontogram extends Model
{
    use BelongsToClinic;

    /** @var list<string> */
    protected $fillable = [
        'clinic_id',
        'patient_id',
        'created_by',
        'recorded_at',
        'entries',
        'bleeding_index',
        'plaque_index',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'entries' => 'array',
            'bleeding_index' => 'decimal:2',
            'plaque_index' => 'decimal:2',
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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
