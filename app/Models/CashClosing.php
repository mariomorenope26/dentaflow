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
 * @property int|null $closed_by
 * @property Carbon $closing_date
 * @property string $opening_amount
 * @property string $cash_total
 * @property string $card_total
 * @property string $digital_total
 * @property string $transfer_total
 * @property string $grand_total
 * @property string|null $notes
 * @property Carbon $closed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CashClosing extends Model
{
    use BelongsToClinic;

    /** @var list<string> */
    protected $fillable = [
        'clinic_id',
        'closed_by',
        'closing_date',
        'opening_amount',
        'cash_total',
        'card_total',
        'digital_total',
        'transfer_total',
        'grand_total',
        'notes',
        'closed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'closing_date' => 'date',
            'opening_amount' => 'decimal:2',
            'cash_total' => 'decimal:2',
            'card_total' => 'decimal:2',
            'digital_total' => 'decimal:2',
            'transfer_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
