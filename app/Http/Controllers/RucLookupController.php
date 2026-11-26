<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\User;
use App\Services\RucLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RucLookupController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->canManageCash() && ! $user->canManagePatients()) {
            abort(403, 'No tienes permisos para realizar consultas RUC.');
        }

        $validated = $request->validate([
            'ruc' => ['required', 'digits:11'],
        ]);

        $clinic = $user->clinic_id !== null
            ? Clinic::query()->find($user->clinic_id)
            : Clinic::current();

        $result = RucLookupService::lookup((string) $validated['ruc'], $clinic);

        if (! $result['ok']) {
            return response()->json([
                'message' => $result['message'] ?? 'No se pudo consultar el RUC.',
            ], $result['status']);
        }

        return response()->json($result);
    }
}
