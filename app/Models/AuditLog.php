<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $clinic_id
 * @property int|null $user_id
 * @property string $action
 * @property string $auditable_type
 * @property int|null $auditable_id
 * @property string $summary
 * @property array<string, mixed>|null $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $created_at
 */
class AuditLog extends Model
{
    use BelongsToClinic;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'clinic_id',
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'summary',
        'metadata',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
