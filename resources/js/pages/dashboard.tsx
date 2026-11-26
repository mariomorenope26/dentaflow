import { Head, Link, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    CalendarClock,
    CalendarDays,
    ClipboardCheck,
    FlaskConical,
    PackageCheck,
    Plus,
    ShieldAlert,
    Stethoscope,
    UserCheck,
    Users,
    Wallet,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { getOfflineSnapshot } from '@/lib/offline-store';
import type { Appointment, Auth, ClinicalEvolution, Patient } from '@/types';

type Props = {
    stats: {
        patients_total: number;
        appointments_today: number;
        in_waiting_room: number;
        appointments_tomorrow: number;
        active_plans: number;
        income_today: number;
        income_month: number;
        overdue_installments: number;
        low_stock_items: number;
        expiring_items: number;
        pending_bio_cycles: number;
    };
    todayAppointments: Appointment[];
    waitingRoom: Appointment[];
    recentPatients: Patient[];
    recentEvolutions: (ClinicalEvolution & {
        patient?: {
            id: number;
            first_name: string;
            last_name: string;
            history_number: string;
        };
    })[];
    urgentLabAlerts: {
        id: number;
        order_code: string;
        work_type: string;
        lab_name: string;
        status: string;
        due_date: string | null;
        patient?: {
            id: number;
            full_name: string;
            history_number: string;
        } | null;
    }[];
    todayDate: string;
};

const STATUS_BADGES: Record<string, string> = {
    reservada: 'bg-slate-100 text-slate-700 border-slate-200',
    confirmada: 'bg-[#DBEAFE] text-[#1E40AF] border-[#93C5FD]',
    en_sala: 'bg-[#FEF3C7] text-[#92400E] border-[#FCD34D]',
    atendida: 'bg-[#DCFCE7] text-[#166534] border-[#86EFAC]',
    cancelada: 'bg-[#FEE2E2] text-[#991B1B] border-[#FCA5A5]',
    no_asistio: 'bg-rose-50 text-rose-700 border-rose-200',
};

export default function Dashboard({
    stats,
    todayAppointments,
    waitingRoom,
    recentPatients,
    recentEvolutions,
    urgentLabAlerts,
}: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const currency = auth.clinic?.currency_symbol ?? 'S/';
    const [offlineAppointments, setOfflineAppointments] = useState<
        Appointment[] | null
    >(null);

    useEffect(() => {
        if (!navigator.onLine) {
            void getOfflineSnapshot().then((snap) => {
                if (snap) setOfflineAppointments(snap.appointments);
            });
        }
    }, []);

    const displayedAppointments =
        !navigator.onLine && offlineAppointments
            ? offlineAppointments
            : todayAppointments;

    return (
        <>
            <Head title="Inicio" />
            <div className="mx-auto max-w-7xl space-y-6 p-4 md:p-6">
                {/* Cabecera de bienvenida */}
                <div className="flex flex-col justify-between gap-4 rounded-2xl border bg-card p-5 shadow-xs sm:flex-row sm:items-center">
                    <div>
                        <p className="text-xs font-semibold tracking-wide text-[#0E8FA3] uppercase">
                            {auth.clinic?.name ?? 'Consultorio Odontológico'}
                        </p>
                        <h1 className="mt-0.5 text-2xl font-bold text-foreground">
                            Bienvenido(a), {auth.user?.name}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Resumen clínico y financiero del día ·{' '}
                            {auth.user?.role_label}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2.5">
                        {auth.user?.can.manage_patients && (
                            <Button
                                asChild
                                className="h-11 gap-2 rounded-xl bg-[#0E8FA3] px-4 text-white hover:bg-[#0A6577]"
                            >
                                <Link href="/patients?new=1">
                                    <Plus className="size-4" />
                                    Nuevo paciente
                                </Link>
                            </Button>
                        )}
                        <Button
                            asChild
                            variant="outline"
                            className="h-11 gap-2 rounded-xl px-4"
                        >
                            <Link href="/agenda">
                                <CalendarDays className="size-4 text-[#0E8FA3]" />
                                Abrir agenda
                            </Link>
                        </Button>
                        {auth.user?.can.manage_cash && (
                            <Button
                                asChild
                                variant="outline"
                                className="h-11 gap-2 rounded-xl px-4"
                            >
                                <Link href="/caja">
                                    <Wallet className="size-4 text-[#2E9E6B]" />
                                    Ir a caja
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                {/* KPIs principales */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div className="rounded-2xl border bg-card p-4 shadow-xs">
                        <div className="flex items-center justify-between text-muted-foreground">
                            <span className="text-xs font-medium">
                                Citas de hoy
                            </span>
                            <CalendarClock className="size-4 text-[#0E8FA3]" />
                        </div>
                        <div className="mt-2 text-2xl font-bold">
                            {stats.appointments_today}
                        </div>
                        <div className="mt-1 text-xs text-muted-foreground">
                            Mañana: {stats.appointments_tomorrow} programadas
                        </div>
                    </div>

                    <div className="rounded-2xl border bg-card p-4 shadow-xs">
                        <div className="flex items-center justify-between text-muted-foreground">
                            <span className="text-xs font-medium">
                                En sala de espera
                            </span>
                            <UserCheck className="size-4 text-[#F0A92E]" />
                        </div>
                        <div className="mt-2 text-2xl font-bold text-[#B45309]">
                            {stats.in_waiting_room}
                        </div>
                        <div className="mt-1 text-xs text-muted-foreground">
                            Listos para atención
                        </div>
                    </div>

                    <div className="rounded-2xl border bg-card p-4 shadow-xs">
                        <div className="flex items-center justify-between text-muted-foreground">
                            <span className="text-xs font-medium">
                                Pacientes activos
                            </span>
                            <Users className="size-4 text-[#5CC2A7]" />
                        </div>
                        <div className="mt-2 text-2xl font-bold">
                            {stats.patients_total}
                        </div>
                        <div className="mt-1 text-xs text-muted-foreground">
                            Fichas digitales
                        </div>
                    </div>

                    <div className="rounded-2xl border bg-card p-4 shadow-xs">
                        <div className="flex items-center justify-between text-muted-foreground">
                            <span className="text-xs font-medium">
                                Planes en curso
                            </span>
                            <ClipboardCheck className="size-4 text-[#2F7FD1]" />
                        </div>
                        <div className="mt-2 text-2xl font-bold">
                            {stats.active_plans}
                        </div>
                        <div className="mt-1 text-xs text-muted-foreground">
                            Presupuestos aprobados
                        </div>
                    </div>

                    <div className="rounded-2xl border bg-card p-4 shadow-xs">
                        <div className="flex items-center justify-between text-muted-foreground">
                            <span className="text-xs font-medium">
                                Ingresos de hoy
                            </span>
                            <Wallet className="size-4 text-[#2E9E6B]" />
                        </div>
                        <div className="mt-2 text-2xl font-bold text-[#2E9E6B]">
                            {currency} {stats.income_today.toFixed(2)}
                        </div>
                        <div className="mt-1 text-xs text-muted-foreground">
                            Mes: {currency} {stats.income_month.toFixed(2)}
                        </div>
                    </div>
                </div>

                {/* Alertas operativas clínicas, bioseguridad, morosidad y laboratorio */}
                {(urgentLabAlerts.length > 0 ||
                    stats.overdue_installments > 0 ||
                    stats.low_stock_items > 0 ||
                    stats.expiring_items > 0 ||
                    stats.pending_bio_cycles > 0) && (
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        {urgentLabAlerts.length > 0 && (
                            <Link
                                href="/laboratorio"
                                className="flex items-start gap-3 rounded-2xl border border-amber-300 bg-amber-50/70 p-3.5 text-xs transition hover:bg-amber-50 dark:bg-amber-950/20"
                            >
                                <FlaskConical className="mt-0.5 size-4 shrink-0 text-amber-700" />
                                <div>
                                    <div className="font-bold text-amber-950 dark:text-amber-200">
                                        {urgentLabAlerts.length} orden(es) de
                                        laboratorio próximas
                                    </div>
                                    <div className="text-amber-800 dark:text-amber-300">
                                        {urgentLabAlerts[0]?.order_code} ·{' '}
                                        {urgentLabAlerts[0]?.patient?.full_name}
                                    </div>
                                </div>
                            </Link>
                        )}
                        {stats.overdue_installments > 0 && (
                            <Link
                                href="/caja"
                                className="flex items-start gap-3 rounded-2xl border border-rose-300 bg-rose-50/70 p-3.5 text-xs transition hover:bg-rose-50 dark:bg-rose-950/20"
                            >
                                <AlertTriangle className="mt-0.5 size-4 shrink-0 text-[#D64545]" />
                                <div>
                                    <div className="font-bold text-[#991B1B] dark:text-rose-200">
                                        {stats.overdue_installments} cuota(s)
                                        vencida(s) por cobrar
                                    </div>
                                    <div className="text-[#991B1B]/80 dark:text-rose-300">
                                        Revisar morosidad en Caja
                                    </div>
                                </div>
                            </Link>
                        )}
                        {(stats.low_stock_items > 0 ||
                            stats.expiring_items > 0) && (
                            <Link
                                href="/esterilizacion-kardex?tab=kardex"
                                className="flex items-start gap-3 rounded-2xl border border-orange-300 bg-orange-50/70 p-3.5 text-xs transition hover:bg-orange-50 dark:bg-orange-950/20"
                            >
                                <PackageCheck className="mt-0.5 size-4 shrink-0 text-orange-700" />
                                <div>
                                    <div className="font-bold text-orange-950 dark:text-orange-200">
                                        Alertas en Kardex de insumos
                                    </div>
                                    <div className="text-orange-800 dark:text-orange-300">
                                        {stats.low_stock_items} bajo stock ·{' '}
                                        {stats.expiring_items} por vencer
                                    </div>
                                </div>
                            </Link>
                        )}
                        {stats.pending_bio_cycles > 0 && (
                            <Link
                                href="/esterilizacion-kardex"
                                className="flex items-start gap-3 rounded-2xl border border-cyan-300 bg-[#E6F5F7]/70 p-3.5 text-xs transition hover:bg-[#E6F5F7] dark:bg-cyan-950/20"
                            >
                                <ShieldAlert className="mt-0.5 size-4 shrink-0 text-[#0A6577]" />
                                <div>
                                    <div className="font-bold text-[#0A6577] dark:text-cyan-200">
                                        {stats.pending_bio_cycles} ciclo(s)
                                        autoclave en incubación
                                    </div>
                                    <div className="text-[#0A6577]/80 dark:text-cyan-300">
                                        Registrar lectura biológica
                                    </div>
                                </div>
                            </Link>
                        )}
                    </div>
                )}

                <div className="grid gap-6 lg:grid-cols-12">
                    {/* Citas del día + Sala de espera */}
                    <div className="space-y-4 lg:col-span-7">
                        {waitingRoom.length > 0 && (
                            <div className="rounded-2xl border border-[#F0A92E]/50 bg-[#FFFBEB] p-4 dark:bg-amber-950/20">
                                <div className="mb-2 flex items-center gap-2 text-sm font-bold text-[#92400E] dark:text-amber-300">
                                    <UserCheck className="size-4" />
                                    En sala de espera ahora (
                                    {waitingRoom.length})
                                </div>
                                <div className="space-y-2">
                                    {waitingRoom.map((appt) => (
                                        <div
                                            key={appt.id}
                                            className="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-white p-3 shadow-xs dark:bg-card"
                                        >
                                            <div>
                                                <Link
                                                    href={`/patients/${appt.patient_id}`}
                                                    className="font-semibold text-foreground hover:text-[#0E8FA3]"
                                                >
                                                    {appt.patient?.full_name}
                                                </Link>
                                                <div className="text-xs text-muted-foreground">
                                                    {appt.chair} ·{' '}
                                                    {appt.dentist?.name} ·{' '}
                                                    {appt.title}
                                                </div>
                                            </div>
                                            <Button
                                                asChild
                                                size="sm"
                                                className="bg-[#0E8FA3] text-white"
                                            >
                                                <Link
                                                    href={`/patients/${appt.patient_id}?tab=odontograma`}
                                                >
                                                    Atender
                                                </Link>
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        <div className="rounded-2xl border bg-card p-5 shadow-xs">
                            <div className="mb-4 flex items-center justify-between">
                                <h2 className="text-base font-bold">
                                    Agenda del día
                                </h2>
                                <Link
                                    href="/agenda"
                                    className="text-xs font-semibold text-[#0E8FA3] hover:underline"
                                >
                                    Ver semana completa
                                </Link>
                            </div>

                            {displayedAppointments.length === 0 ? (
                                <div className="rounded-xl border border-dashed py-8 text-center text-sm text-muted-foreground">
                                    No hay citas programadas para hoy.
                                </div>
                            ) : (
                                <div className="divide-y">
                                    {displayedAppointments.map((appt) => {
                                        const hasAlerts =
                                            Boolean(
                                                appt.patient?.medical_history
                                                    ?.allergies,
                                            ) ||
                                            (appt.patient?.medical_history
                                                ?.medical_alerts?.length ?? 0) >
                                                0;
                                        return (
                                            <div
                                                key={appt.id}
                                                className="flex flex-wrap items-center justify-between gap-3 py-3"
                                            >
                                                <div className="flex items-center gap-3">
                                                    <div className="rounded-xl bg-[#E6F5F7] px-2.5 py-1.5 text-center text-xs font-bold text-[#0A6577]">
                                                        {new Date(
                                                            appt.starts_at,
                                                        ).toLocaleTimeString(
                                                            'es-PE',
                                                            {
                                                                hour: '2-digit',
                                                                minute: '2-digit',
                                                            },
                                                        )}
                                                    </div>
                                                    <div>
                                                        <div className="flex items-center gap-2">
                                                            <Link
                                                                href={`/patients/${appt.patient_id}`}
                                                                className="font-semibold hover:text-[#0E8FA3]"
                                                            >
                                                                {
                                                                    appt.patient
                                                                        ?.full_name
                                                                }
                                                            </Link>
                                                            {hasAlerts && (
                                                                <span className="inline-flex items-center gap-1 rounded-full bg-[#FEE2E2] px-2 py-0.5 text-[10px] font-bold text-[#D64545]">
                                                                    <AlertTriangle className="size-3" />
                                                                    Alerta
                                                                    médica
                                                                </span>
                                                            )}
                                                        </div>
                                                        <div className="text-xs text-muted-foreground">
                                                            {appt.title} ·{' '}
                                                            {appt.chair} ·{' '}
                                                            {appt.dentist?.name}
                                                        </div>
                                                    </div>
                                                </div>

                                                <Badge
                                                    variant="outline"
                                                    className={
                                                        STATUS_BADGES[
                                                            appt.status
                                                        ] ?? ''
                                                    }
                                                >
                                                    {appt.status.replace(
                                                        '_',
                                                        ' ',
                                                    )}
                                                </Badge>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Pacientes recientes y últimas evoluciones */}
                    <div className="space-y-4 lg:col-span-5">
                        <div className="rounded-2xl border bg-card p-5 shadow-xs">
                            <div className="mb-3 flex items-center justify-between">
                                <h2 className="text-base font-bold">
                                    Pacientes recientes
                                </h2>
                                <Link
                                    href="/patients"
                                    className="text-xs font-semibold text-[#0E8FA3] hover:underline"
                                >
                                    Ver todos
                                </Link>
                            </div>
                            <div className="space-y-2">
                                {recentPatients.map((patient) => (
                                    <Link
                                        key={patient.id}
                                        href={`/patients/${patient.id}`}
                                        className="flex items-center justify-between rounded-xl border px-3.5 py-2.5 transition-colors hover:bg-accent/50"
                                    >
                                        <div>
                                            <div className="text-sm font-semibold">
                                                {patient.full_name}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {patient.history_number} ·{' '}
                                                {patient.phone ??
                                                    'Sin teléfono'}
                                            </div>
                                        </div>
                                        {(patient.medical_history?.allergies ||
                                            (patient.medical_history
                                                ?.medical_alerts?.length ?? 0) >
                                                0) && (
                                            <AlertTriangle className="size-4 text-[#D64545]" />
                                        )}
                                    </Link>
                                ))}
                            </div>
                        </div>

                        <div className="rounded-2xl border bg-card p-5 shadow-xs">
                            <div className="mb-3 flex items-center gap-2">
                                <Stethoscope className="size-4 text-[#0E8FA3]" />
                                <h2 className="text-base font-bold">
                                    Últimas atenciones clínicas
                                </h2>
                            </div>
                            {recentEvolutions.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Sin evoluciones clínicas registradas.
                                </p>
                            ) : (
                                <div className="space-y-3">
                                    {recentEvolutions.map((ev) => (
                                        <div
                                            key={ev.id}
                                            className="rounded-xl bg-muted/40 p-3 text-xs"
                                        >
                                            <div className="flex items-center justify-between font-semibold">
                                                <Link
                                                    href={`/patients/${ev.patient_id}?tab=historia`}
                                                    className="text-[#0A6577] hover:underline dark:text-[#7BE0C8]"
                                                >
                                                    {ev.patient?.first_name}{' '}
                                                    {ev.patient?.last_name}
                                                </Link>
                                                <span className="text-muted-foreground">
                                                    {new Date(
                                                        ev.attended_at,
                                                    ).toLocaleDateString(
                                                        'es-PE',
                                                    )}
                                                </span>
                                            </div>
                                            <div className="mt-1 font-medium text-foreground">
                                                {ev.procedure_summary}
                                            </div>
                                            <div className="mt-0.5 text-muted-foreground">
                                                Por {ev.author?.name}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
