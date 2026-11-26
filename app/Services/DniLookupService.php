<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Clinic;
use App\Models\Patient;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DniLookupService
{
    /**
     * Search patient locally by DNI first, then query apiperu.dev if configured.
     *
     * @return array{
     *     ok: bool,
     *     found_local: bool,
     *     status: int,
     *     message?: string,
     *     patient?: array<string, mixed>,
     *     data?: array{
     *         dni: string,
     *         first_name: string,
     *         last_name: string,
     *         full_name: string,
     *         address: string|null
     *     }
     * }
     */
    public static function lookup(string $dni, ?Clinic $clinic): array
    {
        $existing = Patient::query()
            ->where('document_type', 'DNI')
            ->where('document_number', $dni)
            ->first();

        if ($existing instanceof Patient) {
            return [
                'ok' => true,
                'found_local' => true,
                'status' => 200,
                'patient' => [
                    'id' => $existing->id,
                    'history_number' => $existing->history_number,
                    'full_name' => $existing->full_name,
                    'first_name' => $existing->first_name,
                    'last_name' => $existing->last_name,
                    'phone' => $existing->phone,
                    'document_number' => $existing->document_number,
                ],
            ];
        }

        $token = $clinic?->apiperu_token;
        if (! is_string($token) || trim($token) === '') {
            return [
                'ok' => false,
                'found_local' => false,
                'status' => 422,
                'message' => 'No hay un token de apiperu.dev configurado. Ingresa el token en Ajustes > Integraciones.',
            ];
        }

        $response = self::queryApiPeru(trim($token), $dni);

        if ($response === null) {
            return [
                'ok' => false,
                'found_local' => false,
                'status' => 502,
                'message' => 'No fue posible conectarse con apiperu.dev. Verifica tu conexión a internet.',
            ];
        }

        if ($response->status() === 401 || $response->status() === 403) {
            return [
                'ok' => false,
                'found_local' => false,
                'status' => 422,
                'message' => 'El token de apiperu.dev es inválido o ha expirado.',
            ];
        }

        /** @var array<string, mixed>|null $json */
        $json = $response->json();

        if (! $response->successful() || ! is_array($json) || empty($json['success']) || ! is_array($json['data'] ?? null)) {
            $apiMessage = is_array($json) && is_string($json['message'] ?? null)
                ? $json['message']
                : 'No se encontraron datos en RENIEC para ese número de DNI.';

            return [
                'ok' => false,
                'found_local' => false,
                'status' => 404,
                'message' => $apiMessage,
            ];
        }

        /** @var array<string, mixed> $data */
        $data = $json['data'];
        $nombres = self::normalizeName((string) ($data['nombres'] ?? ''));
        $paterno = self::normalizeName((string) ($data['apellido_paterno'] ?? ''));
        $materno = self::normalizeName((string) ($data['apellido_materno'] ?? ''));
        $lastName = trim("{$paterno} {$materno}");
        $fullName = trim("{$nombres} {$lastName}") ?: self::normalizeName((string) ($data['nombre_completo'] ?? ''));

        $address = is_string($data['direccion_completa'] ?? null)
            ? trim((string) $data['direccion_completa'])
            : (is_string($data['direccion'] ?? null) ? trim((string) $data['direccion']) : null);

        return [
            'ok' => true,
            'found_local' => false,
            'status' => 200,
            'data' => [
                'dni' => $dni,
                'first_name' => $nombres,
                'last_name' => $lastName,
                'full_name' => $fullName,
                'address' => $address !== '' ? $address : null,
            ],
        ];
    }

    private static function queryApiPeru(string $token, string $dni): ?Response
    {
        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(8)
                ->post('https://apiperu.dev/api/dni', ['dni' => $dni]);

            if ($response->status() === 404 || $response->status() === 405) {
                $response = Http::withToken($token)
                    ->acceptJson()
                    ->timeout(8)
                    ->post('https://api.apiperu.dev/dni', ['dni' => $dni]);
            }

            return $response;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function normalizeName(string $value): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        if ($clean === '') {
            return '';
        }

        return Str::title(mb_strtolower($clean, 'UTF-8'));
    }
}
