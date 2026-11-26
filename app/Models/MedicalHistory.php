<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToClinic;
use Database\Factories\MedicalHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $clinic_id
 * @property int $patient_id
 * @property string|null $chief_complaint
 * @property string|null $current_illness
 * @property string|null $medical_history_notes
 * @property string|null $dental_history_notes
 * @property string|null $allergies
 * @property string|null $current_medications
 * @property array<int, string>|null $chronic_conditions
 * @property array<int, string>|null $habits
 * @property bool $is_pregnant
 * @property string|null $asa_classification
 * @property string|null $family_history
 * @property array<string, mixed>|null $review_of_systems
 * @property array<int, array<string, string>>|null $medical_alerts
 * @property array<string, string>|null $vital_signs
 * @property string|null $extraoral_exam
 * @property string|null $intraoral_exam
 * @property string|null $tmj_exam
 * @property string|null $soft_tissues_exam
 * @property string|null $periodontal_exam
 * @property string|null $occlusion_exam
 * @property array<string, mixed>|null $stomatognathic_exam
 * @property string|null $presumptive_diagnosis
 * @property string|null $definitive_diagnosis
 * @property string|null $cie10_code
 * @property int|null $updated_by
 * @property int $version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class MedicalHistory extends Model
{
    use BelongsToClinic;

    /** @use HasFactory<MedicalHistoryFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'clinic_id',
        'patient_id',
        'chief_complaint',
        'current_illness',
        'medical_history_notes',
        'dental_history_notes',
        'family_history',
        'allergies',
        'current_medications',
        'chronic_conditions',
        'review_of_systems',
        'habits',
        'is_pregnant',
        'asa_classification',
        'medical_alerts',
        'vital_signs',
        'extraoral_exam',
        'intraoral_exam',
        'tmj_exam',
        'soft_tissues_exam',
        'periodontal_exam',
        'occlusion_exam',
        'stomatognathic_exam',
        'presumptive_diagnosis',
        'definitive_diagnosis',
        'cie10_code',
        'updated_by',
        'version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'chronic_conditions' => 'array',
            'review_of_systems' => 'array',
            'habits' => 'array',
            'is_pregnant' => 'boolean',
            'medical_alerts' => 'array',
            'vital_signs' => 'array',
            'stomatognathic_exam' => 'array',
            'version' => 'integer',
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
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
