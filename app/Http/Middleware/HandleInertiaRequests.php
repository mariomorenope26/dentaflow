<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $clinic = null;
        $userPayload = null;

        if ($user instanceof User) {
            $user->loadMissing('clinic');
            $clinicModel = $user->clinic;

            $userPayload = [
                ...$user->toArray(),
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'can' => [
                    'edit_clinical' => $user->canEditClinicalRecords(),
                    'manage_patients' => $user->canManagePatients(),
                    'manage_appointments' => $user->canManageAppointments(),
                    'manage_cash' => $user->canManageCash(),
                    'manage_clinic' => $user->canManageClinic(),
                    'view_audit' => $user->canViewAuditLogs(),
                ],
            ];

            if ($clinicModel !== null) {
                $clinic = [
                    'id' => $clinicModel->id,
                    'name' => $clinicModel->name,
                    'slug' => $clinicModel->slug,
                    'ruc' => $clinicModel->ruc,
                    'slogan' => $clinicModel->slogan,
                    'phone' => $clinicModel->phone,
                    'email' => $clinicModel->email,
                    'address' => $clinicModel->address,
                    'logo_url' => $clinicModel->logoUrl(),
                    'primary_color' => $clinicModel->primary_color,
                    'currency_symbol' => $clinicModel->currency_symbol,
                    'chairs' => $clinicModel->chairList(),
                    'settings' => $clinicModel->settings ?? [],
                    'print_settings' => $clinicModel->resolvedPrintSettings(),
                    'has_apiperu_token' => ! empty($clinicModel->apiperu_token),
                    'online_booking_enabled' => $clinicModel->online_booking_enabled,
                    'auto_lock_minutes' => $clinicModel->autoLockMinutes(),
                ];
            }
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'onlineBookingUrl' => fn (): ?string => Clinic::current()?->online_booking_enabled
                ? route('public.booking.show')
                : null,
            'auth' => [
                'user' => $userPayload,
                'clinic' => $clinic,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
