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
 * @property int $inventory_item_id
 * @property int|null $user_id
 * @property int|null $patient_id
 * @property string $type
 * @property string $quantity
 * @property string $reason
 * @property Carbon $moved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class InventoryMovement extends Model
{
    use BelongsToClinic;

    /** @var list<string> */
    protected $fillable = [
        'clinic_id',
        'inventory_item_id',
        'user_id',
        'patient_id',
        'type',
        'quantity',
        'reason',
        'moved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'moved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<InventoryItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
