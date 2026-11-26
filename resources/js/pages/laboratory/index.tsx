import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    FlaskConical,
    Pencil,
    Plus,
    Search,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
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
import type { Auth, LabOrder } from '@/types';

type Props = {
    orders: LabOrder[];
    stats: {
        active: number;
        overdue: number;
        urgent_appointments: number;
        ready: number;
    };
    patients: {
        id: number;
        first_name: string;
        last_name: string;
        full_name: string;
        history_number: string;
    }[];
    dentists: { id: number; name: string }[];
    upcomingAppointments: {
        id: number;
        patient_id: number;
        title: string;
        starts_at: string;
        patient?: { full_name: string; history_number: string } | null;
    }[];
    filters: { status: string; search: string };
};

const LAB_STATUS_LABELS: Record<string, { label: string; style: string }> = {
    por_enviar: {
        label: 'Por enviar',
        style: 'bg-slate-100 text-slate-700 border-slate-300',
    },
    en_laboratorio: {
        label: 'En laboratorio',
        style: 'bg-[#DBEAFE] text-[#1E40AF] border-[#93C5FD]',
    },
    prueba_clinica: {
        label: 'En prueba clínica',
        style: 'bg-[#FEF3C7] text-[#92400E] border-[#FCD34D]',
    },
    recibido: {
        label: 'Recibido (Listo)',
        style: 'bg-[#DCFCE7] text-[#166534] border-[#86EFAC]',
    },
    entregado: {
        label: 'Instalado / Entregado',
        style: 'bg-[#E6F5F7] text-[#0A6577] border-[#0E8FA3]/40',
    },
    rehacer: {
        label: 'Rehacer / Ajuste',
        style: 'bg-[#FEE2E2] text-[#991B1B] border-[#FCA5A5]',
    },
};

