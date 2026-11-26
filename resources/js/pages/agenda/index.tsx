import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    Clock,
    FlaskConical,
    GripVertical,
    MessageCircle,
    Pencil,
    Plus,
    Trash2,
    Users,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { getOfflineSnapshot, queueOfflineMutation } from '@/lib/offline-store';
import type { Appointment, Auth } from '@/types';

type Props = {
    appointments: Appointment[];
    waitlist: Appointment[];
    dentists: { id: number; name: string; specialty?: string | null }[];
    patients: {
        id: number;
        first_name: string;
        last_name: string;
        full_name: string;
        history_number: string;
    }[];
    chairs: string[];
    filters: {
        view: 'day' | 'week' | 'month';
        date: string;
        dentist_id: string;
        chair: string;
    };
    range: { start: string; end: string };
};

const STATUS_OPTIONS = [
    { value: 'reservada', label: 'Reservada' },
    { value: 'confirmada', label: 'Confirmada' },
    { value: 'en_sala', label: 'En sala' },
    { value: 'atendida', label: 'Atendida' },
    { value: 'cancelada', label: 'Cancelada' },
    { value: 'no_asistio', label: 'No asistió' },
] as const;

const STATUS_STYLE: Record<string, string> = {
    reservada: 'border-l-slate-400 bg-slate-50 dark:bg-slate-900/40',
    confirmada: 'border-l-[#2F7FD1] bg-[#EFF6FF] dark:bg-blue-950/30',
    en_sala: 'border-l-[#F0A92E] bg-[#FFFBEB] dark:bg-amber-950/30',
    atendida: 'border-l-[#2E9E6B] bg-[#ECFDF5] dark:bg-emerald-950/30',
    cancelada: 'border-l-[#D64545] bg-[#FEF2F2] opacity-70 dark:bg-red-950/30',
    no_asistio: 'border-l-rose-400 bg-rose-50 opacity-75 dark:bg-rose-950/30',
};

function addDays(dateStr: string, days: number): string {
    const d = new Date(`${dateStr}T12:00:00`);
    d.setDate(d.getDate() + days);
    return d.toISOString().slice(0, 10);
}

