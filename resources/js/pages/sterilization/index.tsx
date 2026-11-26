import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowDownUp,
    CheckCircle2,
    FlaskConical,
    Package,
    Plus,
    ShieldCheck,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type {
    Auth,
    InventoryItem,
    InventoryMovement,
    SterilizationCycle,
} from '@/types';

type Props = {
    cycles: SterilizationCycle[];
    items: InventoryItem[];
    movements: InventoryMovement[];
    patients: {
        id: number;
        full_name: string;
        history_number: string;
    }[];
    stats: {
        cycles_month: number;
        pending_bio: number;
        low_stock: number;
        expiring_soon: number;
    };
    initialTab: string;
};

export default function SterilizationInventoryIndex({
    cycles,
    items,
    movements,
    patients,
    stats,
    initialTab,
}: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const currency = auth.clinic?.currency_symbol ?? 'S/';
    const [tab, setTab] = useState<'esterilizacion' | 'kardex'>(
        initialTab === 'kardex' ? 'kardex' : 'esterilizacion',
    );
    const [openNewCycle, setOpenNewCycle] = useState(false);
    const [openNewItem, setOpenNewItem] = useState(false);
    const [movingItem, setMovingItem] = useState<InventoryItem | null>(null);

    const cycleForm = useForm({
        autoclave_name: 'Autoclave Clase B 24L',
        cycle_number: `CICLO-${new Date().toISOString().slice(0, 10)}`,
        sterilized_at: new Date().toISOString().slice(0, 16),
        temperature_c: '134.0',
        pressure_bar: '2.10',
        duration_minutes: 35,
        load_summary: '',
        chemical_indicator: 'conforme',
        biological_indicator: 'pendiente',
        notes: '',
    });

    const itemForm = useForm({
        code: '',
        name: '',
        category: 'Anestésicos',
        unit: 'cajas',
        lot_number: '',
        expires_at: '',
        current_stock: '10',
        min_stock: '2',
        unit_cost: '0',
        supplier: '',
    });

    const moveForm = useForm({
        type: 'salida' as 'entrada' | 'salida' | 'ajuste',
        quantity: '1',
        reason: 'Consumo en procedimiento clínico',
        patient_id: '',
        lot_number: '',
        expires_at: '',
    });

    const submitCycle = (e: React.FormEvent) => {
        e.preventDefault();
        cycleForm.post('/esterilizacion-kardex/cycles', {
            preserveScroll: true,
            onSuccess: () => {
                setOpenNewCycle(false);
                cycleForm.reset('load_summary', 'notes');
            },
        });
    };

    const updateBioResult = (
        cycleId: number,
        biological_indicator:
            | 'pendiente'
            | 'negativo_conforme'
            | 'positivo_alerta',
    ) => {
        router.patch(
            `/esterilizacion-kardex/cycles/${cycleId}`,
            { biological_indicator },
            { preserveScroll: true },
        );
    };

    const deleteCycle = (id: number) => {
        router.delete(`/esterilizacion-kardex/cycles/${id}`, {
            preserveScroll: true,
        });
    };

    const submitItem = (e: React.FormEvent) => {
        e.preventDefault();
        itemForm.post('/esterilizacion-kardex/items', {
            preserveScroll: true,
            onSuccess: () => {
                setOpenNewItem(false);
                itemForm.reset('code', 'name', 'lot_number', 'supplier');
            },
        });
    };

    const submitMovement = (e: React.FormEvent) => {
        e.preventDefault();
        if (!movingItem) return;
        moveForm.post(
            `/esterilizacion-kardex/items/${movingItem.id}/movements`,
            {
                preserveScroll: true,
                onSuccess: () => {
                    setMovingItem(null);
                    moveForm.reset('quantity');
                },
            },
        );
    };

    const deleteItem = (id: number) => {
        router.delete(`/esterilizacion-kardex/items/${id}`, {
            preserveScroll: true,
        });
    };

    const todayStr = new Date().toISOString().slice(0, 10);
    const soonDate = new Date();
    soonDate.setDate(soonDate.getDate() + 30);
    const soonStr = soonDate.toISOString().slice(0, 10);

    return (
        <>
            <Head title="Esterilización y Kardex" />
            <div className="mx-auto max-w-7xl space-y-6 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-bold">
                            <ShieldCheck className="size-6 text-[#0E8FA3]" />
                            Bioseguridad: Esterilización y Kardex de Insumos
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Trazabilidad de ciclos de autoclave, indicadores
                            biológicos por esporas y lotes/vencimientos de
                            insumos críticos
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button
                            variant="outline"
                            onClick={() => setOpenNewCycle(true)}
                            className="h-10 gap-1.5 rounded-xl"
                        >
                            <Plus className="size-4 text-[#0E8FA3]" />
                            Registrar ciclo autoclave
                        </Button>
                        <Button
                            onClick={() => setOpenNewItem(true)}
                            className="h-10 gap-1.5 rounded-xl bg-[#0E8FA3] text-white hover:bg-[#0A6577]"
                        >
                            <Package className="size-4" />
                            Nuevo insumo Kardex
                        </Button>
                    </div>
                </div>

                {/* KPIs */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="rounded-2xl border bg-card p-4 shadow-xs">
                        <div className="text-xs text-muted-foreground">
                            Ciclos este mes
                        </div>
                        <div className="mt-1 text-2xl font-bold text-[#0A6577]">
                            {stats.cycles_month}
                        </div>
                    </div>
                    <div className="rounded-2xl border bg-card p-4 shadow-xs">
                        <div className="text-xs text-muted-foreground">
                            Esporas pendientes de lectura
                        </div>
                        <div
                            className={`mt-1 text-2xl font-bold ${stats.pending_bio > 0 ? 'text-[#F0A92E]' : 'text-[#2E9E6B]'}`}
                        >
                            {stats.pending_bio}
                        </div>
                    </div>
                    <div className="rounded-2xl border bg-card p-4 shadow-xs">
                        <div className="text-xs text-muted-foreground">
                            Insumos con stock crítico
                        </div>
                        <div
                            className={`mt-1 text-2xl font-bold ${stats.low_stock > 0 ? 'text-[#D64545]' : ''}`}
                        >
                            {stats.low_stock}
                        </div>
                    </div>
                    <div className="rounded-2xl border bg-card p-4 shadow-xs">
                        <div className="text-xs text-muted-foreground">
                            Lotes por vencer (≤30 días)
                        </div>
                        <div
                            className={`mt-1 text-2xl font-bold ${stats.expiring_soon > 0 ? 'text-[#D64545]' : ''}`}
                        >
                            {stats.expiring_soon}
                        </div>
                    </div>
                </div>

                {/* Pestañas */}
                <div className="flex gap-2 rounded-2xl border bg-card p-1.5 shadow-xs">
                    <button
                        type="button"
                        onClick={() => setTab('esterilizacion')}
                        className={`flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold ${
                            tab === 'esterilizacion'
                                ? 'bg-[#0E8FA3] text-white'
                                : 'text-muted-foreground hover:bg-muted/50'
                        }`}
                    >
                        <FlaskConical className="size-4" />
                        Control de Esterilización / Autoclave ({cycles.length})
                    </button>
                    <button
                        type="button"
                        onClick={() => setTab('kardex')}
                        className={`flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold ${
                            tab === 'kardex'
                                ? 'bg-[#0E8FA3] text-white'
                                : 'text-muted-foreground hover:bg-muted/50'
                        }`}
                    >
                        <Package className="size-4" />
                        Kardex de Insumos Críticos ({items.length})
                    </button>
                </div>

                {tab === 'esterilizacion' && (
                    <div className="space-y-3">
                        {cycles.length === 0 ? (
                            <div className="rounded-2xl border border-dashed bg-card p-8 text-center text-sm text-muted-foreground">
                                No hay ciclos de esterilización registrados.
                            </div>
                        ) : (
                            cycles.map((c) => (
                                <div
                                    key={c.id}
                                    className="flex flex-col justify-between gap-4 rounded-2xl border bg-card p-4 shadow-xs lg:flex-row lg:items-center"
                                >
                                    <div className="space-y-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="rounded-lg bg-[#E6F5F7] px-2 py-0.5 font-mono text-xs font-bold text-[#0A6577]">
                                                {c.cycle_number}
                                            </span>
                                            <span className="font-bold">
                                                {c.autoclave_name}
                                            </span>
                                            <Badge
                                                variant="outline"
                                                className={
                                                    c.chemical_indicator ===
                                                    'conforme'
                                                        ? 'border-[#2E9E6B] bg-[#DCFCE7] text-[#166534]'
                                                        : 'border-[#D64545] bg-[#FEE2E2] text-[#991B1B]'
                                                }
                                            >
                                                Químico:{' '}
                                                {c.chemical_indicator ===
                                                'conforme'
                                                    ? 'Conforme'
                                                    : 'Fallido'}
                                            </Badge>
                                            <Badge
                                                variant="outline"
                                                className={
                                                    c.biological_indicator ===
                                                    'negativo_conforme'
                                                        ? 'border-[#2E9E6B] bg-[#DCFCE7] text-[#166534]'
                                                        : c.biological_indicator ===
                                                            'positivo_alerta'
                                                          ? 'border-[#D64545] bg-[#D64545] text-white'
                                                          : 'border-amber-300 bg-amber-50 text-amber-900'
                                                }
                                            >
                                                Biológico:{' '}
                                                {c.biological_indicator ===
                                                'negativo_conforme'
                                                    ? 'Negativo (Estéril)'
                                                    : c.biological_indicator ===
                                                        'positivo_alerta'
                                                      ? 'POSITIVO (ALERTA)'
                                                      : 'En incubación'}
                                            </Badge>
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {new Date(
                                                c.sterilized_at,
                                            ).toLocaleString('es-PE')}{' '}
                                            · {c.temperature_c} °C ·{' '}
                                            {c.pressure_bar} bar ·{' '}
                                            {c.duration_minutes} min · Operador:{' '}
                                            {c.operator?.name ?? '-'}
                                        </div>
                                        <p className="text-xs text-foreground">
                                            <strong>Carga:</strong>{' '}
                                            {c.load_summary}
                                        </p>
                                    </div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        {c.biological_indicator ===
                                            'pendiente' && (
                                            <>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    className="h-8 gap-1 text-xs text-[#2E9E6B]"
                                                    onClick={() =>
                                                        updateBioResult(
                                                            c.id,
                                                            'negativo_conforme',
                                                        )
                                                    }
                                                >
                                                    <CheckCircle2 className="size-3.5" />
                                                    Espora Negativa OK
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    className="h-8 text-xs text-[#D64545]"
                                                    onClick={() =>
                                                        updateBioResult(
                                                            c.id,
                                                            'positivo_alerta',
                                                        )
                                                    }
                                                >
                                                    Espora Positiva
                                                </Button>
                                            </>
                                        )}
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            className="h-8 px-2 text-[#D64545]"
                                            onClick={() => deleteCycle(c.id)}
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                )}

                {tab === 'kardex' && (
                    <div className="grid gap-6 lg:grid-cols-12">
                        <div className="overflow-hidden rounded-2xl border bg-card shadow-xs lg:col-span-8">
                            <div className="border-b px-4 py-3 text-sm font-bold">
                                Inventario de Insumos y Fechas de Vencimiento
                            </div>
                            <div className="divide-y">
                                {items.map((item) => {
                                    const stock = Number(item.current_stock);
                                    const min = Number(item.min_stock);
                                    const isLow = stock <= min;
                                    const expStr = item.expires_at
                                        ? item.expires_at.slice(0, 10)
                                        : null;
                                    const isExpired =
                                        expStr !== null && expStr < todayStr;
                                    const isExpiringSoon =
                                        expStr !== null &&
                                        !isExpired &&
                                        expStr <= soonStr;

                                    return (
                                        <div
                                            key={item.id}
                                            className="flex flex-col justify-between gap-3 p-4 sm:flex-row sm:items-center"
                                        >
                                            <div className="space-y-1">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <span className="font-mono text-xs font-bold text-[#0A6577]">
                                                        {item.code}
                                                    </span>
                                                    <span className="font-bold">
                                                        {item.name}
                                                    </span>
                                                    {isLow && (
                                                        <Badge variant="destructive">
                                                            Stock crítico
                                                        </Badge>
                                                    )}
                                                    {isExpired && (
                                                        <Badge variant="destructive">
                                                            VENCIDO
                                                        </Badge>
                                                    )}
                                                    {isExpiringSoon && (
                                                        <Badge className="gap-1 bg-amber-100 text-amber-900">
                                                            <AlertTriangle className="size-3" />
                                                            Vence pronto
                                                        </Badge>
                                                    )}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {item.category} · Lote:{' '}
                                                    {item.lot_number || '-'} ·
                                                    Vence: {expStr || '-'} ·
                                                    Stock mínimo: {min}{' '}
                                                    {item.unit} · Costo:{' '}
                                                    {currency}{' '}
                                                    {Number(
                                                        item.unit_cost,
                                                    ).toFixed(2)}
                                                </div>
                                            </div>

                                            <div className="flex items-center gap-2">
                                                <div className="rounded-xl border bg-muted/30 px-3 py-1.5 text-center">
                                                    <div className="text-base font-bold text-foreground">
                                                        {stock}
                                                    </div>
                                                    <div className="text-[10px] text-muted-foreground uppercase">
                                                        {item.unit}
                                                    </div>
                                                </div>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    className="h-8 gap-1 text-xs"
                                                    onClick={() => {
                                                        setMovingItem(item);
                                                        moveForm.setData({
                                                            type: 'salida',
                                                            quantity: '1',
                                                            reason: 'Consumo clínico',
                                                            patient_id: '',
                                                            lot_number:
                                                                item.lot_number ??
                                                                '',
                                                            expires_at:
                                                                expStr ?? '',
                                                        });
                                                    }}
                                                >
                                                    <ArrowDownUp className="size-3.5 text-[#0E8FA3]" />
                                                    Movimiento
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    className="h-8 px-2 text-[#D64545]"
                                                    onClick={() =>
                                                        deleteItem(item.id)
                                                    }
                                                >
                                                    <Trash2 className="size-3.5" />
                                                </Button>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        <div className="rounded-2xl border bg-card p-4 shadow-xs lg:col-span-4">
                            <h3 className="mb-3 text-sm font-bold">
                                Últimos movimientos Kardex
                            </h3>
                            <div className="space-y-2 text-xs">
                                {movements.slice(0, 12).map((m) => (
                                    <div
                                        key={m.id}
                                        className="rounded-xl border bg-background p-2.5"
                                    >
                                        <div className="flex items-center justify-between font-semibold">
                                            <span>{m.item?.name}</span>
                                            <span
                                                className={
                                                    m.type === 'entrada'
                                                        ? 'text-[#2E9E6B]'
                                                        : 'text-[#D64545]'
                                                }
                                            >
                                                {m.type === 'entrada'
                                                    ? '+'
                                                    : '-'}
                                                {m.quantity} {m.item?.unit}
                                            </span>
                                        </div>
                                        <div className="text-muted-foreground">
                                            {m.reason}
                                            {m.patient
                                                ? ` · ${m.patient.full_name}`
                                                : ''}
                                        </div>
                                        <div className="text-[10px] text-muted-foreground">
                                            {new Date(
                                                m.moved_at,
                                            ).toLocaleString('es-PE')}{' '}
                                            · {m.user?.name}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                )}
            </div>

            {/* Modal Nuevo Ciclo Autoclave */}
            <Dialog open={openNewCycle} onOpenChange={setOpenNewCycle}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            Registrar ciclo de esterilización
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitCycle} className="space-y-3">
                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <Label>Equipo / Autoclave *</Label>
                                <Input
                                    required
                                    value={cycleForm.data.autoclave_name}
                                    onChange={(e) =>
                                        cycleForm.setData(
                                            'autoclave_name',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>N.º Ciclo *</Label>
                                <Input
                                    required
                                    value={cycleForm.data.cycle_number}
                                    onChange={(e) =>
                                        cycleForm.setData(
                                            'cycle_number',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-3 gap-2">
                            <div>
                                <Label>Temp (°C)</Label>
                                <Input
                                    type="number"
                                    step="0.1"
                                    value={cycleForm.data.temperature_c}
                                    onChange={(e) =>
                                        cycleForm.setData(
                                            'temperature_c',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Presión (bar)</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    value={cycleForm.data.pressure_bar}
                                    onChange={(e) =>
                                        cycleForm.setData(
                                            'pressure_bar',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Duración (min)</Label>
                                <Input
                                    type="number"
                                    value={cycleForm.data.duration_minutes}
                                    onChange={(e) =>
                                        cycleForm.setData(
                                            'duration_minutes',
                                            Number(e.target.value),
                                        )
                                    }
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <Label>Indicador Químico (tira)</Label>
                                <select
                                    value={cycleForm.data.chemical_indicator}
                                    onChange={(e) =>
                                        cycleForm.setData(
                                            'chemical_indicator',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-2 text-sm"
                                >
                                    <option value="conforme">
                                        Conforme (Viraje OK)
                                    </option>
                                    <option value="fallido">Fallido</option>
                                </select>
                            </div>
                            <div>
                                <Label>Indicador Biológico (esporas)</Label>
                                <select
                                    value={cycleForm.data.biological_indicator}
                                    onChange={(e) =>
                                        cycleForm.setData(
                                            'biological_indicator',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-2 text-sm"
                                >
                                    <option value="pendiente">
                                        Pendiente (En incubación)
                                    </option>
                                    <option value="negativo_conforme">
                                        Negativo (Conforme)
                                    </option>
                                    <option value="positivo_alerta">
                                        Positivo (Alerta)
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <Label>Detalle de carga / paquetes *</Label>
                            <textarea
                                required
                                rows={2}
                                placeholder="Ej. 3 kits cirugía, 6 kits diagnóstico, 2 piezas de mano"
                                value={cycleForm.data.load_summary}
                                onChange={(e) =>
                                    cycleForm.setData(
                                        'load_summary',
                                        e.target.value,
                                    )
                                }
                                className="mt-1 w-full rounded-xl border bg-background p-2.5 text-sm"
                            />
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setOpenNewCycle(false)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                className="bg-[#0E8FA3] text-white"
                            >
                                Guardar ciclo
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Nuevo Insumo */}
            <Dialog open={openNewItem} onOpenChange={setOpenNewItem}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Nuevo insumo crítico Kardex</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitItem} className="space-y-3">
                        <div className="grid grid-cols-3 gap-2">
                            <div>
                                <Label>Código *</Label>
                                <Input
                                    required
                                    placeholder="INS-01"
                                    value={itemForm.data.code}
                                    onChange={(e) =>
                                        itemForm.setData('code', e.target.value)
                                    }
                                />
                            </div>
                            <div className="col-span-2">
                                <Label>Nombre del insumo *</Label>
                                <Input
                                    required
                                    value={itemForm.data.name}
                                    onChange={(e) =>
                                        itemForm.setData('name', e.target.value)
                                    }
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <Label>Categoría *</Label>
                                <Input
                                    required
                                    value={itemForm.data.category}
                                    onChange={(e) =>
                                        itemForm.setData(
                                            'category',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Unidad *</Label>
                                <Input
                                    required
                                    value={itemForm.data.unit}
                                    onChange={(e) =>
                                        itemForm.setData('unit', e.target.value)
                                    }
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <Label>Número de Lote</Label>
                                <Input
                                    value={itemForm.data.lot_number}
                                    onChange={(e) =>
                                        itemForm.setData(
                                            'lot_number',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Fecha de vencimiento</Label>
                                <Input
                                    type="date"
                                    value={itemForm.data.expires_at}
                                    onChange={(e) =>
                                        itemForm.setData(
                                            'expires_at',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-3 gap-2">
                            <div>
                                <Label>Stock inicial *</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    required
                                    value={itemForm.data.current_stock}
                                    onChange={(e) =>
                                        itemForm.setData(
                                            'current_stock',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Stock mínimo *</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    required
                                    value={itemForm.data.min_stock}
                                    onChange={(e) =>
                                        itemForm.setData(
                                            'min_stock',
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
                                    value={itemForm.data.unit_cost}
                                    onChange={(e) =>
                                        itemForm.setData(
                                            'unit_cost',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setOpenNewItem(false)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                className="bg-[#0E8FA3] text-white"
                            >
                                Guardar insumo
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Movimiento Kardex */}
            <Dialog
                open={movingItem !== null}
                onOpenChange={(open) => !open && setMovingItem(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            Movimiento Kardex: {movingItem?.name}
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitMovement} className="space-y-3">
                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <Label>Tipo de movimiento</Label>
                                <select
                                    value={moveForm.data.type}
                                    onChange={(e) =>
                                        moveForm.setData(
                                            'type',
                                            e.target.value as
                                                | 'entrada'
                                                | 'salida'
                                                | 'ajuste',
                                        )
                                    }
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-2 text-sm"
                                >
                                    <option value="salida">
                                        Salida (Consumo)
                                    </option>
                                    <option value="entrada">
                                        Entrada (Compra / Reposición)
                                    </option>
                                    <option value="ajuste">
                                        Ajuste de inventario exacto
                                    </option>
                                </select>
                            </div>
                            <div>
                                <Label>Cantidad ({movingItem?.unit}) *</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    required
                                    value={moveForm.data.quantity}
                                    onChange={(e) =>
                                        moveForm.setData(
                                            'quantity',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>
                        <div>
                            <Label>Motivo / Procedimiento *</Label>
                            <Input
                                required
                                value={moveForm.data.reason}
                                onChange={(e) =>
                                    moveForm.setData('reason', e.target.value)
                                }
                            />
                        </div>
                        <div>
                            <Label>Asociar a paciente (opcional)</Label>
                            <select
                                value={moveForm.data.patient_id}
                                onChange={(e) =>
                                    moveForm.setData(
                                        'patient_id',
                                        e.target.value,
                                    )
                                }
                                className="mt-1 h-10 w-full rounded-md border bg-background px-2 text-sm"
                            >
                                <option value="">Uso general / interno</option>
                                {patients.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.history_number} · {p.full_name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setMovingItem(null)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                className="bg-[#0E8FA3] text-white"
                            >
                                Registrar movimiento
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
