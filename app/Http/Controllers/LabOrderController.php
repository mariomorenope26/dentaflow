<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LabOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $status = trim((string) $request->query('status', ''));
        $search = trim((string) $request->query('search', ''));

        $query = LabOrder::query()
            ->with([
                'patient:id,first_name,last_name,history_number,phone',
                'dentist:id,name',
                'appointment:id,title,starts_at,chair',
            ]);

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('order_code', 'like', "%{$search}%")
                    ->orWhere('lab_name', 'like', "%{$search}%")
                    ->orWhere('work_type', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($pq) use ($search): void {
                        $pq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('history_number', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->orderByRaw("CASE WHEN status IN ('por_enviar', 'en_laboratorio', 'prueba_clinica', 'rehacer') THEN 0 ELSE 1 END")
            ->orderBy('due_date')
            ->orderByDesc('id')
            ->get();

        $allActive = LabOrder::query()
            ->whereIn('status', ['por_enviar', 'en_laboratorio', 'prueba_clinica', 'rehacer'])
            ->get();

        $overdueCount = $allActive->filter(fn (LabOrder $o): bool => $o->due_date !== null && $o->due_date->isPast())->count();
        $urgentAppointmentCount = LabOrder::query()
            ->whereIn('status', ['por_enviar', 'en_laboratorio', 'rehacer'])
            ->whereHas('appointment', function ($q): void {
                $q->where('starts_at', '>=', now())
                    ->where('starts_at', '<=', now()->addDays(2));
            })
            ->count();

        $patients = Patient::query()
            ->where('is_active', true)
            ->orderBy('last_name')
            ->limit(150)
            ->get(['id', 'first_name', 'last_name', 'history_number']);

        $dentists = User::query()
            ->whereIn('role', [UserRole::AdminConsultorio, UserRole::Odontologo])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $upcomingAppointments = Appointment::query()
            ->with('patient:id,first_name,last_name,history_number')
            ->where('starts_at', '>=', now()->subDay())
            ->orderBy('starts_at')
            ->limit(80)
            ->get(['id', 'patient_id', 'title', 'starts_at']);

        return Inertia::render('laboratory/index', [
            'orders' => $orders,
            'stats' => [
                'active' => $allActive->count(),
                'overdue' => $overdueCount,
                'urgent_appointments' => $urgentAppointmentCount,
                'ready' => LabOrder::query()->where('status', 'recibido')->count(),
            ],
            'patients' => $patients,
            'dentists' => $dentists,
            'upcomingAppointments' => $upcomingAppointments,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->canEditClinicalRecords() && ! $user->canManageAppointments() && ! $user->canManageClinic()) {
            abort(403);
        }

        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'dentist_id' => ['nullable', 'integer', 'exists:users,id'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'lab_name' => ['required', 'string', 'max:140'],
            'work_type' => ['required', 'string', 'max:140'],
            'tooth_code' => ['nullable', 'string', 'max:20'],
            'shade' => ['nullable', 'string', 'max:30'],
            'material' => ['nullable', 'string', 'max:100'],
            'sent_at' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'status' => ['required', 'string', 'in:por_enviar,en_laboratorio,prueba_clinica,recibido,entregado,rehacer'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $patient = Patient::query()->findOrFail((int) $validated['patient_id']);
        $clinicId = $patient->clinic_id ?: ($user->clinic_id ?? (int) Clinic::query()->value('id'));

        $order = LabOrder::create([
            ...$validated,
            'clinic_id' => $clinicId,
            'order_code' => LabOrder::nextOrderCode($clinicId),
            'cost' => $validated['cost'] ?? 0,
            'created_by' => $user->id,
        ]);

        AuditLogger::log('create', $order, "Registró orden de laboratorio {$order->order_code} ({$order->work_type}) para {$patient->full_name}", [], $request);

        return back()->with('success', "Orden de laboratorio {$order->order_code} registrada.");
    }

    public function update(Request $request, LabOrder $labOrder): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        if ($labOrder->clinic_id !== $user->clinic_id) {
            abort(403);
        }

        $validated = $request->validate([
            'lab_name' => ['sometimes', 'string', 'max:140'],
            'work_type' => ['sometimes', 'string', 'max:140'],
            'tooth_code' => ['nullable', 'string', 'max:20'],
            'shade' => ['nullable', 'string', 'max:30'],
            'material' => ['nullable', 'string', 'max:100'],
            'sent_at' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'received_at' => ['nullable', 'date'],
            'delivered_at' => ['nullable', 'date'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'status' => ['sometimes', 'string', 'in:por_enviar,en_laboratorio,prueba_clinica,recibido,entregado,rehacer'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (($validated['status'] ?? null) === 'recibido' && empty($validated['received_at']) && $labOrder->received_at === null) {
            $validated['received_at'] = now()->toDateString();
        }
        if (($validated['status'] ?? null) === 'entregado' && empty($validated['delivered_at']) && $labOrder->delivered_at === null) {
            $validated['delivered_at'] = now()->toDateString();
        }

        $labOrder->update($validated);

        AuditLogger::log('update', $labOrder, "Actualizó orden de laboratorio {$labOrder->order_code} a estado {$labOrder->status}", [], $request);

        return back()->with('success', "Orden {$labOrder->order_code} actualizada.");
    }

    public function destroy(Request $request, LabOrder $labOrder): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        if ($labOrder->clinic_id !== $user->clinic_id) {
            abort(403);
        }

        $code = $labOrder->order_code;
        $clinicId = $labOrder->clinic_id;
        $labOrder->delete();

        AuditLogger::log('delete', LabOrder::class, "Eliminó orden de laboratorio {$code}", [], $request, $clinicId);

        return back()->with('success', "Orden {$code} eliminada.");
    }
}
