<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ClinicalEvolution;
use App\Models\InventoryItem;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentInstallment;
use App\Models\SterilizationCycle;
use App\Models\TreatmentPlan;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $today = CarbonImmutable::today();
        $tomorrow = $today->copy()->addDay();

        $todayAppointments = Appointment::query()
            ->with(['patient.medicalHistory', 'dentist:id,name,specialty'])
            ->where('is_waitlist', false)
            ->whereBetween('starts_at', [$today->startOfDay(), $today->endOfDay()])
            ->orderBy('starts_at')
            ->get();

        $waitingRoom = $todayAppointments
            ->where('status', AppointmentStatus::EnSala)
            ->values();

        $recentPatients = Patient::query()
            ->with('medicalHistory:id,patient_id,medical_alerts,allergies')
            ->latest('updated_at')
            ->limit(6)
            ->get();

        $recentEvolutions = ClinicalEvolution::query()
            ->with(['patient:id,first_name,last_name,history_number', 'author:id,name'])
            ->latest('attended_at')
            ->limit(5)
            ->get();

        $incomeToday = (float) Payment::query()
            ->where('status', 'registrado')
            ->whereBetween('paid_at', [$today->startOfDay(), $today->endOfDay()])
            ->sum('amount');

        $incomeMonth = (float) Payment::query()
            ->where('status', 'registrado')
            ->whereBetween('paid_at', [$today->startOfMonth(), $today->endOfMonth()])
            ->sum('amount');

        $urgentLabAlerts = LabOrder::query()
            ->with(['patient:id,first_name,last_name,history_number', 'appointment:id,starts_at'])
            ->whereIn('status', ['por_enviar', 'en_laboratorio', 'rehacer'])
            ->where(function ($q) use ($today): void {
                $q->whereDate('due_date', '<=', $today->addDays(2)->toDateString())
                    ->orWhereHas('appointment', function ($aq) use ($today): void {
                        $aq->whereBetween('starts_at', [$today->startOfDay(), $today->addDays(2)->endOfDay()]);
                    });
            })
            ->limit(6)
            ->get();

        $overdueInstallmentsCount = PaymentInstallment::query()
            ->whereIn('status', ['pendiente', 'parcial'])
            ->whereDate('due_date', '<', $today->toDateString())
            ->count();

        $lowStockItemsCount = InventoryItem::query()
            ->where('is_active', true)
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->count();

        $expiringItemsCount = InventoryItem::query()
            ->where('is_active', true)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', $today->addDays(30)->toDateString())
            ->count();

        $pendingBioCyclesCount = SterilizationCycle::query()
            ->where('biological_indicator', 'pendiente')
            ->count();

        $stats = [
            'patients_total' => Patient::query()->where('is_active', true)->count(),
            'appointments_today' => $todayAppointments->count(),
            'in_waiting_room' => $waitingRoom->count(),
            'appointments_tomorrow' => Appointment::query()
                ->where('is_waitlist', false)
                ->whereBetween('starts_at', [$tomorrow->startOfDay(), $tomorrow->endOfDay()])
                ->count(),
            'active_plans' => TreatmentPlan::query()
                ->whereIn('status', ['aprobado', 'en_curso'])
                ->count(),
            'income_today' => $incomeToday,
            'income_month' => $incomeMonth,
            'overdue_installments' => $overdueInstallmentsCount,
            'low_stock_items' => $lowStockItemsCount,
            'expiring_items' => $expiringItemsCount,
            'pending_bio_cycles' => $pendingBioCyclesCount,
        ];

        return Inertia::render('dashboard', [
            'stats' => $stats,
            'todayAppointments' => $todayAppointments,
            'waitingRoom' => $waitingRoom,
            'recentPatients' => $recentPatients,
            'recentEvolutions' => $recentEvolutions,
            'urgentLabAlerts' => $urgentLabAlerts,
            'todayDate' => $today->toDateString(),
        ]);
    }
}
