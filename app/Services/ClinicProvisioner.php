<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Clinic;
use App\Models\TreatmentCatalog;
use Illuminate\Support\Str;

class ClinicProvisioner
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function createClinicWithDefaults(array $attributes): Clinic
    {
        $name = (string) ($attributes['name'] ?? 'Consultorio Odontológico');
        $baseSlug = Str::slug($name) ?: 'consultorio';
        $slug = $baseSlug;
        $counter = 1;

        while (Clinic::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        /** @var Clinic $clinic */
        $clinic = Clinic::query()->create([
            'name' => $name,
            'slug' => $slug,
            'ruc' => $attributes['ruc'] ?? null,
            'slogan' => $attributes['slogan'] ?? 'Odontología Integral y Especialidades',
            'phone' => $attributes['phone'] ?? null,
            'email' => $attributes['email'] ?? null,
            'address' => $attributes['address'] ?? null,
            'primary_color' => $attributes['primary_color'] ?? '#0E8FA3',
            'currency_symbol' => $attributes['currency_symbol'] ?? 'S/',
            'chairs' => $attributes['chairs'] ?? ['Sillón 1', 'Sillón 2'],
            'apiperu_token' => $attributes['apiperu_token'] ?? null,
            'settings' => $attributes['settings'] ?? [
                'odontogram_notation' => 'FDI',
                'auto_lock_minutes' => 15,
            ],
            'is_active' => true,
        ]);

        self::seedDefaultCatalog($clinic);

        return $clinic;
    }

    public static function seedDefaultCatalog(Clinic $clinic): void
    {
        foreach (OdontogramCatalog::defaultTreatmentCatalog() as $item) {
            TreatmentCatalog::withoutGlobalScopes()->firstOrCreate(
                [
                    'clinic_id' => $clinic->id,
                    'code' => $item['code'],
                ],
                [
                    'name' => $item['name'],
                    'category' => $item['category'],
                    'default_price' => $item['default_price'],
                    'duration_minutes' => $item['duration_minutes'],
                    'odontogram_condition' => $item['odontogram_condition'],
                    'is_active' => true,
                ],
            );
        }
    }
}
