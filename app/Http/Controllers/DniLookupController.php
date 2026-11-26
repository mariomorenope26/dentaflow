<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\User;
use App\Services\DniLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DniLookupController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->canManagePatients() && ! $user->canManageCash()) {
            abort(403, 'No tienes permisos para realizar consultas RENIEC.');
        }

        $validated = $request->validate([
            'dni' => ['required', 'digits:8'],
        ]);

        $clinic = $user->clinic_id !== null
            ? Clinic::query()->find($user->clinic_id)
            : Clinic::current();

        $result = DniLookupService::lookup((string) $validated['dni'], $clinic);

        if (! $result['ok']) {
            return response()->json([
                'message' => $result['message'] ?? 'No se pudo consultar el DNI.',
            ], $result['status']);
        }

        return response()->json($result);
    }
}
