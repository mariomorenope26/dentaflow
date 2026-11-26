<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ClinicFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $ruc
 * @property string|null $slogan
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address
 * @property string|null $logo_path
 * @property string $primary_color
 * @property string $currency_symbol
 * @property array<int, string>|null $chairs
 * @property array<string, mixed>|null $settings
 * @property string|null $apiperu_token
 * @property string $ai_provider
 * @property string|null $ai_api_key
 * @property bool $online_booking_enabled
 * @property array<string, mixed>|null $print_settings
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Clinic extends Model
{
    /** @use HasFactory<ClinicFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'slug',
        'ruc',
        'slogan',
        'phone',
        'email',
        'address',
        'logo_path',
        'primary_color',
        'currency_symbol',
        'chairs',
        'settings',
        'apiperu_token',
        'ai_provider',
        'ai_api_key',
        'online_booking_enabled',
        'print_settings',
        'is_active',
    ];

    /** @var list<string> */
    protected $hidden = ['apiperu_token', 'ai_api_key'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'chairs' => 'array',
            'settings' => 'array',
            'apiperu_token' => 'encrypted',
            'ai_api_key' => 'encrypted',
            'online_booking_enabled' => 'boolean',
            'print_settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Patient, $this>
     */
    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    /**
     * @return HasMany<TreatmentCatalog, $this>
     */
    public function treatmentCatalogs(): HasMany
    {
        return $this->hasMany(TreatmentCatalog::class);
    }

    /**
     * @return array<int, string>
     */
    public function chairList(): array
    {
        $chairs = $this->chairs;
        if (is_array($chairs) && count($chairs) > 0) {
            return array_values($chairs);
        }

        return ['Sillón 1', 'Sillón 2'];
    }

    public function autoLockMinutes(): int
    {
        $minutes = $this->settings['auto_lock_minutes'] ?? 15;

        return max(1, (int) $minutes);
    }

    public function logoUrl(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    public function logoDataUri(): ?string
    {
        if (! $this->logo_path || ! Storage::disk('public')->exists($this->logo_path)) {
            return null;
        }

        $bytes = Storage::disk('public')->get($this->logo_path);
        if (! is_string($bytes) || $bytes === '') {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($this->logo_path) ?: 'image/png';

        return "data:{$mime};base64,".base64_encode($bytes);
    }

    /**
     * @return array{
     *     paper_size: string,
     *     thermal_width: string,
     *     show_logo: bool,
     *     show_cop: bool,
     *     header_text: string,
     *     footer_text: string
     * }
     */
    public function resolvedPrintSettings(): array
    {
        $raw = $this->print_settings ?? [];
        $paperSize = $raw['paper_size'] ?? 'a4';
        $thermalWidth = $raw['thermal_width'] ?? '80mm';

        return [
            'paper_size' => in_array($paperSize, ['a4', 'letter'], true)
                ? (string) $paperSize
                : 'a4',
            'thermal_width' => in_array($thermalWidth, ['80mm', '58mm'], true)
                ? (string) $thermalWidth
                : '80mm',
            'show_logo' => (bool) ($raw['show_logo'] ?? true),
            'show_cop' => (bool) ($raw['show_cop'] ?? true),
            'header_text' => (string) ($raw['header_text'] ?? ''),
            'footer_text' => (string) ($raw['footer_text'] ?? 'Gracias por confiar su salud bucal en nosotros.'),
        ];
    }

    public static function current(): ?self
    {
        return self::query()->where('is_active', true)->orderBy('id')->first();
    }
}
