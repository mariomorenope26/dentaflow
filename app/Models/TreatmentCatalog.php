<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToClinic;
use Database\Factories\TreatmentCatalogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $clinic_id
 * @property string $code
 * @property string $name
 * @property string $category
 * @property string $default_price
 * @property int $duration_minutes
 * @property string|null $odontogram_condition
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TreatmentCatalog extends Model
{
    use BelongsToClinic;

    /** @use HasFactory<TreatmentCatalogFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'clinic_id',
        'code',
        'name',
        'category',
        'default_price',
        'duration_minutes',
        'odontogram_condition',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_price' => 'decimal:2',
            'duration_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