export default function AgendaIndex({
    appointments,
    waitlist,
    dentists,
    patients,
    chairs,
    filters,
    range,
}: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const canManage = Boolean(auth.user?.can.manage_appointments);

    const [openCreate, setOpenCreate] = useState(false);
    const [editingAppt, setEditingAppt] = useState<Appointment | null>(null);
    const [deletingAppt, setDeletingAppt] = useState<Appointment | null>(null);
    const [draggedId, setDraggedId] = useState<number | null>(null);
    const [offlineCache, setOfflineCache] = useState<Appointment[] | null>(
        null,
    );

    const editForm = useForm({
        dentist_id: '',
        chair: 'Sillón 1',
        title: '',
        date: filters.date,
        start_time: '09:00',
        end_time: '09:45',
        status: 'reservada',
        is_waitlist: false,
        reason: '',
        notes: '',
    });

    const openEditModal = (appt: Appointment) => {
        const s = new Date(appt.starts_at);
        const e = new Date(appt.ends_at);
        const fmtDate = (d: Date) =>
            `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        const fmtTime = (d: Date) =>
            `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;

        setEditingAppt(appt);
        editForm.setData({
            dentist_id: String(appt.dentist_id),
            chair: appt.chair,
            title: appt.title,
            date: fmtDate(s),
            start_time: fmtTime(s),
            end_time: fmtTime(e),
            status: appt.status,
            is_waitlist: appt.is_waitlist,
            reason: appt.reason ?? '',
            notes: appt.notes ?? '',
        });
    };

    const submitEdit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingAppt) return;
        router.patch(
            `/agenda/${editingAppt.id}`,
            {
                dentist_id: Number(editForm.data.dentist_id),
                chair: editForm.data.chair,
                title: editForm.data.title,
                starts_at: `${editForm.data.date} ${editForm.data.start_time}:00`,
                ends_at: `${editForm.data.date} ${editForm.data.end_time}:00`,
                status: editForm.data.status,
                is_waitlist: editForm.data.is_waitlist,
                reason: editForm.data.reason,
                notes: editForm.data.notes,
            },
            {
                preserveScroll: true,
                onSuccess: () => setEditingAppt(null),
            },
        );
    };

    const confirmDeleteAppt = () => {
        if (!deletingAppt) return;
        router.delete(`/agenda/${deletingAppt.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeletingAppt(null),
        });
    };

    useEffect(() => {
        if (!navigator.onLine) {
            void getOfflineSnapshot().then((snap) => {
                if (snap) setOfflineCache(snap.appointments);
            });
        }
    }, []);

    const activeAppointments =
        !navigator.onLine && offlineCache ? offlineCache : appointments;

    const form = useForm({
        patient_id: String(patients[0]?.id ?? ''),
        dentist_id: String(dentists[0]?.id ?? ''),
        chair: chairs[0] ?? 'Sillón 1',
        title: 'Evaluación odontológica',
        date: filters.date,
        start_time: '09:00',
        end_time: '09:45',
        is_waitlist: false,
        reason: '',
    });

    const navigateAgenda = (next: Partial<typeof filters>) => {
        router.get(
            '/agenda',
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true },
        );
    };

    const shiftDate = (delta: number) => {
        const step =
            filters.view === 'day'
                ? delta
                : filters.view === 'month'
                  ? delta * 30
                  : delta * 7;
        navigateAgenda({ date: addDays(filters.date, step) });
    };

    const daysInRange = useMemo(() => {
        const list: string[] = [];
        let cursor = range.start;
        while (cursor <= range.end && list.length < 42) {
            list.push(cursor);
            cursor = addDays(cursor, 1);
        }
        return list;
    }, [range.start, range.end]);

    const groupedByDay = useMemo(() => {
        const map: Record<string, Appointment[]> = {};
        for (const appt of activeAppointments) {
            const day = appt.starts_at.slice(0, 10);
            map[day] = map[day] ?? [];
            map[day].push(appt);
        }
        return map;
    }, [activeAppointments]);

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(
            '/agenda',
            {
                patient_id: Number(form.data.patient_id),
                dentist_id: Number(form.data.dentist_id),
                chair: form.data.chair,
                title: form.data.title,
                starts_at: `${form.data.date} ${form.data.start_time}:00`,
                ends_at: `${form.data.date} ${form.data.end_time}:00`,
                is_waitlist: form.data.is_waitlist,
                reason: form.data.reason,
            },
            {
                preserveScroll: true,
                onSuccess: () => setOpenCreate(false),
            },
        );
    };

    const handleStatusChange = async (
        appt: Appointment,
        nextStatus: string,
    ) => {
        if (!canManage) return;

        if (!navigator.onLine) {
            await queueOfflineMutation(
                'appointment.status',
                `Cita #${appt.id} -> ${nextStatus}`,
                {
                    appointment_id: appt.id,
                    expected_version: appt.version,
                    status: nextStatus,
                },
            );
            toast.success(
                'Sin conexión: cambio de estado guardado en la cola local.',
            );
            return;
        }

        router.patch(
            `/agenda/${appt.id}`,
            { status: nextStatus },
            { preserveScroll: true },
        );
    };

    const handleDropOnDay = (targetDay: string) => {
        if (!draggedId || !canManage) return;
        const appt = activeAppointments.find((a) => a.id === draggedId);
        setDraggedId(null);
        if (!appt) return;

        const timeStart = appt.starts_at.slice(11, 19) || '09:00:00';
        const timeEnd = appt.ends_at.slice(11, 19) || '09:45:00';
        if (appt.starts_at.slice(0, 10) === targetDay) return;

        router.patch(
            `/agenda/${appt.id}`,
            {
                starts_at: `${targetDay} ${timeStart}`,
                ends_at: `${targetDay} ${timeEnd}`,
            },
            { preserveScroll: true },
        );
    };

    const promoteWaitlist = (appt: Appointment) => {
        router.patch(
            `/agenda/${appt.id}`,
            { is_waitlist: false, status: 'confirmada' },
            { preserveScroll: true },
        );
    };

    const sendWhatsappReminder = async (appt: Appointment) => {
        try {
            const xsrf = decodeURIComponent(
                document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '',
            );
            const res = await fetch(`/agenda/${appt.id}/whatsapp-reminder`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': xsrf,
                },
            });
            const data = await res.json();
            if (!res.ok || !data.whatsapp_url) {
                toast.error('No se pudo preparar el recordatorio.');
                return;
            }
            window.open(data.whatsapp_url, '_blank', 'noopener,noreferrer');
            toast.success(
                'Enlace de confirmación 1-clic generado por WhatsApp.',
            );
            router.reload({ only: ['appointments'] });
        } catch {
            toast.error('Error al generar recordatorio WhatsApp.');
        }
    };

    return (
        <>
            <Head title="Agenda y citas" />
            <div className="mx-auto max-w-7xl space-y-5 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">
                            Agenda clínica
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Arrastra y suelta citas entre días para reprogramar
                            · {range.start} al {range.end}
                        </p>
                    </div>
                    {canManage && (
                        <Button
                            onClick={() => setOpenCreate(true)}
                            className="h-11 gap-2 rounded-xl bg-[#0E8FA3] px-5 text-white hover:bg-[#0A6577]"
                        >
                            <Plus className="size-4" />
                            Nueva cita
                        </Button>
                    )}
                </div>

                {/* Filtros y vistas */}
                <div className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border bg-card p-3.5 shadow-xs">
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="icon"
                            className="size-9 rounded-xl"
                            onClick={() => shiftDate(-1)}
                        >
                            <ChevronLeft className="size-4" />
                        </Button>
                        <Input
                            type="date"
                            value={filters.date}
                            onChange={(e) =>
                                navigateAgenda({ date: e.target.value })
                            }
                            className="h-9 w-40 rounded-xl text-xs"
                        />
                        <Button
                            variant="outline"
                            size="icon"
                            className="size-9 rounded-xl"
                            onClick={() => shiftDate(1)}
                        >
                            <ChevronRight className="size-4" />
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            className="h-9 rounded-xl text-xs"
                            onClick={() =>
                                navigateAgenda({
                                    date: new Date().toISOString().slice(0, 10),
                                })
                            }
                        >
                            Hoy
                        </Button>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <div className="inline-flex rounded-xl border bg-muted/40 p-0.5">
                            {(
                                [
                                    ['day', 'Día'],
                                    ['week', 'Semana'],
                                    ['month', 'Mes'],
                                ] as const
                            ).map(([v, label]) => (
                                <button
                                    key={v}
                                    type="button"
                                    onClick={() => navigateAgenda({ view: v })}
                                    className={`rounded-lg px-3 py-1.5 text-xs font-semibold ${
                                        filters.view === v
                                            ? 'bg-[#0E8FA3] text-white'
                                            : 'text-muted-foreground'
                                    }`}
                                >
                                    {label}
                                </button>
                            ))}
                        </div>

                        <select
                            value={filters.dentist_id}
                            onChange={(e) =>
                                navigateAgenda({ dentist_id: e.target.value })
                            }
                            className="h-9 rounded-xl border bg-background px-2.5 text-xs"
                        >
                            <option value="">Todos los profesionales</option>
                            {dentists.map((d) => (
                                <option key={d.id} value={d.id}>
                                    {d.name}
                                </option>
                            ))}
                        </select>

                        <select
                            value={filters.chair}
                            onChange={(e) =>
                                navigateAgenda({ chair: e.target.value })
                            }
                            className="h-9 rounded-xl border bg-background px-2.5 text-xs"
                        >
                            <option value="">Todos los sillones</option>
                            {chairs.map((c) => (
                                <option key={c} value={c}>
                                    {c}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                {/* Calendario */}
                <div
                    className={`grid gap-3 ${
                        filters.view === 'day'
                            ? 'grid-cols-1'
                            : filters.view === 'month'
                              ? 'grid-cols-2 md:grid-cols-4 lg:grid-cols-7'
                              : 'grid-cols-1 md:grid-cols-2 lg:grid-cols-7'
                    }`}
                >
                    {daysInRange.map((day) => {
                        const dayAppts = groupedByDay[day] ?? [];
                        const label = new Date(
                            `${day}T12:00:00`,
                        ).toLocaleDateString('es-PE', {
                            weekday: 'short',
                            day: '2-digit',
                            month: 'short',
                        });
                        const isToday =
                            day === new Date().toISOString().slice(0, 10);

                        return (
                            <div
                                key={day}
                                onDragOver={(e) => e.preventDefault()}
                                onDrop={() => handleDropOnDay(day)}
                                className={`flex min-h-44 flex-col rounded-2xl border bg-card p-3 shadow-xs transition-colors ${
                                    isToday
                                        ? 'border-[#0E8FA3] ring-1 ring-[#0E8FA3]/30'
                                        : ''
                                }`}
                            >
                                <div className="mb-2 flex items-center justify-between border-b pb-1.5">
                                    <span
                                        className={`text-xs font-bold capitalize ${
                                            isToday
                                                ? 'text-[#0E8FA3]'
                                                : 'text-foreground'
                                        }`}
                                    >
                                        {label}
                                    </span>
                                    <Badge
                                        variant="secondary"
                                        className="text-[10px]"
                                    >
                                        {dayAppts.length}
                                    </Badge>
                                </div>

                                <div className="flex-1 space-y-2">
                                    {dayAppts.length === 0 ? (
                                        <p className="py-4 text-center text-[11px] text-muted-foreground">
                                            Libre
                                        </p>
                                    ) : (
                                        dayAppts.map((appt) => (
                                            <div
                                                key={appt.id}
                                                draggable={canManage}
                                                onDragStart={() =>
                                                    setDraggedId(appt.id)
                                                }
                                                className={`rounded-xl border border-l-4 p-2.5 text-xs shadow-2xs ${
                                                    STATUS_STYLE[appt.status] ??
                                                    ''
                                                }`}
                                            >
                                                <div className="flex items-start justify-between gap-1">
                                                    <span className="font-bold text-[#0A6577] dark:text-[#7BE0C8]">
                                                        {new Date(
                                                            appt.starts_at,
                                                        ).toLocaleTimeString(
                                                            'es-PE',
                                                            {
                                                                hour: '2-digit',
                                                                minute: '2-digit',
                                                            },
                                                        )}
                                                    </span>
                                                    {canManage && (
                                                        <div className="flex items-center gap-0.5">
                                                            <button
                                                                type="button"
                                                                title="Editar cita"
                                                                onClick={() =>
                                                                    openEditModal(
                                                                        appt,
                                                                    )
                                                                }
                                                                className="rounded p-1 text-muted-foreground hover:bg-black/5 hover:text-foreground"
                                                            >
                                                                <Pencil className="size-3" />
                                                            </button>
                                                            <button
                                                                type="button"
                                                                title="Eliminar cita"
                                                                onClick={() =>
                                                                    setDeletingAppt(
                                                                        appt,
                                                                    )
                                                                }
                                                                className="rounded p-1 text-muted-foreground hover:bg-red-50 hover:text-[#D64545]"
                                                            >
                                                                <Trash2 className="size-3" />
                                                            </button>
                                                            <GripVertical className="size-3.5 cursor-grab text-muted-foreground" />
                                                        </div>
                                                    )}
                                                </div>

                                                <Link
                                                    href={`/patients/${appt.patient_id}`}
                                                    className="mt-1 block font-bold text-foreground hover:text-[#0E8FA3]"
                                                >
                                                    {appt.patient?.full_name}
                                                </Link>
                                                <div className="text-[11px] text-muted-foreground">
                                                    {appt.title}
                                                </div>
                                                <div className="mt-0.5 text-[10px] text-muted-foreground">
                                                    {appt.chair} ·{' '}
                                                    {appt.dentist?.name}
                                                </div>

                                                {(appt.patient?.medical_history
                                                    ?.allergies ||
                                                    (appt.patient
                                                        ?.medical_history
                                                        ?.medical_alerts
                                                        ?.length ?? 0) > 0) && (
                                                    <div className="mt-1 inline-flex items-center gap-1 rounded bg-[#FEE2E2] px-1.5 py-0.5 text-[10px] font-bold text-[#D64545]">
                                                        <AlertTriangle className="size-2.5" />
                                                        Alerta médica
                                                    </div>
                                                )}

                                                {(appt.lab_orders ?? []).some(
                                                    (lo) =>
                                                        [
                                                            'por_enviar',
                                                            'en_laboratorio',
                                                            'rehacer',
                                                        ].includes(lo.status),
                                                ) && (
                                                    <div className="mt-1 inline-flex items-center gap-1 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-900">
                                                        <FlaskConical className="size-2.5" />
                                                        Lab. pendiente
                                                    </div>
                                                )}

                                                {appt.patient_confirmed_at && (
                                                    <div className="mt-1 text-[10px] font-bold text-[#2E9E6B]">
                                                        ✓ Confirmó por enlace
                                                    </div>
                                                )}
                                                {appt.reschedule_requested_at && (
                                                    <div className="mt-1 rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-900">
                                                        Reprogramar:{' '}
                                                        {appt.reschedule_note ||
                                                            'Solicitó cambio'}
                                                    </div>
                                                )}

                                                {canManage && (
                                                    <div className="mt-2 flex items-center gap-1">
                                                        <select
                                                            value={appt.status}
                                                            onChange={(e) =>
                                                                void handleStatusChange(
                                                                    appt,
                                                                    e.target
                                                                        .value,
                                                                )
                                                            }
                                                            className="h-7 flex-1 rounded-lg border bg-background px-1.5 text-[11px] font-medium"
                                                        >
                                                            {STATUS_OPTIONS.map(
                                                                (opt) => (
                                                                    <option
                                                                        key={
                                                                            opt.value
                                                                        }
                                                                        value={
                                                                            opt.value
                                                                        }
                                                                    >
                                                                        {
                                                                            opt.label
                                                                        }
                                                                    </option>
                                                                ),
                                                            )}
                                                        </select>
                                                        <button
                                                            type="button"
                                                            title={
                                                                appt.reminder_sent_at
                                                                    ? 'Reenviar WhatsApp 1-clic'
                                                                    : 'Enviar recordatorio WhatsApp 1-clic'
                                                            }
                                                            onClick={() =>
                                                                void sendWhatsappReminder(
                                                                    appt,
                                                                )
                                                            }
                                                            className={`inline-flex h-7 items-center gap-1 rounded-lg border px-1.5 text-[10px] font-semibold ${
                                                                appt.reminder_sent_at
                                                                    ? 'border-[#2E9E6B]/40 bg-[#DCFCE7] text-[#166534]'
                                                                    : 'bg-background text-[#0A6577] hover:bg-[#E6F5F7]'
                                                            }`}
                                                        >
                                                            <MessageCircle className="size-3" />
                                                            WA
                                                        </button>
                                                    </div>
                                                )}
                                            </div>
                                        ))
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>

                {/* Lista de espera */}
                <div className="rounded-2xl border bg-card p-5 shadow-xs">
                    <div className="mb-3 flex items-center gap-2">
                        <Users className="size-4 text-[#F0A92E]" />
                        <h2 className="text-base font-bold">
                            Lista de espera ({waitlist.length})
                        </h2>
                    </div>
                    {waitlist.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No hay pacientes en lista de espera actualmente.
                        </p>
                    ) : (
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {waitlist.map((w) => (
                                <div
                                    key={w.id}
                                    className="flex items-center justify-between rounded-xl border p-3"
                                >
                                    <div>
                                        <Link
                                            href={`/patients/${w.patient_id}`}
                                            className="text-sm font-bold hover:text-[#0E8FA3]"
                                        >
                                            {w.patient?.full_name}
                                        </Link>
                                        <div className="text-xs text-muted-foreground">
                                            {w.title} · {w.dentist?.name}
                                        </div>
                                    </div>
                                    {canManage && (
                                        <div className="flex items-center gap-1">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                className="h-8 text-xs text-[#0E8FA3]"
                                                onClick={() =>
                                                    promoteWaitlist(w)
                                                }
                                            >
                                                Confirmar
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                className="h-8 px-2 text-xs text-[#D64545]"
                                                onClick={() =>
                                                    setDeletingAppt(w)
                                                }
                                            >
                                                <Trash2 className="size-3.5" />
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            <Dialog open={openCreate} onOpenChange={setOpenCreate}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <CalendarDays className="size-5 text-[#0E8FA3]" />
                            Programar nueva cita
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleCreate} className="space-y-3">
                        <div>
                            <Label>Paciente *</Label>
                            <select
                                required
                                value={form.data.patient_id}
                                onChange={(e) =>
                                    form.setData('patient_id', e.target.value)
                                }
                                className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                            >
                                {patients.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.history_number} · {p.full_name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <Label>Motivo / Procedimiento *</Label>
                            <Input
                                required
                                value={form.data.title}
                                onChange={(e) =>
                                    form.setData('title', e.target.value)
                                }
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <Label>Odontólogo *</Label>
                                <select
                                    required
                                    value={form.data.dentist_id}
                                    onChange={(e) =>
                                        form.setData(
                                            'dentist_id',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                                >
                                    {dentists.map((d) => (
                                        <option key={d.id} value={d.id}>
                                            {d.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label>Sillón *</Label>
                                <select
                                    value={form.data.chair}
                                    onChange={(e) =>
                                        form.setData('chair', e.target.value)
                                    }
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                                >
                                    {chairs.map((c) => (
                                        <option key={c} value={c}>
                                            {c}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                        <div className="grid grid-cols-3 gap-2">
                            <div>
                                <Label>Fecha</Label>
                                <Input
                                    type="date"
                                    required
                                    value={form.data.date}
                                    onChange={(e) =>
                                        form.setData('date', e.target.value)
                                    }
                                />
                            </div>
                            <div>
                                <Label>Inicio</Label>
                                <Input
                                    type="time"
                                    required
                                    value={form.data.start_time}
                                    onChange={(e) =>
                                        form.setData(
                                            'start_time',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Fin</Label>
                                <Input
                                    type="time"
                                    required
                                    value={form.data.end_time}
                                    onChange={(e) =>
                                        form.setData('end_time', e.target.value)
                                    }
                                />
                            </div>
                        </div>
                        <label className="flex items-center gap-2 text-xs font-medium">
                            <input
                                type="checkbox"
                                checked={form.data.is_waitlist}
                                onChange={(e) =>
                                    form.setData(
                                        'is_waitlist',
                                        e.target.checked,
                                    )
                                }
                            />
                            Agregar a lista de espera (sin ocupar horario)
                        </label>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setOpenCreate(false)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                className="gap-1.5 bg-[#0E8FA3] text-white"
                            >
                                <Clock className="size-4" />
                                Guardar cita
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Editar Cita */}
            <Dialog
                open={editingAppt !== null}
                onOpenChange={(open) => !open && setEditingAppt(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            Editar cita — {editingAppt?.patient?.full_name}
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitEdit} className="space-y-3">
                        <div>
                            <Label>Motivo / Procedimiento *</Label>
                            <Input
                                required
                                value={editForm.data.title}
                                onChange={(e) =>
                                    editForm.setData('title', e.target.value)
                                }
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <Label>Odontólogo *</Label>
                                <select
                                    required
                                    value={editForm.data.dentist_id}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'dentist_id',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                                >
                                    {dentists.map((d) => (
                                        <option key={d.id} value={d.id}>
                                            {d.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label>Sillón *</Label>
                                <select
                                    value={editForm.data.chair}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'chair',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                                >
                                    {chairs.map((c) => (
                                        <option key={c} value={c}>
                                            {c}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                        <div className="grid grid-cols-3 gap-2">
                            <div>
                                <Label>Fecha</Label>
                                <Input
                                    type="date"
                                    required
                                    value={editForm.data.date}
                                    onChange={(e) =>
                                        editForm.setData('date', e.target.value)
                                    }
                                />
                            </div>
                            <div>
                                <Label>Inicio</Label>
                                <Input
                                    type="time"
                                    required
                                    value={editForm.data.start_time}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'start_time',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Fin</Label>
                                <Input
                                    type="time"
                                    required
                                    value={editForm.data.end_time}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'end_time',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>
                        <div>
                            <Label>Estado de la cita</Label>
                            <select
                                value={editForm.data.status}
                                onChange={(e) =>
                                    editForm.setData('status', e.target.value)
                                }
                                className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                            >
                                {STATUS_OPTIONS.map((opt) => (
                                    <option key={opt.value} value={opt.value}>
                                        {opt.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <Label>Notas clínicas / observación</Label>
                            <Input
                                value={editForm.data.notes}
                                onChange={(e) =>
                                    editForm.setData('notes', e.target.value)
                                }
                            />
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setEditingAppt(null)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                className="bg-[#0E8FA3] text-white"
                            >
                                Actualizar cita
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Eliminar Cita */}
            <Dialog
                open={deletingAppt !== null}
                onOpenChange={(open) => !open && setDeletingAppt(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="text-[#D64545]">
                            Eliminar cita de agenda
                        </DialogTitle>
                        <DialogDescription>
                            ¿Seguro que deseas eliminar la cita{' '}
                            <strong>{deletingAppt?.title}</strong> de{' '}
                            <strong>{deletingAppt?.patient?.full_name}</strong>?
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setDeletingAppt(null)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={confirmDeleteAppt}
                        >
                            Eliminar cita
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
