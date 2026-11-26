<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\User;
use App\Services\ContralorAuditService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContralorController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->canEditClinicalRecords() && ! $user->canManageClinic() && ! $user->canViewAuditLogs()) {
            abort(403, 'No tienes permisos para acceder al Contralor IA.');
        }

        $clinic = $user->clinic ?? Clinic::query()->firstOrFail();

        return Inertia::render('contralor/index', [
            'audit' => ContralorAuditService::auditClinic($clinic),
            'clinicName' => $clinic->name,
        ]);
    }
}
