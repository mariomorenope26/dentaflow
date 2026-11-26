<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ClinicSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $clinic = $user->clinic ?? Clinic::query()->firstOrFail();

        Gate::authorize('manage', $clinic);

        $members = User::query()
            ->where('clinic_id', $clinic->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'specialty', 'license_number', 'phone', 'commission_rate', 'is_active', 'created_at'])
            ->map(fn (User $member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->role->value,
                'role_label' => $member->role->label(),
                'specialty' => $member->specialty,
                'license_number' => $member->license_number,
                'phone' => $member->phone,
                'commission_rate' => (float) $member->commission_rate,
                'is_active' => $member->is_active,
                'is_protected_admin' => $member->role === UserRole::AdminConsultorio,
            ]);

        $token = $clinic->apiperu_token;
        $maskedToken = is_string($token) && $token !== ''
            ? '••••••••'.mb_substr($token, -6)
            : null;
        $aiKey = $clinic->ai_api_key;
        $maskedAiKey = is_string($aiKey) && $aiKey !== ''
            ? '••••••••'.mb_substr($aiKey, -6)
            : null;

        return Inertia::render('clinic/settings', [
            'clinic' => [
                'id' => $clinic->id,
                'name' => $clinic->name,
                'ruc' => $clinic->ruc,
                'slogan' => $clinic->slogan,
                'phone' => $clinic->phone,
                'email' => $clinic->email,
                'address' => $clinic->address,
                'logo_url' => $clinic->logoUrl(),
                'primary_color' => $clinic->primary_color,
                'currency_symbol' => $clinic->currency_symbol,
                'chairs' => $clinic->chairList(),
                'odontogram_notation' => $clinic->settings['odontogram_notation'] ?? 'FDI',
                'auto_lock_minutes' => $clinic->autoLockMinutes(),
                'has_apiperu_token' => is_string($token) && $token !== '',
                'apiperu_token_masked' => $maskedToken,
                'ai_provider' => $clinic->ai_provider ?: 'local',
                'has_ai_api_key' => is_string($aiKey) && $aiKey !== '',
                'ai_api_key_masked' => $maskedAiKey,
                'online_booking_enabled' => $clinic->online_booking_enabled,
                'print_settings' => $clinic->resolvedPrintSettings(),
            ],
            'members' => $members,
            'roles' => array_map(
                fn (UserRole $r): array => ['value' => $r->value, 'label' => $r->label()],
                UserRole::cases(),
            ),
            'systemStats' => [
                'patients' => Patient::withoutGlobalScopes()->where('clinic_id', $clinic->id)->count(),
                'appointments' => Appointment::withoutGlobalScopes()->where('clinic_id', $clinic->id)->count(),
                'payments' => Payment::withoutGlobalScopes()->where('clinic_id', $clinic->id)->count(),
                'db_driver' => (string) config('database.default'),
            ],
            'initialSection' => (string) $request->query('section', 'consultorio'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $clinic = $user->clinic ?? Clinic::query()->firstOrFail();

        Gate::authorize('manage', $clinic);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'ruc' => ['nullable', 'string', 'max:20'],
            'slogan' => ['nullable', 'string', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'chairs' => ['required', 'array', 'min:1'],
            'chairs.*' => ['required', 'string', 'max:60'],
            'odontogram_notation' => ['required', 'string', 'in:FDI,UNIVERSAL'],
            'auto_lock_minutes' => ['required', 'integer', 'min:1', 'max:240'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'apiperu_token' => ['nullable', 'string', 'max:255'],
            'clear_apiperu_token' => ['nullable', 'boolean'],
            'ai_provider' => ['nullable', 'string', 'in:local,gemini,openai'],
            'ai_api_key' => ['nullable', 'string', 'max:255'],
            'clear_ai_api_key' => ['nullable', 'boolean'],
            'online_booking_enabled' => ['nullable', 'boolean'],
            'print_settings' => ['nullable', 'array'],
            'print_settings.paper_size' => ['nullable', 'string', 'in:a4,letter'],
            'print_settings.thermal_width' => ['nullable', 'string', 'in:80mm,58mm'],
            'print_settings.show_logo' => ['nullable', 'boolean'],
            'print_settings.show_cop' => ['nullable', 'boolean'],
            'print_settings.header_text' => ['nullable', 'string', 'max:300'],
            'print_settings.footer_text' => ['nullable', 'string', 'max:300'],
        ]);

        $logoPath = $clinic->logo_path;
        if (! empty($validated['remove_logo']) && is_string($logoPath) && $logoPath !== '') {
            Storage::disk('public')->delete($logoPath);
            $logoPath = null;
        }

        if ($request->hasFile('logo')) {
            if (is_string($logoPath) && $logoPath !== '') {
                Storage::disk('public')->delete($logoPath);
            }
            /** @var UploadedFile $file */
            $file = $request->file('logo');
            $logoPath = (string) $file->store('clinic-logos', 'public');
        }

        $apiperuToken = $clinic->apiperu_token;
        if (! empty($validated['clear_apiperu_token'])) {
            $apiperuToken = null;
        } elseif (! empty($validated['apiperu_token'])) {
            $apiperuToken = trim((string) $validated['apiperu_token']);
        }

        $aiApiKey = $clinic->ai_api_key;
        if (! empty($validated['clear_ai_api_key'])) {
            $aiApiKey = null;
        } elseif (! empty($validated['ai_api_key'])) {
            $aiApiKey = trim((string) $validated['ai_api_key']);
        }

        $aiProvider = $validated['ai_provider'] ?? $clinic->ai_provider ?? 'local';
        $onlineBooking = array_key_exists('online_booking_enabled', $validated)
            ? (bool) $validated['online_booking_enabled']
            : $clinic->online_booking_enabled;

        $currentPrint = $clinic->resolvedPrintSettings();
        /** @var array<string, mixed> $incomingPrint */
        $incomingPrint = is_array($validated['print_settings'] ?? null) ? $validated['print_settings'] : [];

        $mergedPrint = [
            'paper_size' => $incomingPrint['paper_size'] ?? $currentPrint['paper_size'],
            'thermal_width' => $incomingPrint['thermal_width'] ?? $currentPrint['thermal_width'],
            'show_logo' => array_key_exists('show_logo', $incomingPrint) ? (bool) $incomingPrint['show_logo'] : $currentPrint['show_logo'],
            'show_cop' => array_key_exists('show_cop', $incomingPrint) ? (bool) $incomingPrint['show_cop'] : $currentPrint['show_cop'],
            'header_text' => (string) ($incomingPrint['header_text'] ?? $currentPrint['header_text']),
            'footer_text' => (string) ($incomingPrint['footer_text'] ?? $currentPrint['footer_text']),
        ];

        $clinic->update([
            'name' => $validated['name'],
            'ruc' => $validated['ruc'] ?? null,
            'slogan' => $validated['slogan'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'logo_path' => $logoPath,
            'primary_color' => $validated['primary_color'],
            'currency_symbol' => $validated['currency_symbol'],
            'chairs' => array_values($validated['chairs']),
            'apiperu_token' => $apiperuToken,
            'ai_provider' => $aiProvider,
            'ai_api_key' => $aiApiKey,
            'online_booking_enabled' => $onlineBooking,
            'print_settings' => $mergedPrint,
            'settings' => [
                ...($clinic->settings ?? []),
                'odontogram_notation' => $validated['odontogram_notation'],
                'auto_lock_minutes' => (int) $validated['auto_lock_minutes'],
            ],
        ]);

        AuditLogger::log('update', $clinic, "Actualizó configuración del consultorio {$clinic->name}", [], $request);

        return back()->with('success', 'Configuración del consultorio guardada.');
    }

    public function storeUser(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $clinic = $user->clinic ?? Clinic::query()->firstOrFail();

        Gate::authorize('manage', $clinic);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', Rule::in(array_column(UserRole::cases(), 'value'))],
            'specialty' => ['nullable', 'string', 'max:120'],
            'license_number' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:40'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'password' => ['required', 'string', 'min:8', Password::defaults()],
        ]);

        $member = User::create([
            'clinic_id' => $clinic->id,
            'name' => $validated['name'],
            'email' => mb_strtolower(trim((string) $validated['email'])),
            'role' => $validated['role'],
            'specialty' => $validated['specialty'] ?? null,
            'license_number' => $validated['license_number'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'commission_rate' => $validated['commission_rate'] ?? 40.00,
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        AuditLogger::log('create', $member, "Agregó usuario {$member->name} con rol {$member->role->label()}", [], $request, $clinic->id);

        return back()->with('success', "Usuario {$member->name} agregado al equipo.");
    }

    public function updateUser(Request $request, User $member): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $clinic = $user->clinic ?? Clinic::query()->firstOrFail();

        Gate::authorize('manage', $clinic);

        if ($member->clinic_id !== $clinic->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($member->id)],
            'role' => ['sometimes', 'string', Rule::in(array_column(UserRole::cases(), 'value'))],
            'specialty' => ['nullable', 'string', 'max:120'],
            'license_number' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:40'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', Password::defaults()],
        ]);

        $requestedRole = isset($validated['role']) ? (string) $validated['role'] : null;

        if ($member->role === UserRole::AdminConsultorio) {
            if ($requestedRole !== null && $requestedRole !== UserRole::AdminConsultorio->value) {
                return back()
                    ->withErrors(['role' => 'El rol de Administrador del consultorio está protegido y no se puede cambiar.'])
                    ->with('error', 'El rol de Administrador del consultorio está protegido y no se puede cambiar.');
            }

            if (array_key_exists('is_active', $validated) && ! $validated['is_active']) {
                return back()->with('error', 'No se puede desactivar una cuenta con rol Administrador del consultorio.');
            }

            unset($validated['role']);
        } elseif ($member->id === $user->id && $requestedRole !== null && $requestedRole !== $member->role->value) {
            return back()
                ->withErrors(['role' => 'No puedes modificar tu propio rol.'])
                ->with('error', 'No puedes modificar tu propio rol.');
        }

        if ($member->id === $user->id && isset($validated['is_active']) && ! $validated['is_active']) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        if (isset($validated['email'])) {
            $validated['email'] = mb_strtolower(trim((string) $validated['email']));
        }

        $previousRole = $member->role;
        $previousActive = $member->is_active;
        $passwordChanged = ! empty($validated['password']);

        if ($passwordChanged) {
            $validated['password'] = Hash::make((string) $validated['password']);
        } else {
            unset($validated['password']);
        }

        $member->update($validated);
        $member->refresh();

        $shouldRevokeSessions = $passwordChanged
            || $previousRole !== $member->role
            || ($previousActive && ! $member->is_active);

        if ($shouldRevokeSessions && $member->id !== $user->id && config('session.driver') === 'database') {
            DB::table((string) config('session.table', 'sessions'))
                ->where('user_id', $member->id)
                ->delete();
        }

        AuditLogger::log('update', $member, "Actualizó datos/permisos de {$member->name}", [], $request, $clinic->id);

        return back()->with('success', 'Usuario actualizado.');
    }

    public function destroyUser(Request $request, User $member): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $clinic = $user->clinic ?? Clinic::query()->firstOrFail();

        Gate::authorize('manage', $clinic);

        if ($member->clinic_id !== $clinic->id) {
            abort(403);
        }

        if ($member->id === $user->id) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta activa.');
        }

        if ($member->role === UserRole::AdminConsultorio) {
            return back()->with('error', 'La cuenta Administrador del consultorio está protegida y no puede eliminarse.');
        }

        $name = $member->name;

        if (config('session.driver') === 'database') {
            DB::table((string) config('session.table', 'sessions'))
                ->where('user_id', $member->id)
                ->delete();
        }

        $member->delete();

        AuditLogger::log('delete', User::class, "Eliminó usuario del equipo: {$name}", [], $request, $clinic->id);

        return back()->with('success', "Usuario {$name} eliminado del equipo.");
    }

    public function auditIndex(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $clinic = $user->clinic ?? Clinic::query()->firstOrFail();

        Gate::authorize('viewAudit', $clinic);

        $logs = AuditLog::query()
            ->with('user:id,name,role')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('clinic/audit', [
            'logs' => $logs,
        ]);
    }
}
