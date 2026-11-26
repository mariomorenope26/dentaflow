<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\TreatmentCatalog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PublicBookingController extends Controller
{
    /**
     * Solo el personal odontológico/médico activo puede recibir reservas desde el portal web público.
     *
     * @return Builder<User>
     */
    private function bookableDentistsQuery(int $clinicId): Builder
    {
        return User::query()
            ->where('clinic_id', $clinicId)
            ->where('is_active', true)
            ->where('role', UserRole::Odontologo);
    }

    public function show(): Response
    {
        $clinic = Clinic::current();
        if ($clinic === null || ! $clinic->online_booking_enabled) {
            abort(404);
        }

        $dentists = $this->bookableDentistsQuery($clinic->id)
            ->orderBy('name')
            ->get(['id', 'name', 'specialty', 'license_number']);

        $services = TreatmentCatalog::withoutGlobalScopes()
            ->where('clinic_id', $clinic->id)
            ->where('is_active', true)
            ->orderBy('category')
            ->limit(12)
            ->get(['id', 'name', 'category', 'duration_minutes', 'default_price']);

        return Inertia::render('public/booking', [
            'clinic' => [
                'name' => $clinic->name,
                'slogan' => $clinic->slogan,
                'phone' => $clinic->phone,
                'address' => $clinic->address,
                'logo_url' => $clinic->logoUrl(),
                'primary_color' => $clinic->primary_color,
                'currency_symbol' => $clinic->currency_symbol,
            ],
            'dentists' => $dentists,
            'services' => $services,
            'defaultDate' => CarbonImmutable::tomorrow()->toDateString(),
        ]);
    }

    public function slots(Request $request): JsonResponse
    {
        $clinic = Clinic::current();
        if ($clinic === null || ! $clinic->online_booking_enabled) {
            abort(404);
        }

        $validated = $request->validate([
            'dentist_id' => ['required', 'integer'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'duration' => ['nullable', 'integer', 'min:15', 'max:120'],
        ]);

        $dentist = $this->bookableDentistsQuery($clinic->id)
            ->findOrFail((int) $validated['dentist_id']);

        $date = CarbonImmutable::parse((string) $validated['date']);
        $duration = (int) ($validated['duration'] ?? 30);

        $existingAppointments = Appointment::withoutGlobalScopes()
            ->where('clinic_id', $clinic->id)
            ->where('dentist_id', $dentist->id)
            ->where('is_waitlist', false)
            ->whereDate('starts_at', $date->toDateString())
            ->whereNotIn('status', [AppointmentStatus::Cancelada->value, AppointmentStatus::NoAsistio->value])
            ->get(['starts_at', 'ends_at']);

        // Horario de atención estándar 09:00 a 19:00 (intervalos cada 30 min)
        $slots = [];
        $cursor = $date->setTime(9, 0);
        $closing = $date->setTime(19, 0);

        while ($cursor->addMinutes($duration)->lessThanOrEqualTo($closing)) {
            $slotEnd = $cursor->addMinutes($duration);
            $isPast = $cursor->lessThanOrEqualTo(now()->addMinutes(30));

            $overlaps = $existingAppointments->contains(function (Appointment $appt) use ($cursor, $slotEnd): bool {
                return $appt->starts_at->lessThan($slotEnd) && $appt->ends_at->greaterThan($cursor);
            });

            if (! $isPast && ! $overlaps) {
                $slots[] = [
                    'start' => $cursor->format('H:i'),
                    'end' => $slotEnd->format('H:i'),
                ];
            }

            $cursor = $cursor->addMinutes(30);
        }

        return response()->json([
            'date' => $date->toDateString(),
            'slots' => $slots,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $clinic = Clinic::current();
        if ($clinic === null || ! $clinic->online_booking_enabled) {
            abort(404);
        }

        $validated = $request->validate([
            'dentist_id' => ['required', 'integer'],
            'service_title' => ['required', 'string', 'max:160'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'duration' => ['nullable', 'integer', 'min:15', 'max:120'],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'document_number' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $dentist = $this->bookableDentistsQuery($clinic->id)
            ->findOrFail((int) $validated['dentist_id']);

        $startsAt = CarbonImmutable::parse("{$validated['date']} {$validated['start_time']}");
        $duration = (int) ($validated['duration'] ?? 30);
        $endsAt = $startsAt->addMinutes($duration);

        if ($startsAt->lessThan(now())) {
            return back()->with('error', 'Selecciona una fecha y hora futura disponible.');
        }

        $hasOverlap = Appointment::withoutGlobalScopes()
            ->where('clinic_id', $clinic->id)
            ->where('dentist_id', $dentist->id)
            ->where('is_waitlist', false)
            ->whereNotIn('status', [AppointmentStatus::Cancelada->value, AppointmentStatus::NoAsistio->value])
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();

        if ($hasOverlap) {
            return back()->with('error', 'Ese horario acaba de ser reservado. Por favor elige otro horario disponible.');
        }

        $appointment = DB::transaction(function () use ($clinic, $dentist, $validated, $startsAt, $endsAt): Appointment {
            $docNum = trim((string) $validated['document_number']);

            $patient = Patient::withoutGlobalScopes()
                ->where('clinic_id', $clinic->id)
                ->where('document_number', $docNum)
                ->first();

            if ($patient === null) {
                $patient = Patient::withoutGlobalScopes()->create([
                    'clinic_id' => $clinic->id,
                    'history_number' => Patient::nextHistoryNumber($clinic->id),
                    'first_name' => trim((string) $validated['first_name']),
                    'last_name' => trim((string) $validated['last_name']),
                    'document_type' => strlen($docNum) === 8 ? 'DNI' : 'OTRO',
                    'document_number' => $docNum,
                    'phone' => trim((string) $validated['phone']),
                    'email' => ! empty($validated['email']) ? mb_strtolower(trim((string) $validated['email'])) : null,
                    'crm_stage' => 'nuevo',
                    'tags' => ['Reserva Online'],
                    'is_active' => true,
                ]);
            }

            $chairs = $clinic->chairList();

            return Appointment::withoutGlobalScopes()->create([
                'clinic_id' => $clinic->id,
                'patient_id' => $patient->id,
                'dentist_id' => $dentist->id,
                'chair' => $chairs[0],
                'title' => trim((string) $validated['service_title']),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => AppointmentStatus::Reservada,
                'is_waitlist' => false,
                'reason' => $validated['reason'] ?? 'Reserva online 24/7 desde portal web',
                'confirmation_token' => Str::random(32),
            ]);
        });

        return redirect()
            ->route('public.appointment.show', ['token' => $appointment->confirmation_token])
            ->with('success', '¡Tu cita fue reservada correctamente! Puedes confirmarla o gestionarla aquí.');
    }
}
