<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToClinic;
use App\Enums\AppointmentStatus;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $clinic_id
 * @property int $patient_id
 * @property int $dentist_id
 * @property string $chair
 * @property string $title
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property AppointmentStatus $status
 * @property bool $is_waitlist
 * @property string|null $reason
 * @property string|null $notes
 * @property int|null $created_by
 * @property int $version
 * @property string|null $confirmation_token
 * @property Carbon|null $reminder_sent_at
 * @property Carbon|null $patient_confirmed_at
 * @property Carbon|null $reschedule_requested_at
 * @property string|null $reschedule_note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Appointment extends Model
{
    use BelongsToClinic;

    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'clinic_id',
        'patient_id',
        'dentist_id',
        'chair',
        'title',
        'starts_at',
        'ends_at',
        'status',
        'is_waitlist',
        'reason',
        'notes',
        'created_by',
        'version',
        'confirmation_token',
        'reminder_sent_at',
        'patient_confirmed_at',
        'reschedule_requested_at',
        'reschedule_note',
    ];

    protected static function booted(): void
    {
        static::creating(function (Appointment $appointment): void {
            if (! $appointment->confirmation_token) {
                $appointment->confirmation_token = Str::random(32);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => AppointmentStatus::class,
            'is_waitlist' => 'boolean',
            'version' => 'integer',
            'reminder_sent_at' => 'datetime',
            'patient_confirmed_at' => 'datetime',
            'reschedule_requested_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<LabOrder, $this>
     */
    public function labOrders(): HasMany
    {
        return $this->hasMany(LabOrder::class);
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
    public function dentist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
