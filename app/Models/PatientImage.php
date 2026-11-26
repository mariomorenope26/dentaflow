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
 * @property int|null $uploaded_by
 * @property string $category
 * @property string $title
 * @property string|null $tooth_code
 * @property string $file_path
 * @property Carbon $taken_at
 * @property string|null $notes
 * @property array<string, mixed>|null $annotations
 * @property array<string, mixed>|null $ai_analysis
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PatientImage extends Model
{
    use BelongsToClinic;

    /** @var list<string> */
    protected $fillable = [
        'clinic_id',
        'patient_id',
        'uploaded_by',
        'category',
        'title',
        'tooth_code',
        'file_path',
        'taken_at',
        'notes',
        'annotations',
        'ai_analysis',
    ];

    /** @var list<string> */
    protected $appends = ['url'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'taken_at' => 'date',
            'annotations' => 'array',
            'ai_analysis' => 'array',
        ];
    }

    public function getUrlAttribute(): string
    {
        return route('patients.images.show', [
            'patient' => $this->patient_id,
            'image' => $this->id,
        ]);
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
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
