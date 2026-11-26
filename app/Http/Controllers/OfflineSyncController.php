<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\ClinicalEvolution;
use App\Models\Odontogram;
use App\Models\Patient;
use App\Models\TreatmentCatalog;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\OdontogramCatalog;
use App\Services\OdontogramSyncService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfflineSyncController extends Controller
{
    public function bootstrap(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $today = CarbonImmutable::today();

        $appointments = Appointment::query()
            ->with(['patient:id,first_name,last_name,history_number,phone', 'dentist:id,name'])
            ->whereBetween('starts_at', [$today->copy()->subDay()->startOfDay(), $today->copy()->addDays(4)->endOfDay()])
            ->orderBy('starts_at')
            ->get();

        $patients = Patient::query()
            ->with([
                'medicalHistory',
                'odontograms' => fn ($q) => $q->with('findings')->latest('recorded_at')->limit(2),
                'clinicalEvolutions' => fn ($q) => $q->with('author:id,name')->latest('attended_at')->limit(5),
            ])
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->limit(25)
            ->get();

        $catalog = TreatmentCatalog::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->get();

        $dentists = User::query()
            ->when($user->clinic_id !== null, fn ($q) => $q->where('clinic_id', $user->clinic_id))
            ->whereIn('role', [UserRole::AdminConsultorio, UserRole::Odontologo])
            ->where('is_active', true)
            ->get(['id', 'name', 'specialty']);

        return response()->json([
            'synced_at' => now()->toIso8601String(),
            'clinic' => $user->clinic?->only(['id', 'name', 'chairs', 'settings']),
            'appointments' => $appointments,
            'patients' => $patients,
            'treatment_catalog' => $catalog,
            'odontogram_conditions' => OdontogramCatalog::conditions(),
            'dentists' => $dentists,
        ]);
    }

    public function push(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'mutations' => ['required', 'array'],
            'mutations.*.id' => ['required', 'string', 'max:100'],
            'mutations.*.type' => ['required', 'string', 'in:odontogram.sync,clinical_evolution.create,appointment.status'],
            'mutations.*.force' => ['sometimes', 'boolean'],
            'mutations.*.payload' => ['required', 'array'],
        ]);

        $applied = [];
        $conflicts = [];

        foreach ($validated['mutations'] as $mutation) {
            $id = (string) $mutation['id'];
            $type = (string) $mutation['type'];
            $force = (bool) ($mutation['force'] ?? false);
            /** @var array<string, mixed> $payload */
            $payload = $mutation['payload'];

            if ($type === 'clinical_evolution.create') {
                if (! $user->canEditClinicalRecords()) {
                    continue;
                }

                $patient = Patient::query()->find((int) ($payload['patient_id'] ?? 0));
                if (! $patient instanceof Patient) {
                    continue;
                }

                $evolution = ClinicalEvolution::create([
                    'clinic_id' => $patient->clinic_id,
                    'patient_id' => $patient->id,
                    'user_id' => $user->id,
                    'attended_at' => $payload['attended_at'] ?? now(),
                    'procedure_summary' => (string) ($payload['procedure_summary'] ?? 'Atención offline'),
                    'clinical_notes' => (string) ($payload['clinical_notes'] ?? ''),
                    'prescription_notes' => $payload['prescription_notes'] ?? null,
                    'signed_at' => ! empty($payload['sign_now']) ? now() : null,
                    'signed_by' => ! empty($payload['sign_now']) ? $user->id : null,
                ]);

                AuditLogger::log('create', $evolution, "Sincronizó evolución clínica offline para {$patient->full_name}", ['offline_id' => $id], $request);
                $applied[] = $id;

                continue;
            }

            if ($type === 'odontogram.sync') {
                if (! $user->canEditClinicalRecords()) {
                    continue;
                }

                $odontogram = Odontogram::query()->with('findings')->find((int) ($payload['odontogram_id'] ?? 0));
                if (! $odontogram instanceof Odontogram) {
                    continue;
                }

                $expectedVersion = (int) ($payload['expected_version'] ?? $odontogram->version);
                if (! $force && $expectedVersion !== $odontogram->version) {
                    $conflicts[] = [
                        'id' => $id,
                        'type' => $type,
                        'label' => "Odontograma #{$odontogram->id} (Pieza / hallazgos modificados en otro dispositivo)",
                        'server_version' => $odontogram->version,
                        'server_updated_at' => $odontogram->updated_at?->toIso8601String(),
                        'server_findings' => $odontogram->findings->toArray(),
                        'local_findings' => $payload['findings'] ?? [],
                    ];

                    continue;
                }

                /** @var array<int, array{tooth_code: string, surface: string, condition_code: string, status: string, notes?: string|null}> $findings */
                $findings = is_array($payload['findings'] ?? null) ? $payload['findings'] : [];
                OdontogramSyncService::replaceFindings($odontogram, $findings, $user);

                AuditLogger::log('update', $odontogram, "Sincronizó odontograma #{$odontogram->id} desde modo offline", ['offline_id' => $id], $request);
                $applied[] = $id;

                continue;
            }

            if ($type === 'appointment.status') {
                if (! $user->canManageAppointments()) {
                    continue;
                }

                $appointment = Appointment::query()->find((int) ($payload['appointment_id'] ?? 0));
                if (! $appointment instanceof Appointment) {
                    continue;
                }

                $expectedVersion = (int) ($payload['expected_version'] ?? $appointment->version);
                $nextStatus = AppointmentStatus::tryFrom((string) ($payload['status'] ?? ''));

                if ($nextStatus === null) {
                    continue;
                }

                if (! $force && $expectedVersion !== $appointment->version) {
                    $conflicts[] = [
                        'id' => $id,
                        'type' => $type,
                        'label' => "Cita #{$appointment->id}: {$appointment->title}",
                        'server_version' => $appointment->version,
                        'server_status' => $appointment->status->value,
                        'local_status' => $nextStatus->value,
                    ];

                    continue;
                }

                $appointment->update([
                    'status' => $nextStatus,
                    'version' => $appointment->version + 1,
                ]);

                AuditLogger::log('update', $appointment, "Sincronizó estado de cita #{$appointment->id} a {$nextStatus->label()}", ['offline_id' => $id], $request);
                $applied[] = $id;
            }
        }

        return response()->json([
            'applied' => $applied,
            'conflicts' => $conflicts,
            'synced_at' => now()->toIso8601String(),
        ]);
    }
}
