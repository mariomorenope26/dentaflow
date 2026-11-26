<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToClinic;
use Database\Factories\TreatmentPlanItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $clinic_id
 * @property int $treatment_plan_id
 * @property int|null $treatment_catalog_id
 * @property string|null $tooth_code
 * @property string|null $surface
 * @property string $description
 * @property string $phase
 * @property string $priority
 * @property int $quantity
 * @property string $unit_price
 * @property string $discount
 * @property string $status
 * @property Carbon|null $completed_at
 * @property int|null $completed_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TreatmentPlanItem extends Model
{
    use BelongsToClinic;

    /** @use HasFactory<TreatmentPlanItemFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'clinic_id',
        'treatment_plan_id',
        'treatment_catalog_id',
        'tooth_code',
        'surface',
        'description',
        'phase',
        'priority',
        'quantity',
        'unit_price',
        'discount',
        'status',
        'completed_at',
        'completed_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'discount' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TreatmentPlan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class, 'treatment_plan_id');
    }

    /**
     * @return BelongsTo<TreatmentCatalog, $this>
     */
    public function catalog(): BelongsTo
    {
        return $this->belongsTo(TreatmentCatalog::class, 'treatment_catalog_id');
    }

    /**
     * @return HasMany<OdontogramFinding, $this>
     */
    public function odontogramFindings(): HasMany
    {
        return $this->hasMany(OdontogramFinding::class);
    }
}
