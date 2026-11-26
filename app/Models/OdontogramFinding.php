<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToClinic;
use Database\Factories\OdontogramFindingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $clinic_id
 * @property int $odontogram_id
 * @property string $tooth_code
 * @property string $surface
 * @property string $condition_code
 * @property string $status
 * @property string|null $notes
 * @property int|null $recorded_by
 * @property int|null $treatment_plan_item_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class OdontogramFinding extends Model
{
    use BelongsToClinic;

    /** @use HasFactory<OdontogramFindingFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'clinic_id',
        'odontogram_id',
        'tooth_code',
        'surface',
        'condition_code',
        'status',
        'notes',
        'recorded_by',
        'treatment_plan_item_id',
    ];

    /**
     * @return BelongsTo<Odontogram, $this>
     */
    public function odontogram(): BelongsTo
    {
        return $this->belongsTo(Odontogram::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * @return BelongsTo<TreatmentPlanItem, $this>
     */
    public function treatmentPlanItem(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlanItem::class);
    }
}
