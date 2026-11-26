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
 * @property int|null $treatment_plan_id
 * @property int|null $received_by
 * @property string $receipt_number
 * @property string|null $sunat_series
 * @property string|null $sunat_correlative
 * @property string $document_type
 * @property string|null $customer_doc_type
 * @property string|null $customer_doc_number
 * @property string|null $customer_name
 * @property string|null $customer_address
 * @property string $payment_method
 * @property string $amount
 * @property string $tax_subtotal
 * @property string $tax_igv
 * @property Carbon $paid_at
 * @property string $concept
 * @property string|null $notes
 * @property string $status
 * @property string $sunat_status
 * @property string|null $sunat_hash
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Payment extends Model
{
    use BelongsToClinic;

    /** @var list<string> */
    protected $fillable = [
        'clinic_id',
        'patient_id',
        'treatment_plan_id',
        'received_by',
        'receipt_number',
        'sunat_series',
        'sunat_correlative',
        'document_type',
        'customer_doc_type',
        'customer_doc_number',
        'customer_name',
        'customer_address',
        'payment_method',
        'amount',
        'tax_subtotal',
        'tax_igv',
        'paid_at',
        'concept',
        'notes',
        'status',
        'sunat_status',
        'sunat_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'tax_subtotal' => 'decimal:2',
            'tax_igv' => 'decimal:2',
            'paid_at' => 'datetime',
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
     * @return BelongsTo<TreatmentPlan, $this>
     */
    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public static function nextReceiptNumber(int $clinicId, string $documentType = 'recibo'): string
    {
        $prefix = match ($documentType) {
            'boleta' => 'BOL',
            'factura' => 'FAC',
            default => 'REC',
        };

        $count = self::withoutGlobalScopes()
            ->where('clinic_id', $clinicId)
            ->where('document_type', $documentType)
            ->count();

        return sprintf('%s-%04d', $prefix, $count + 1);
    }
}
