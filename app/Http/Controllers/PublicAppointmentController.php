<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicAppointmentController extends Controller
{
    public function show(string $token): Response
    {
        $appointment = Appointment::withoutGlobalScopes()
            ->with([
                'clinic:id,name,ruc,phone,address,logo_path,primary_color',
                'patient:id,first_name,last_name,history_number',
                'dentist:id,name,specialty,license_number',
            ])
            ->where('confirmation_token', $token)
            ->firstOrFail();

        return Inertia::render('public/appointment-confirmation', [
            'appointment' => [
                'id' => $appointment->id,
                'title' => $appointment->title,
                'chair' => $appointment->chair,
                'starts_at' => $appointment->starts_at->toIso8601String(),
                'ends_at' => $appointment->ends_at->toIso8601String(),
                'status' => $appointment->status->value,
                'status_label' => $appointment->status->label(),
                'patient_confirmed_at' => $appointment->patient_confirmed_at?->toIso8601String(),
                'reschedule_requested_at' => $appointment->reschedule_requested_at?->toIso8601String(),
                'reschedule_note' => $appointment->reschedule_note,
                'token' => $token,
                'patient_name' => $appointment->patient->full_name ?? 'Paciente',
                'dentist_name' => $appointment->dentist->name ?? 'Odontólogo',
                'dentist_specialty' => $appointment->dentist?->specialty,
            ],
            'clinic' => [
                'name' => $appointment->clinic->name ?? 'Consultorio Odontológico',
                'phone' => $appointment->clinic?->phone,
                'address' => $appointment->clinic?->address,
                'logo_url' => $appointment->clinic?->logoUrl(),
                'primary_color' => $appointment->clinic->primary_color ?? '#0E8FA3',
            ],
        ]);
    }

    public function respond(Request $request, string $token): RedirectResponse
    {
        $appointment = Appointment::withoutGlobalScopes()
            ->where('confirmation_token', $token)
            ->firstOrFail();

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:confirm,reschedule'],
            'reschedule_note' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['action'] === 'confirm') {
            $appointment->update([
                'status' => AppointmentStatus::Confirmada,
                'patient_confirmed_at' => now(),
                'reschedule_requested_at' => null,
                'reschedule_note' => null,
                'version' => $appointment->version + 1,
            ]);

            return back()->with('success', '¡Gracias! Tu asistencia ha sido confirmada en nuestra agenda clínica.');
        }

        $appointment->update([
            'reschedule_requested_at' => now(),
            'reschedule_note' => $validated['reschedule_note'] ?? 'El paciente solicitó reprogramar horario.',
            'version' => $appointment->version + 1,
        ]);

        return back()->with('success', 'Hemos recibido tu solicitud de reprogramación. Recepción te contactará a la brevedad.');
    }
}
