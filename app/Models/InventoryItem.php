<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $clinic_id
 * @property string $code
 * @property string $name
 * @property string $category
 * @property string $unit
 * @property string|null $lot_number
 * @property Carbon|null $expires_at
 * @property string $current_stock
 * @property string $min_stock
 * @property string $unit_cost
 * @property string|null $supplier
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class InventoryItem extends Model
{
    use BelongsToClinic;

    /** @var list<string> */
    protected $fillable = [
        'clinic_id',
        'code',
        'name',
        'category',
        'unit',
        'lot_number',
        'expires_at',
        'current_stock',
        'min_stock',
        'unit_cost',
        'supplier',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'date',
            'current_stock' => 'decimal:2',
            'min_stock' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<InventoryMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class)->orderByDesc('moved_at');
    }
}