export default function LaboratoryIndex({
    orders,
    stats,
    patients,
    dentists,
    upcomingAppointments,
    filters,
}: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const currency = auth.clinic?.currency_symbol ?? 'S/';
    const [search, setSearch] = useState(filters.search);
    const [openCreate, setOpenCreate] = useState(false);
    const [editingOrder, setEditingOrder] = useState<LabOrder | null>(null);
    const [deletingOrder, setDeletingOrder] = useState<LabOrder | null>(null);

    const form = useForm({
        patient_id: String(patients[0]?.id ?? ''),
        dentist_id: String(dentists[0]?.id ?? ''),
        appointment_id: '',
        lab_name: '',
        work_type: 'Corona de Zirconia',
        tooth_code: '',
        shade: 'A2',
        material: 'Zirconia multicapa',
        sent_at: new Date().toISOString().slice(0, 10),
        due_date: '',
        cost: '250.00',
        status: 'en_laboratorio',
        notes: '',
    });

    const editForm = useForm({
        lab_name: '',
        work_type: '',
        tooth_code: '',
        shade: '',
        material: '',
        due_date: '',
        cost: '0.00',
        status: 'en_laboratorio',
        notes: '',
    });

    const filterBy = (nextStatus: string) => {
        router.get(
            '/laboratorio',
            { status: nextStatus, search },
            { preserveState: true },
        );
    };

    const submitCreate = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/laboratorio', {
            preserveScroll: true,
            onSuccess: () => {
                setOpenCreate(false);
                form.reset('lab_name', 'tooth_code', 'notes');
            },
        });
    };

    const openEditModal = (order: LabOrder) => {
        setEditingOrder(order);
        editForm.setData({
            lab_name: order.lab_name,
            work_type: order.work_type,
            tooth_code: order.tooth_code ?? '',
            shade: order.shade ?? '',
            material: order.material ?? '',
            due_date: order.due_date ? order.due_date.slice(0, 10) : '',
            cost: String(order.cost),
            status: order.status,
            notes: order.notes ?? '',
        });
    };

    const submitEdit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingOrder) return;
        editForm.patch(`/laboratorio/${editingOrder.id}`, {
            preserveScroll: true,
            onSuccess: () => setEditingOrder(null),
        });
    };

    const quickStatus = (order: LabOrder, status: string) => {
        router.patch(
            `/laboratorio/${order.id}`,
            { status },
            { preserveScroll: true },
        );
    };

    const confirmDelete = () => {
        if (!deletingOrder) return;
        router.delete(`/laboratorio/${deletingOrder.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeletingOrder(null),
        });
    };

    return (
        <>
            <Head title="Laboratorio dental" />
            <div className="mx-auto max-w-7xl space-y-6 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-bold">
                            <FlaskConical className="size-6 text-[#0E8FA3]" />
                            Laboratorio Dental
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Seguimiento de prótesis, coronas, carillas, férulas
                            y alineadores con control de entrega por cita
                        </p>
                    </div>
                    <Button
                        onClick={() => setOpenCreate(true)}
                        className="h-11 gap-2 rounded-xl bg-[#0E8FA3] px-5 text-white hover:bg-[#0A6577]"
                    >
                        <Plus className="size-4" />
                        Nueva orden de laboratorio
                    </Button>
                </div>

                {/* KPIs */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="rounded-2xl border bg-card p-4 shadow-xs">
                        <div className="text-xs text-muted-foreground">
                            Trabajos activos
                        </div>
                        <div className="mt-1 text-2xl font-bold text-[#0A6577]">
                            {stats.active}
                        </div>
                    </div>
                    <div className="rounded-2xl border bg-card p-4 shadow-xs">
                        <div className="text-xs text-muted-foreground">
                            Listos para instalar
                        </div>
                        <div className="mt-1 text-2xl font-bold text-[#2E9E6B]">
                            {stats.ready}
                        </div>
                    </div>
                    <div className="rounded-2xl border bg-card p-4 shadow-xs">
                        <div className="text-xs text-muted-foreground">
                            Con entrega vencida
                        </div>
                        <div
                            className={`mt-1 text-2xl font-bold ${stats.overdue > 0 ? 'text-[#D64545]' : ''}`}
                        >
                            {stats.overdue}
                        </div>
                    </div>
                    <div className="rounded-2xl border bg-card p-4 shadow-xs">
                        <div className="text-xs text-muted-foreground">
                            Citas próximas sin trabajo recibido
                        </div>
                        <div
                            className={`mt-1 text-2xl font-bold ${stats.urgent_appointments > 0 ? 'text-[#F0A92E]' : ''}`}
                        >
                            {stats.urgent_appointments}
                        </div>
                    </div>
                </div>

                {/* Filtros */}
                <div className="space-y-3 rounded-2xl border bg-card p-4 shadow-xs">
                    <div className="flex gap-2">
                        <div className="relative flex-1">
                            <Search className="pointer-events-none absolute top-3 left-3 size-4 text-muted-foreground" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Buscar por paciente, código LAB, laboratorio o trabajo..."
                                className="h-10 rounded-xl pl-9"
                            />
                        </div>
                        <Button
                            variant="outline"
                            className="h-10 rounded-xl"
                            onClick={() => filterBy(filters.status)}
                        >
                            Buscar
                        </Button>
                    </div>
                    <div className="flex flex-wrap gap-1.5">
                        <button
                            type="button"
                            onClick={() => filterBy('')}
                            className={`rounded-full border px-3 py-1 text-xs font-medium ${
                                filters.status === ''
                                    ? 'border-[#0E8FA3] bg-[#0E8FA3] text-white'
                                    : 'bg-background text-muted-foreground'
                            }`}
                        >
                            Todos
                        </button>
                        {Object.entries(LAB_STATUS_LABELS).map(([k, meta]) => (
                            <button
                                key={k}
                                type="button"
                                onClick={() => filterBy(k)}
                                className={`rounded-full border px-3 py-1 text-xs font-medium ${
                                    filters.status === k
                                        ? 'border-[#0E8FA3] bg-[#0E8FA3] text-white'
                                        : 'bg-background text-muted-foreground'
                                }`}
                            >
                                {meta.label}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Lista de órdenes */}
                <div className="space-y-3">
                    {orders.length === 0 ? (
                        <div className="rounded-2xl border border-dashed bg-card p-10 text-center text-sm text-muted-foreground">
                            No hay órdenes de laboratorio con los filtros
                            actuales.
                        </div>
                    ) : (
                        orders.map((order) => {
                            const st =
                                LAB_STATUS_LABELS[order.status] ??
                                LAB_STATUS_LABELS.en_laboratorio;
                            const isPending = [
                                'por_enviar',
                                'en_laboratorio',
                                'rehacer',
                            ].includes(order.status);
                            const isOverdue =
                                isPending &&
                                order.due_date !== null &&
                                order.due_date.slice(0, 10) <
                                    new Date().toISOString().slice(0, 10);

                            return (
                                <div
                                    key={order.id}
                                    className="flex flex-col justify-between gap-4 rounded-2xl border bg-card p-4 shadow-xs lg:flex-row lg:items-center"
                                >
                                    <div className="space-y-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="rounded-lg bg-[#E6F5F7] px-2 py-0.5 font-mono text-xs font-bold text-[#0A6577]">
                                                {order.order_code}
                                            </span>
                                            <span className="text-base font-bold">
                                                {order.work_type}
                                            </span>
                                            <Badge
                                                variant="outline"
                                                className={st.style}
                                            >
                                                {st.label}
                                            </Badge>
                                            {isOverdue && (
                                                <Badge className="gap-1 bg-[#D64545] text-white">
                                                    <AlertTriangle className="size-3" />
                                                    Entrega vencida
                                                </Badge>
                                            )}
                                        </div>
                                        <div className="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                                            <span>
                                                Paciente:{' '}
                                                <Link
                                                    href={`/patients/${order.patient_id}`}
                                                    className="font-semibold text-foreground hover:text-[#0E8FA3]"
                                                >
                                                    {order.patient?.full_name} (
                                                    {
                                                        order.patient
                                                            ?.history_number
                                                    }
                                                    )
                                                </Link>
                                            </span>
                                            <span>
                                                Laboratorio:{' '}
                                                <strong>
                                                    {order.lab_name}
                                                </strong>
                                            </span>
                                            {order.tooth_code && (
                                                <span>
                                                    Pieza:{' '}
                                                    <strong>
                                                        {order.tooth_code}
                                                    </strong>
                                                </span>
                                            )}
                                            {order.shade && (
                                                <span>
                                                    Color Vita:{' '}
                                                    <strong>
                                                        {order.shade}
                                                    </strong>
                                                </span>
                                            )}
                                            <span>
                                                Entrega est.:{' '}
                                                <strong>
                                                    {order.due_date
                                                        ? order.due_date.slice(
                                                              0,
                                                              10,
                                                          )
                                                        : '-'}
                                                </strong>
                                            </span>
                                            <span>
                                                Costo: {currency}{' '}
                                                {Number(order.cost).toFixed(2)}
                                            </span>
                                        </div>
                                        {order.notes && (
                                            <p className="text-xs text-muted-foreground">
                                                {order.notes}
                                            </p>
                                        )}
                                    </div>

                                    <div className="flex flex-wrap items-center gap-2">
                                        {order.status !== 'recibido' &&
                                            order.status !== 'entregado' && (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    className="h-8 gap-1 text-xs text-[#2E9E6B]"
                                                    onClick={() =>
                                                        quickStatus(
                                                            order,
                                                            'recibido',
                                                        )
                                                    }
                                                >
                                                    <CheckCircle2 className="size-3.5" />
                                                    Marcar recibido
                                                </Button>
                                            )}
                                        {order.status === 'recibido' && (
                                            <Button
                                                size="sm"
                                                className="h-8 gap-1 bg-[#0E8FA3] text-xs text-white"
                                                onClick={() =>
                                                    quickStatus(
                                                        order,
                                                        'entregado',
                                                    )
                                                }
                                            >
                                                Instalado en boca
                                            </Button>
                                        )}
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            className="h-8 px-2.5 text-xs"
                                            onClick={() => openEditModal(order)}
                                        >
                                            <Pencil className="size-3.5" />
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            className="h-8 px-2.5 text-xs text-[#D64545]"
                                            onClick={() =>
                                                setDeletingOrder(order)
                                            }
                                        >
                                            <Trash2 className="size-3.5" />
                                        </Button>
                                    </div>
                                </div>
                            );
                        })
                    )}
                </div>
            </div>

            {/* Modal Nueva Orden */}
            <Dialog open={openCreate} onOpenChange={setOpenCreate}>
                <DialogContent className="max-h-[90vh] max-w-xl overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Nueva orden de laboratorio</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitCreate} className="space-y-3">
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div>
                                <Label>Paciente *</Label>
                                <select
                                    required
                                    value={form.data.patient_id}
                                    onChange={(e) =>
                                        form.setData(
                                            'patient_id',
                                            e.target.value,
                                        )
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
                                <Label>Odontólogo solicitante</Label>
                                <select
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
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2">
                            <div>
                                <Label>Laboratorio dental *</Label>
                                <Input
                                    required
                                    placeholder="Ej. Laboratorio NovaDent"
                                    value={form.data.lab_name}
                                    onChange={(e) =>
                                        form.setData('lab_name', e.target.value)
                                    }
                                />
                            </div>
                            <div>
                                <Label>Tipo de trabajo *</Label>
                                <Input
                                    required
                                    value={form.data.work_type}
                                    onChange={(e) =>
                                        form.setData(
                                            'work_type',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-3 gap-2">
                            <div>
                                <Label>Pieza / Arcada</Label>
                                <Input
                                    placeholder="46"
                                    value={form.data.tooth_code}
                                    onChange={(e) =>
                                        form.setData(
                                            'tooth_code',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Color Vita</Label>
                                <Input
                                    placeholder="A2"
                                    value={form.data.shade}
                                    onChange={(e) =>
                                        form.setData('shade', e.target.value)
                                    }
                                />
                            </div>
                            <div>
                                <Label>Material</Label>
                                <Input
                                    value={form.data.material}
                                    onChange={(e) =>
                                        form.setData('material', e.target.value)
                                    }
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-3 gap-2">
                            <div>
                                <Label>Fecha envío</Label>
                                <Input
                                    type="date"
                                    value={form.data.sent_at}
                                    onChange={(e) =>
                                        form.setData('sent_at', e.target.value)
                                    }
                                />
                            </div>
                            <div>
                                <Label>Entrega prometida</Label>
                                <Input
                                    type="date"
                                    value={form.data.due_date}
                                    onChange={(e) =>
                                        form.setData('due_date', e.target.value)
                                    }
                                />
                            </div>
                            <div>
                                <Label>Costo ({currency})</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    value={form.data.cost}
                                    onChange={(e) =>
                                        form.setData('cost', e.target.value)
                                    }
                                />
                            </div>
                        </div>

                        {upcomingAppointments.length > 0 && (
                            <div>
                                <Label>
                                    Vincular a próxima cita (alerta si no llega)
                                </Label>
                                <select
                                    value={form.data.appointment_id}
                                    onChange={(e) =>
                                        form.setData(
                                            'appointment_id',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                                >
                                    <option value="">Sin vínculo</option>
                                    {upcomingAppointments.map((ap) => (
                                        <option key={ap.id} value={ap.id}>
                                            {ap.starts_at.slice(0, 16)} ·{' '}
                                            {ap.patient?.full_name} ({ap.title})
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}

                        <div>
                            <Label>Indicaciones al técnico dental</Label>
                            <textarea
                                rows={2}
                                value={form.data.notes}
                                onChange={(e) =>
                                    form.setData('notes', e.target.value)
                                }
                                className="mt-1 w-full rounded-xl border bg-background p-2.5 text-sm"
                            />
                        </div>

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
                                className="bg-[#0E8FA3] text-white"
                            >
                                Registrar orden
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Editar Orden */}
            <Dialog
                open={editingOrder !== null}
                onOpenChange={(open) => !open && setEditingOrder(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            Editar orden {editingOrder?.order_code}
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitEdit} className="space-y-3">
                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <Label>Laboratorio</Label>
                                <Input
                                    required
                                    value={editForm.data.lab_name}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'lab_name',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Estado</Label>
                                <select
                                    value={editForm.data.status}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'status',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-2 text-sm"
                                >
                                    {Object.entries(LAB_STATUS_LABELS).map(
                                        ([k, meta]) => (
                                            <option key={k} value={k}>
                                                {meta.label}
                                            </option>
                                        ),
                                    )}
                                </select>
                            </div>
                        </div>
                        <div className="grid grid-cols-3 gap-2">
                            <div>
                                <Label>Color Vita</Label>
                                <Input
                                    value={editForm.data.shade}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'shade',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Entrega est.</Label>
                                <Input
                                    type="date"
                                    value={editForm.data.due_date}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'due_date',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Costo ({currency})</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    value={editForm.data.cost}
                                    onChange={(e) =>
                                        editForm.setData('cost', e.target.value)
                                    }
                                />
                            </div>
                        </div>
                        <div>
                            <Label>Notas / observaciones</Label>
                            <textarea
                                rows={2}
                                value={editForm.data.notes}
                                onChange={(e) =>
                                    editForm.setData('notes', e.target.value)
                                }
                                className="mt-1 w-full rounded-xl border bg-background p-2.5 text-sm"
                            />
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setEditingOrder(null)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                className="bg-[#0E8FA3] text-white"
                            >
                                Guardar cambios
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Confirmar Eliminar Orden */}
            <Dialog
                open={deletingOrder !== null}
                onOpenChange={(open) => !open && setDeletingOrder(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Eliminar orden de laboratorio</DialogTitle>
                        <DialogDescription>
                            ¿Seguro que deseas eliminar la orden{' '}
                            <strong>{deletingOrder?.order_code}</strong>?
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setDeletingOrder(null)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={confirmDelete}
                        >
                            Eliminar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
