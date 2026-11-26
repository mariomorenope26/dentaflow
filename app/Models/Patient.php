<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToClinic;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $clinic_id
 * @property string $history_number
 * @property string|null $fhir_uuid
 * @property string $first_name
 * @property string $last_name
 * @property string $document_type
 * @property string|null $document_number
 * @property Carbon|null $birth_date
 * @property string|null $gender
 * @property string|null $blood_type
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address
 * @property string|null $occupation
 * @property string|null $insurance_provider
 * @property string|null $insurance_number
 * @property string|null $guardian_name
 * @property string|null $guardian_phone
 * @property string|null $guardian_relationship
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property array<int, string>|null $tags
 * @property string|null $notes
 * @property bool $is_active
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $full_name
 * @property-read int|null $age
 */
class Patient extends Model
{
    use BelongsToClinic;

    /** @use HasFactory<PatientFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'clinic_id',
        'history_number',
        'fhir_uuid',
        'first_name',
        'last_name',
        'document_type',
        'document_number',
        'birth_date',
        'gender',
        'blood_type',
        'phone',
        'email',
        'address',
        'occupation',
        'insurance_provider',
        'insurance_number',
        'guardian_name',
        'guardian_phone',
        'guardian_relationship',
        'emergency_contact_name',
        'emergency_contact_phone',
        'tags',
        'notes',
        'is_active',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (Patient $patient): void {
            if (! $patient->fhir_uuid) {
                $patient->fhir_uuid = (string) Str::uuid();
            }
        });
    }

    /** @var list<string> */
    protected $appends = ['full_name', 'age'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'tags' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birth_date?->age;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasOne<MedicalHistory, $this>
     */
    public function medicalHistory(): HasOne
    {
        return $this->hasOne(MedicalHistory::class);
    }

    /**
     * @return HasMany<ClinicalEvolution, $this>
     */
    public function clinicalEvolutions(): HasMany
    {
        return $this->hasMany(ClinicalEvolution::class)->orderByDesc('attended_at')->orderByDesc('id');
    }

    /**
     * @return HasMany<Odontogram, $this>
     */
    public function odontograms(): HasMany
    {
        return $this->hasMany(Odontogram::class)->orderByDesc('recorded_at')->orderByDesc('id');
    }

    /**
     * @return HasMany<TreatmentPlan, $this>
     */
    public function treatmentPlans(): HasMany
    {
        return $this->hasMany(TreatmentPlan::class)->orderByDesc('created_at');
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class)->orderByDesc('starts_at');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderByDesc('paid_at')->orderByDesc('id');
    }

    /**
     * @return HasMany<ClinicalDocument, $this>
     */
    public function clinicalDocuments(): HasMany
    {
        return $this->hasMany(ClinicalDocument::class)->orderByDesc('issued_at')->orderByDesc('id');
    }

    /**
     * @return HasMany<PatientImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(PatientImage::class)->orderByDesc('taken_at')->orderByDesc('id');
    }

    /**
     * @return HasMany<Periodontogram, $this>
     */
    public function periodontograms(): HasMany
    {
        return $this->hasMany(Periodontogram::class)->orderByDesc('recorded_at')->orderByDesc('id');
    }

    /**
     * @return HasMany<OrthodonticControl, $this>
     */
    public function orthodonticControls(): HasMany
    {
        return $this->hasMany(OrthodonticControl::class)->orderByDesc('control_date')->orderByDesc('id');
    }

    /**
     * @return HasMany<LabOrder, $this>
     */
    public function labOrders(): HasMany
    {
        return $this->hasMany(LabOrder::class)->orderByDesc('id');
    }

    /**
     * @return HasMany<PaymentInstallment, $this>
     */
    public function paymentInstallments(): HasMany
    {
        return $this->hasMany(PaymentInstallment::class)->orderBy('installment_number');
    }

    public static function nextHistoryNumber(int $clinicId): string
    {
        $latest = self::withoutGlobalScopes()
            ->where('clinic_id', $clinicId)
            ->orderByDesc('id')
            ->value('history_number');

        $nextNum = 1;
        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches) === 1) {
            $nextNum = ((int) $matches[1]) + 1;
        }

        return sprintf('HC-%04d', $nextNum);
    }
}
