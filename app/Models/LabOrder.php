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
 * @property int $patient_id
 * @property int|null $dentist_id
 * @property int|null $appointment_id
 * @property string $order_code
 * @property string $lab_name
 * @property string $work_type
 * @property string|null $tooth_code
 * @property string|null $shade
 * @property string|null $material
 * @property Carbon|null $sent_at
 * @property Carbon|null $due_date
 * @property Carbon|null $received_at
 * @property Carbon|null $delivered_at
 * @property string $cost
 * @property string $status
 * @property string|null $notes
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class LabOrder extends Model
{
    use BelongsToClinic;

    /** @var list<string> */
    protected $fillable = [
        'clinic_id',
        'patient_id',
        'dentist_id',
        'appointment_id',
        'order_code',
        'lab_name',
        'work_type',
        'tooth_code',
        'shade',
        'material',
        'sent_at',
        'due_date',
        'received_at',
        'delivered_at',
        'cost',
        'status',
        'notes',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'date',
            'due_date' => 'date',
            'received_at' => 'date',
            'delivered_at' => 'date',
            'cost' => 'decimal:2',
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
    public function dentist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public static function nextOrderCode(int $clinicId): string
    {
        $count = self::withoutGlobalScopes()
            ->where('clinic_id', $clinicId)
            ->count();

        return sprintf('LAB-%04d', $count + 1);
    }
}
