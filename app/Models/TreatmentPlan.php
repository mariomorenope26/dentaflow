<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToClinic;
use Database\Factories\TreatmentPlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $clinic_id
 * @property int $patient_id
 * @property int|null $created_by
 * @property string $title
 * @property string $status
 * @property string $discount_amount
 * @property Carbon|null $valid_until
 * @property string|null $notes
 * @property Carbon|null $approved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TreatmentPlan extends Model
{
    use BelongsToClinic;

    /** @use HasFactory<TreatmentPlanFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'clinic_id',
        'patient_id',
        'created_by',
        'title',
        'status',
        'discount_amount',
        'valid_until',
        'notes',
        'approved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:2',
            'valid_until' => 'date',
            'approved_at' => 'datetime',
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

    /**
     * @return HasMany<TreatmentPlanItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(TreatmentPlanItem::class)->orderBy('phase')->orderBy('id');
    }
}
