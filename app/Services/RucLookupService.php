<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Clinic;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class RucLookupService
{
    /**
     * @return array{
     *     ok: bool,
     *     status: int,
     *     message?: string,
     *     data?: array{
     *         ruc: string,
     *         name: string,
     *         status: string|null,
     *         condition: string|null,
     *         address: string|null
     *     }
     * }
     */
    public static function lookup(string $ruc, ?Clinic $clinic): array
    {
        $token = $clinic?->apiperu_token;
        if (! is_string($token) || trim($token) === '') {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'No hay un token de apiperu.dev configurado. Ingresa el token en Ajustes > Integraciones.',
            ];
        }

        $response = self::queryApiPeru(trim($token), $ruc);

        if ($response === null) {
            return [
                'ok' => false,
                'status' => 502,
                'message' => 'No fue posible conectarse con apiperu.dev para consultar el RUC.',
            ];
        }

        if ($response->status() === 401 || $response->status() === 403) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'El token de apiperu.dev es inválido o ha expirado.',
            ];
        }

        /** @var array<string, mixed>|null $json */
        $json = $response->json();

        if (! $response->successful() || ! is_array($json) || empty($json['success']) || ! is_array($json['data'] ?? null)) {
            $apiMessage = is_array($json) && is_string($json['message'] ?? null)
                ? $json['message']
                : 'No se encontraron datos en SUNAT para ese RUC.';

            return [
                'ok' => false,
                'status' => 404,
                'message' => $apiMessage,
            ];
        }

        /** @var array<string, mixed> $data */
        $data = $json['data'];
        $name = trim((string) ($data['nombre_o_razon_social'] ?? $data['razon_social'] ?? ''));
        $address = is_string($data['direccion_completa'] ?? null)
            ? trim((string) $data['direccion_completa'])
            : (is_string($data['direccion'] ?? null) ? trim((string) $data['direccion']) : null);

        return [
            'ok' => true,
            'status' => 200,
            'data' => [
                'ruc' => $ruc,
                'name' => $name,
                'status' => is_string($data['estado'] ?? null) ? (string) $data['estado'] : null,
                'condition' => is_string($data['condicion'] ?? null) ? (string) $data['condicion'] : null,
                'address' => $address !== '' ? $address : null,
            ],
        ];
    }

    private static function queryApiPeru(string $token, string $ruc): ?Response
    {
        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(8)
                ->post('https://apiperu.dev/api/ruc', ['ruc' => $ruc]);

            if ($response->status() === 404 || $response->status() === 405) {
                $response = Http::withToken($token)
                    ->acceptJson()
                    ->timeout(8)
                    ->post('https://api.apiperu.dev/ruc', ['ruc' => $ruc]);
            }

            return $response;
        } catch (\Throwable) {
            return null;
        }
    }
}
