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
 * @property int|null $operator_id
 * @property string $autoclave_name
 * @property string $cycle_number
 * @property Carbon $sterilized_at
 * @property string $temperature_c
 * @property string $pressure_bar
 * @property int $duration_minutes
 * @property string $load_summary
 * @property string $chemical_indicator
 * @property string $biological_indicator
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SterilizationCycle extends Model
{
    use BelongsToClinic;

    /** @var list<string> */
    protected $fillable = [
        'clinic_id',
        'operator_id',
        'autoclave_name',
        'cycle_number',
        'sterilized_at',
        'temperature_c',
        'pressure_bar',
        'duration_minutes',
        'load_summary',
        'chemical_indicator',
        'biological_indicator',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sterilized_at' => 'datetime',
            'temperature_c' => 'decimal:1',
            'pressure_bar' => 'decimal:2',
            'duration_minutes' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}
