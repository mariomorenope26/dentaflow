<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Appointment::class);

        /** @var User $user */
        $user = $request->user();

        $view = (string) $request->query('view', 'week');
        $dateParam = (string) $request->query('date', CarbonImmutable::today()->toDateString());
        $dentistFilter = $request->query('dentist_id');
        $chairFilter = trim((string) $request->query('chair', ''));

        $anchor = CarbonImmutable::parse($dateParam);

        [$rangeStart, $rangeEnd] = match ($view) {
            'day' => [$anchor->startOfDay(), $anchor->endOfDay()],
            'month' => [$anchor->startOfMonth()->startOfWeek(), $anchor->endOfMonth()->endOfWeek()],
            default => [$anchor->startOfWeek(), $anchor->endOfWeek()],
        };

        $query = Appointment::query()
            ->with([
                'patient:id,first_name,last_name,history_number,phone',
                'patient.medicalHistory:id,patient_id,medical_alerts,allergies',
                'dentist:id,name,specialty',
                'labOrders:id,patient_id,appointment_id,order_code,work_type,status,due_date',
            ])
            ->where('is_waitlist', false)
            ->whereBetween('starts_at', [$rangeStart, $rangeEnd]);

        if (! empty($dentistFilter)) {
            $query->where('dentist_id', (int) $dentistFilter);
        }

        if ($chairFilter !== '') {
            $query->where('chair', $chairFilter);
        }

        $appointments = $query->orderBy('starts_at')->get();

        $waitlist = Appointment::query()
            ->with(['patient:id,first_name,last_name,history_number,phone', 'dentist:id,name'])
            ->where('is_waitlist', true)
            ->whereNotIn('status', [AppointmentStatus::Atendida, AppointmentStatus::Cancelada])
            ->orderBy('starts_at')
            ->get();

        $clinicId = $user->clinic_id;

        $dentists = User::query()
            ->when($clinicId !== null, fn ($q) => $q->where('clinic_id', $clinicId))
            ->whereIn('role', [UserRole::AdminConsultorio, UserRole::Odontologo])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'specialty']);

        $patients = Patient::query()
            ->where('is_active', true)
            ->orderBy('last_name')
            ->limit(100)
            ->get(['id', 'first_name', 'last_name', 'history_number', 'phone']);

        return Inertia::render('agenda/index', [
            'appointments' => $appointments,
            'waitlist' => $waitlist,
            'dentists' => $dentists,
            'patients' => $patients,
            'chairs' => $user->clinic?->chairList() ?? ['Sillón 1', 'Sillón 2'],
            'filters' => [
                'view' => in_array($view, ['day', 'week', 'month'], true) ? $view : 'week',
                'date' => $anchor->toDateString(),
                'dentist_id' => $dentistFilter ? (string) $dentistFilter : '',
                'chair' => $chairFilter,
            ],
            'range' => [
                'start' => $rangeStart->toDateString(),
                'end' => $rangeEnd->toDateString(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Appointment::class);

        /** @var User $user */
        $user = $request->user();
        $clinicId = $user->clinic_id ?? (int) Clinic::query()->value('id');

        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'dentist_id' => ['required', 'integer', 'exists:users,id'],
            'chair' => ['required', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:160'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'status' => ['nullable', 'string', 'in:reservada,confirmada,en_sala,atendida,cancelada,no_asistio'],
            'is_waitlist' => ['boolean'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $patient = Patient::query()->findOrFail((int) $validated['patient_id']);
        $dentist = User::query()->findOrFail((int) $validated['dentist_id']);

        if ($patient->clinic_id !== $clinicId || ($dentist->clinic_id !== null && $dentist->clinic_id !== $clinicId)) {
            abort(403);
        }

        $isWaitlist = (bool) ($validated['is_waitlist'] ?? false);

        if (! $isWaitlist) {
            $overlap = Appointment::query()
                ->where('clinic_id', $clinicId)
                ->where('is_waitlist', false)
                ->whereNotIn('status', [AppointmentStatus::Cancelada, AppointmentStatus::NoAsistio])
                ->where(function ($q) use ($validated): void {
                    $q->where('dentist_id', $validated['dentist_id'])
                        ->orWhere('chair', $validated['chair']);
                })
                ->where('starts_at', '<', $validated['ends_at'])
                ->where('ends_at', '>', $validated['starts_at'])
                ->exists();

            if ($overlap) {
                return back()->with('error', 'Existe un cruce de horario con el profesional o sillón seleccionado.');
            }
        }

        $appointment = Appointment::create([
            ...$validated,
            'clinic_id' => $clinicId,
            'status' => $validated['status'] ?? AppointmentStatus::Reservada->value,
            'is_waitlist' => $isWaitlist,
            'created_by' => $user->id,
        ]);

        AuditLogger::log('create', $appointment, "Programó cita para {$patient->full_name} ({$appointment->starts_at->format('d/m H:i')})", [], $request);

        return back()->with('success', $isWaitlist ? 'Paciente agregado a lista de espera.' : 'Cita programada correctamente.');
    }

    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        Gate::authorize('update', $appointment);

        $validated = $request->validate([
            'dentist_id' => ['sometimes', 'integer', 'exists:users,id'],
            'chair' => ['sometimes', 'string', 'max:60'],
            'title' => ['sometimes', 'string', 'max:160'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date'],
            'status' => ['sometimes', 'string', 'in:reservada,confirmada,en_sala,atendida,cancelada,no_asistio'],
            'is_waitlist' => ['sometimes', 'boolean'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $startsAt = isset($validated['starts_at']) ? CarbonImmutable::parse((string) $validated['starts_at']) : $appointment->starts_at;
        $endsAt = isset($validated['ends_at']) ? CarbonImmutable::parse((string) $validated['ends_at']) : $appointment->ends_at;

        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            return back()->with('error', 'La hora de fin debe ser posterior a la hora de inicio.');
        }

        $appointment->update([
            ...$validated,
            'version' => $appointment->version + 1,
        ]);

        AuditLogger::log('update', $appointment, "Actualizó cita #{$appointment->id} ({$appointment->status->label()})", [], $request);

        return back()->with('success', 'Cita actualizada.');
    }

    public function destroy(Request $request, Appointment $appointment): RedirectResponse
    {
        Gate::authorize('delete', $appointment);

        $summary = "Eliminó cita #{$appointment->id} ({$appointment->title})";
        $clinicId = $appointment->clinic_id;
        $appointment->delete();

        AuditLogger::log('delete', Appointment::class, $summary, [], $request, $clinicId);

        return back()->with('success', 'Cita eliminada de la agenda.');
    }

    public function sendWhatsappReminder(Request $request, Appointment $appointment): JsonResponse
    {
        Gate::authorize('update', $appointment);

        $appointment->loadMissing(['patient:id,first_name,last_name,phone', 'dentist:id,name', 'clinic:id,name,address']);

        if (! $appointment->confirmation_token) {
            $appointment->confirmation_token = Str::random(32);
        }

        $now = Carbon::now();
        $appointment->reminder_sent_at = $now;
        $appointment->save();

        $confirmUrl = url("/confirmar-cita/{$appointment->confirmation_token}");
        $patientName = $appointment->patient->first_name ?? 'paciente';
        $clinicName = $appointment->clinic->name ?? 'Consultorio Odontológico';
        $dateLabel = $appointment->starts_at->format('d/m/Y');
        $timeLabel = $appointment->starts_at->format('H:i');

        $message = "Hola {$patientName}, le saluda *{$clinicName}*. Le recordamos su cita odontológica el *{$dateLabel}* a las *{$timeLabel}* ({$appointment->title}) con {$appointment->dentist?->name}.\n\nConfirme su asistencia con 1 clic aquí:\n{$confirmUrl}";

        $digits = preg_replace('/\D/', '', (string) ($appointment->patient->phone ?? '')) ?? '';
        if ($digits !== '' && strlen($digits) === 9 && str_starts_with($digits, '9')) {
            $digits = '51'.$digits;
        }

        $whatsappUrl = $digits !== ''
            ? 'https://wa.me/'.$digits.'?text='.rawurlencode($message)
            : 'https://wa.me/?text='.rawurlencode($message);

        AuditLogger::log('update', $appointment, "Generó recordatorio WhatsApp 1-clic para cita #{$appointment->id}", [], $request);

        return response()->json([
            'ok' => true,
            'confirm_url' => $confirmUrl,
            'whatsapp_url' => $whatsappUrl,
            'reminder_sent_at' => $now->toIso8601String(),
        ]);
    }
}
