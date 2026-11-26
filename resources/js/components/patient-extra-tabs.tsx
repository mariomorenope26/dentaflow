import { router, useForm, usePage } from '@inertiajs/react';
import {
    Activity,
    AlertTriangle,
    ArrowLeftRight,
    Ban,
    CalendarClock,
    Camera,
    CheckCircle2,
    FileCode2,
    FileDown,
    FileSignature,
    Pencil,
    Plus,
    Printer,
    Ruler,
    Search,
    Trash2,
    Wallet,
} from 'lucide-react';
import { useRef, useState } from 'react';
import { toast } from 'sonner';
import { RadiographViewerModal } from '@/components/radiograph-viewer-modal';
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
    ClinicalDocument,
    FinancialSummary,
    OrthodonticControl,
    Patient,
    PatientImage,
    Payment,
    PaymentInstallment,
    PeriodontalToothEntry,
    Periodontogram,
    TreatmentPlan,
} from '@/types';

const PERIO_TEETH = [
    '18',
    '17',
    '16',
    '15',
    '14',
    '13',
    '12',
    '11',
    '21',
    '22',
    '23',
    '24',
    '25',
    '26',
    '27',
    '28',
    '48',
    '47',
    '46',
    '45',
    '44',
    '43',
    '42',
    '41',
    '31',
    '32',
    '33',
    '34',
    '35',
    '36',
    '37',
    '38',
];

function SignaturePad({ onChange }: { onChange: (dataUrl: string) => void }) {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const drawing = useRef(false);

    const getCoords = (e: React.PointerEvent<HTMLCanvasElement>) => {
        const canvas = canvasRef.current;
        if (!canvas) return { x: 0, y: 0 };
        const rect = canvas.getBoundingClientRect();
        return {
            x: ((e.clientX - rect.left) / rect.width) * canvas.width,
            y: ((e.clientY - rect.top) / rect.height) * canvas.height,
        };
    };

    const startDraw = (e: React.PointerEvent<HTMLCanvasElement>) => {
        const canvas = canvasRef.current;
        const ctx = canvas?.getContext('2d');
        if (!canvas || !ctx) return;
        drawing.current = true;
        const { x, y } = getCoords(e);
        ctx.beginPath();
        ctx.moveTo(x, y);
        ctx.lineWidth = 2.4;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#1F2D3A';
    };

    const moveDraw = (e: React.PointerEvent<HTMLCanvasElement>) => {
        if (!drawing.current) return;
        const canvas = canvasRef.current;
        const ctx = canvas?.getContext('2d');
        if (!canvas || !ctx) return;
        const { x, y } = getCoords(e);
        ctx.lineTo(x, y);
        ctx.stroke();
    };

    const endDraw = () => {
        if (!drawing.current) return;
        drawing.current = false;
        const canvas = canvasRef.current;
        if (canvas) {
            onChange(canvas.toDataURL('image/png'));
        }
    };

    const clear = () => {
        const canvas = canvasRef.current;
        const ctx = canvas?.getContext('2d');
        if (!canvas || !ctx) return;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        onChange('');
    };

    return (
        <div className="space-y-1.5">
            <div className="flex items-center justify-between">
                <Label className="text-xs font-semibold">
                    Firma digital en pantalla (dedo, lápiz o mouse)
                </Label>
                <button
                    type="button"
                    onClick={clear}
                    className="text-xs font-semibold text-[#D64545]"
                >
                    Limpiar firma
                </button>
            </div>
            <canvas
                ref={canvasRef}
                width={480}
                height={150}
                onPointerDown={startDraw}
                onPointerMove={moveDraw}
                onPointerUp={endDraw}
                onPointerLeave={endDraw}
                className="w-full touch-none rounded-xl border bg-white shadow-inner"
            />
        </div>
    );
}

export function PaymentsTab({
    patient,
    payments,
    plans,
    summary,
    canManageCash,
}: {
    patient: Patient;
    payments: Payment[];
    plans: TreatmentPlan[];
    summary: FinancialSummary;
    canManageCash: boolean;
}) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const currency = auth.clinic?.currency_symbol ?? 'S/';
    const [openPay, setOpenPay] = useState(false);
    const [lookingUpRuc, setLookingUpRuc] = useState(false);
    const [openInstallments, setOpenInstallments] = useState(false);
    const [payingQuota, setPayingQuota] = useState<PaymentInstallment | null>(
        null,
    );

    const installments = patient.payment_installments ?? [];

    const form = useForm({
        amount: summary.balance_due > 0 ? String(summary.balance_due) : '',
        payment_method: 'yape_plin',
        document_type: 'boleta',
        customer_doc_type: '1',
        customer_doc_number: '',
        customer_name: '',
        customer_address: '',
        concept: `Abono atención dental · ${patient.history_number}`,
        treatment_plan_id: plans[0]?.id ? String(plans[0].id) : '',
        notes: '',
    });

    const quotaGenForm = useForm({
        treatment_plan_id: plans[0]?.id ? String(plans[0].id) : '',
        total_amount:
            summary.balance_due > 0 ? String(summary.balance_due) : '900',
        down_payment: '0',
        installments_count: '3',
        first_due_date: new Date().toISOString().slice(0, 10),
        frequency_days: '30',
        notes: 'Financiamiento acordado con el paciente',
    });

    const quotaPayForm = useForm({
        amount: '',
        payment_method: 'yape_plin',
        document_type: 'boleta',
    });

    const lookupRuc = async () => {
        const clean = form.data.customer_doc_number.replace(/\D/g, '');
        if (!/^\d{11}$/.test(clean)) {
            toast.error('Ingresa un RUC válido de 11 dígitos.');
            return;
        }
        setLookingUpRuc(true);
        try {
            const xsrf = decodeURIComponent(
                document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '',
            );
            const res = await fetch('/api-integrations/ruc-lookup', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': xsrf,
                },
                body: JSON.stringify({ ruc: clean }),
            });
            const body = await res.json();
            if (!res.ok) {
                toast.error(body.message ?? 'No se pudo consultar el RUC.');
                return;
            }
            form.setData((prev) => ({
                ...prev,
                customer_doc_type: '6',
                customer_doc_number: clean,
                customer_name: body.data.name || prev.customer_name,
                customer_address: body.data.address || prev.customer_address,
            }));
            toast.success(`RUC SUNAT: ${body.data.name}`);
        } catch {
            toast.error('Error al consultar RUC.');
        } finally {
            setLookingUpRuc(false);
        }
    };

    const submitInstallments = (e: React.FormEvent) => {
        e.preventDefault();
        quotaGenForm.post(`/patients/${patient.id}/installments/generate`, {
            preserveScroll: true,
            onSuccess: () => setOpenInstallments(false),
        });
    };

    const submitQuotaPay = (e: React.FormEvent) => {
        e.preventDefault();
        if (!payingQuota) return;
        quotaPayForm.post(
            `/patients/${patient.id}/installments/${payingQuota.id}/pay`,
            {
                preserveScroll: true,
                onSuccess: () => setPayingQuota(null),
            },
        );
    };

    const deleteInstallment = (id: number) => {
        router.delete(`/patients/${patient.id}/installments/${id}`, {
            preserveScroll: true,
        });
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(`/patients/${patient.id}/payments`, {
            preserveScroll: true,
            onSuccess: () => {
                setOpenPay(false);
                form.reset();
            },
        });
    };

    const voidPayment = (id: number) => {
        router.patch(`/payments/${id}/void`, {}, { preserveScroll: true });
    };

    return (
        <div className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-3">
                <div className="rounded-2xl border bg-card p-4 shadow-xs">
                    <div className="text-xs text-muted-foreground">
                        Total presupuestado
                    </div>
                    <div className="mt-1 text-2xl font-bold">
                        {currency} {summary.total_budgeted.toFixed(2)}
                    </div>
                </div>
                <div className="rounded-2xl border bg-card p-4 shadow-xs">
                    <div className="text-xs text-muted-foreground">
                        Total pagado
                    </div>
                    <div className="mt-1 text-2xl font-bold text-[#2E9E6B]">
                        {currency} {summary.total_paid.toFixed(2)}
                    </div>
                </div>
                <div className="rounded-2xl border bg-card p-4 shadow-xs">
                    <div className="text-xs text-muted-foreground">
                        Saldo pendiente
                    </div>
                    <div
                        className={`mt-1 text-2xl font-bold ${
                            summary.balance_due > 0
                                ? 'text-[#D64545]'
                                : 'text-[#2E9E6B]'
                        }`}
                    >
                        {currency} {summary.balance_due.toFixed(2)}
                    </div>
                </div>
            </div>

            <div className="rounded-2xl border bg-card p-5 shadow-xs">
                <div className="mb-4 flex items-center justify-between">
                    <h3 className="text-base font-bold">
                        Cuenta corriente y comprobantes
                    </h3>
                    {canManageCash && (
                        <Button
                            onClick={() => setOpenPay(true)}
                            className="h-10 gap-1.5 rounded-xl bg-[#0E8FA3] text-xs text-white hover:bg-[#0A6577]"
                        >
                            <Wallet className="size-4" />
                            Registrar pago
                        </Button>
                    )}
                </div>

                {payments.length === 0 ? (
                    <p className="py-6 text-center text-sm text-muted-foreground">
                        Sin pagos registrados aún para este paciente.
                    </p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b text-xs text-muted-foreground">
                                <tr>
                                    <th className="py-2 pr-3">Comprobante</th>
                                    <th className="py-2 pr-3">Fecha</th>
                                    <th className="py-2 pr-3">Concepto</th>
                                    <th className="py-2 pr-3">Medio</th>
                                    <th className="py-2 pr-3">Monto</th>
                                    <th className="py-2 text-right">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {payments.map((p) => (
                                    <tr
                                        key={p.id}
                                        className={
                                            p.status === 'anulado'
                                                ? 'opacity-50'
                                                : ''
                                        }
                                    >
                                        <td className="py-2.5 pr-3 font-mono text-xs font-bold">
                                            {p.receipt_number}
                                        </td>
                                        <td className="py-2.5 pr-3 text-xs">
                                            {new Date(p.paid_at).toLocaleString(
                                                'es-PE',
                                            )}
                                        </td>
                                        <td className="py-2.5 pr-3">
                                            {p.concept}
                                        </td>
                                        <td className="py-2.5 pr-3 text-xs uppercase">
                                            {p.payment_method.replace(
                                                '_',
                                                ' / ',
                                            )}
                                        </td>
                                        <td className="py-2.5 pr-3 font-bold">
                                            {currency}{' '}
                                            {Number(p.amount).toFixed(2)}
                                        </td>
                                        <td className="py-2.5 text-right">
                                            <div className="inline-flex items-center gap-1.5">
                                                <a
                                                    href={`/payments/${p.id}/thermal`}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="inline-flex h-7 items-center gap-1 rounded-lg border border-[#0E8FA3]/40 bg-[#E6F5F7]/60 px-2 text-xs font-semibold text-[#0A6577] hover:bg-[#E6F5F7]"
                                                >
                                                    <Printer className="size-3" />
                                                    Ticket
                                                </a>
                                                <a
                                                    href={`/payments/${p.id}/pdf`}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="inline-flex h-7 items-center gap-1 rounded-lg border px-2 text-xs hover:bg-accent"
                                                >
                                                    <FileDown className="size-3" />
                                                    PDF
                                                </a>
                                                {p.document_type !==
                                                    'recibo' && (
                                                    <a
                                                        href={`/payments/${p.id}/ubl-xml`}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="inline-flex h-7 items-center gap-1 rounded-lg border px-2 text-xs hover:bg-accent"
                                                    >
                                                        <FileCode2 className="size-3 text-[#0E8FA3]" />
                                                        UBL
                                                    </a>
                                                )}
                                                {canManageCash &&
                                                    p.status ===
                                                        'registrado' && (
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="ghost"
                                                            className="h-7 text-xs text-[#D64545]"
                                                            onClick={() =>
                                                                voidPayment(
                                                                    p.id,
                                                                )
                                                            }
                                                        >
                                                            <Ban className="mr-1 size-3" />
                                                            Anular
                                                        </Button>
                                                    )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {/* Cronograma de Cuotas y Financiamiento */}
            <div className="rounded-2xl border bg-card p-5 shadow-xs">
                <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 className="flex items-center gap-2 text-base font-bold">
                            <CalendarClock className="size-4 text-[#0E8FA3]" />
                            Plan de Financiamiento por Cuotas
                        </h3>
                        <p className="text-xs text-muted-foreground">
                            Control de vencimientos y cobranza progresiva para
                            ortodoncia, prótesis o implantes
                        </p>
                    </div>
                    {canManageCash && (
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpenInstallments(true)}
                            className="h-9 gap-1.5 rounded-xl text-xs"
                        >
                            <Plus className="size-3.5 text-[#0E8FA3]" />
                            Programar cuotas
                        </Button>
                    )}
                </div>

                {installments.length === 0 ? (
                    <p className="text-xs text-muted-foreground">
                        Sin cronograma de cuotas programado aún.
                    </p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead className="border-b text-muted-foreground">
                                <tr>
                                    <th className="py-2 pr-3">Cuota</th>
                                    <th className="py-2 pr-3">Vencimiento</th>
                                    <th className="py-2 pr-3">Importe</th>
                                    <th className="py-2 pr-3">Pagado</th>
                                    <th className="py-2 pr-3">Estado</th>
                                    <th className="py-2 text-right">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {installments.map((inst) => {
                                    const todayIso = new Date()
                                        .toISOString()
                                        .slice(0, 10);
                                    const dueIso = inst.due_date.slice(0, 10);
                                    const isOverdue =
                                        inst.status !== 'pagado' &&
                                        dueIso < todayIso;
                                    const remaining = Math.max(
                                        0,
                                        Number(inst.amount) -
                                            Number(inst.paid_amount),
                                    );
                                    return (
                                        <tr key={inst.id}>
                                            <td className="py-2.5 pr-3 font-bold">
                                                Cuota N.º{' '}
                                                {inst.installment_number}
                                            </td>
                                            <td className="py-2.5 pr-3">
                                                {dueIso}
                                            </td>
                                            <td className="py-2.5 pr-3 font-semibold">
                                                {currency}{' '}
                                                {Number(inst.amount).toFixed(2)}
                                            </td>
                                            <td className="py-2.5 pr-3 text-[#2E9E6B]">
                                                {currency}{' '}
                                                {Number(
                                                    inst.paid_amount,
                                                ).toFixed(2)}
                                            </td>
                                            <td className="py-2.5 pr-3">
                                                {inst.status === 'pagado' ? (
                                                    <Badge className="bg-[#DCFCE7] text-[#166534]">
                                                        Pagado
                                                    </Badge>
                                                ) : isOverdue ? (
                                                    <Badge className="gap-1 bg-[#D64545] text-white">
                                                        <AlertTriangle className="size-3" />
                                                        Vencida
                                                    </Badge>
                                                ) : (
                                                    <Badge
                                                        variant="outline"
                                                        className="capitalize"
                                                    >
                                                        {inst.status}
                                                    </Badge>
                                                )}
                                            </td>
                                            <td className="py-2.5 text-right">
                                                <div className="inline-flex items-center gap-1">
                                                    {canManageCash &&
                                                        inst.status !==
                                                            'pagado' && (
                                                            <>
                                                                <Button
                                                                    type="button"
                                                                    size="sm"
                                                                    variant="outline"
                                                                    className="h-7 px-2 text-xs text-[#0E8FA3]"
                                                                    onClick={() => {
                                                                        setPayingQuota(
                                                                            inst,
                                                                        );
                                                                        quotaPayForm.setData(
                                                                            {
                                                                                amount: remaining.toFixed(
                                                                                    2,
                                                                                ),
                                                                                payment_method:
                                                                                    'yape_plin',
                                                                                document_type:
                                                                                    'boleta',
                                                                            },
                                                                        );
                                                                    }}
                                                                >
                                                                    Cobrar
                                                                </Button>
                                                                <Button
                                                                    type="button"
                                                                    size="sm"
                                                                    variant="ghost"
                                                                    className="h-7 px-1.5 text-[#D64545]"
                                                                    onClick={() =>
                                                                        deleteInstallment(
                                                                            inst.id,
                                                                        )
                                                                    }
                                                                >
                                                                    <Trash2 className="size-3.5" />
                                                                </Button>
                                                            </>
                                                        )}
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            <Dialog open={openPay} onOpenChange={setOpenPay}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Registrar pago del paciente</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submit} className="space-y-3">
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label>Monto ({currency}) *</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    min="0.50"
                                    required
                                    value={form.data.amount}
                                    onChange={(e) =>
                                        form.setData('amount', e.target.value)
                                    }
                                />
                            </div>
                            <div>
                                <Label>Comprobante</Label>
                                <select
                                    value={form.data.document_type}
                                    onChange={(e) =>
                                        form.setData(
                                            'document_type',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                                >
                                    <option value="recibo">
                                        Recibo interno
                                    </option>
                                    <option value="boleta">
                                        Boleta de venta
                                    </option>
                                    <option value="factura">Factura</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <Label>Medio de pago</Label>
                            <select
                                value={form.data.payment_method}
                                onChange={(e) =>
                                    form.setData(
                                        'payment_method',
                                        e.target.value,
                                    )
                                }
                                className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                            >
                                <option value="efectivo">Efectivo</option>
                                <option value="yape_plin">Yape / Plin</option>
                                <option value="tarjeta">Tarjeta (POS)</option>
                                <option value="transferencia">
                                    Transferencia bancaria
                                </option>
                            </select>
                        </div>
                        <div>
                            <Label>Concepto *</Label>
                            <Input
                                required
                                value={form.data.concept}
                                onChange={(e) =>
                                    form.setData('concept', e.target.value)
                                }
                            />
                        </div>
                        {plans.length > 0 && (
                            <div>
                                <Label>Asociar a plan de tratamiento</Label>
                                <select
                                    value={form.data.treatment_plan_id}
                                    onChange={(e) =>
                                        form.setData(
                                            'treatment_plan_id',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                                >
                                    <option value="">Ninguno / General</option>
                                    {plans.map((pl) => (
                                        <option key={pl.id} value={pl.id}>
                                            {pl.title}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}
                        {form.data.document_type === 'factura' && (
                            <div className="space-y-2 rounded-xl border border-[#0E8FA3]/30 bg-[#E6F5F7]/40 p-3">
                                <div className="text-xs font-bold text-[#0A6577]">
                                    Facturación SUNAT UBL 2.1 (RUC)
                                </div>
                                <div className="flex gap-2">
                                    <Input
                                        placeholder="RUC 11 dígitos"
                                        maxLength={11}
                                        value={form.data.customer_doc_number}
                                        onChange={(e) =>
                                            form.setData(
                                                'customer_doc_number',
                                                e.target.value.replace(
                                                    /\D/g,
                                                    '',
                                                ),
                                            )
                                        }
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={lookingUpRuc}
                                        onClick={() => void lookupRuc()}
                                        className="shrink-0 gap-1 text-xs"
                                    >
                                        <Search className="size-3.5" />
                                        {lookingUpRuc ? '...' : 'RUC'}
                                    </Button>
                                </div>
                                <Input
                                    placeholder="Razón social *"
                                    required
                                    value={form.data.customer_name}
                                    onChange={(e) =>
                                        form.setData(
                                            'customer_name',
                                            e.target.value,
                                        )
                                    }
                                />
                                <Input
                                    placeholder="Dirección fiscal"
                                    value={form.data.customer_address}
                                    onChange={(e) =>
                                        form.setData(
                                            'customer_address',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        )}
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setOpenPay(false)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                disabled={form.processing}
                                className="bg-[#0E8FA3] text-white"
                            >
                                Registrar cobro
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Programar Cuotas */}
            <Dialog open={openInstallments} onOpenChange={setOpenInstallments}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            Programar financiamiento por cuotas
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitInstallments} className="space-y-3">
                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <Label>Monto total ({currency}) *</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    required
                                    value={quotaGenForm.data.total_amount}
                                    onChange={(e) =>
                                        quotaGenForm.setData(
                                            'total_amount',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Inicial ya cubierto ({currency})</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    value={quotaGenForm.data.down_payment}
                                    onChange={(e) =>
                                        quotaGenForm.setData(
                                            'down_payment',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-3 gap-2">
                            <div>
                                <Label>N.º cuotas *</Label>
                                <Input
                                    type="number"
                                    min={1}
                                    max={36}
                                    required
                                    value={quotaGenForm.data.installments_count}
                                    onChange={(e) =>
                                        quotaGenForm.setData(
                                            'installments_count',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Primer vencimiento *</Label>
                                <Input
                                    type="date"
                                    required
                                    value={quotaGenForm.data.first_due_date}
                                    onChange={(e) =>
                                        quotaGenForm.setData(
                                            'first_due_date',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Frecuencia</Label>
                                <select
                                    value={quotaGenForm.data.frequency_days}
                                    onChange={(e) =>
                                        quotaGenForm.setData(
                                            'frequency_days',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-2 text-xs"
                                >
                                    <option value="30">Mensual</option>
                                    <option value="15">Quincenal</option>
                                </select>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setOpenInstallments(false)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                className="bg-[#0E8FA3] text-white"
                            >
                                Generar cuotas
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Cobrar Cuota */}
            <Dialog
                open={payingQuota !== null}
                onOpenChange={(open) => !open && setPayingQuota(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            Cobrar Cuota N.º {payingQuota?.installment_number}
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitQuotaPay} className="space-y-3">
                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <Label>Monto a cobrar ({currency}) *</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    required
                                    value={quotaPayForm.data.amount}
                                    onChange={(e) =>
                                        quotaPayForm.setData(
                                            'amount',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Comprobante</Label>
                                <select
                                    value={quotaPayForm.data.document_type}
                                    onChange={(e) =>
                                        quotaPayForm.setData(
                                            'document_type',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-2 text-sm"
                                >
                                    <option value="boleta">
                                        Boleta de venta
                                    </option>
                                    <option value="recibo">
                                        Recibo interno
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <Label>Medio de pago</Label>
                            <select
                                value={quotaPayForm.data.payment_method}
                                onChange={(e) =>
                                    quotaPayForm.setData(
                                        'payment_method',
                                        e.target.value,
                                    )
                                }
                                className="mt-1 h-10 w-full rounded-md border bg-background px-2 text-sm"
                            >
                                <option value="yape_plin">Yape / Plin</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="tarjeta">Tarjeta POS</option>
                                <option value="transferencia">
                                    Transferencia
                                </option>
                            </select>
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setPayingQuota(null)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                className="bg-[#2E9E6B] text-white"
                            >
                                Confirmar cobro en Caja
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
}

export function DocumentsTab({
    patient,
    documents,
    templates,
    canEdit,
}: {
    patient: Patient;
    documents: ClinicalDocument[];
    templates: { type: string; title: string; content: string }[];
    canEdit: boolean;
}) {
    const [openCreate, setOpenCreate] = useState(false);
    const [editingDoc, setEditingDoc] = useState<ClinicalDocument | null>(null);
    const [signingDoc, setSigningDoc] = useState<ClinicalDocument | null>(null);
    const [sigData, setSigData] = useState('');
    const [signerName, setSignerName] = useState(patient.full_name);

    const form = useForm({
        type: 'consentimiento',
        title: templates[0]?.title ?? 'Consentimiento informado',
        content: templates[0]?.content ?? '',
        signature_data: '',
        signed_by_name: patient.full_name,
    });

    const editForm = useForm({
        title: '',
        content: '',
    });

    const openEditDocument = (doc: ClinicalDocument) => {
        setEditingDoc(doc);
        editForm.setData({
            title: doc.title,
            content: doc.content,
        });
    };

    const submitEditDocument = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingDoc) return;
        editForm.patch(`/patients/${patient.id}/documents/${editingDoc.id}`, {
            preserveScroll: true,
            onSuccess: () => setEditingDoc(null),
        });
    };

    const deleteDocument = (docId: number) => {
        router.delete(`/patients/${patient.id}/documents/${docId}`, {
            preserveScroll: true,
        });
    };

    const applyTemplate = (tpl: {
        type: string;
        title: string;
        content: string;
    }) => {
        form.setData((prev) => ({
            ...prev,
            type: tpl.type,
            title: tpl.title,
            content: tpl.content,
        }));
    };

    const submitCreate = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(`/patients/${patient.id}/documents`, {
            preserveScroll: true,
            onSuccess: () => {
                setOpenCreate(false);
                form.reset();
            },
        });
    };

    const submitSignature = (e: React.FormEvent) => {
        e.preventDefault();
        if (!signingDoc || !sigData) return;
        router.post(
            `/patients/${patient.id}/documents/${signingDoc.id}/sign`,
            {
                signature_data: sigData,
                signed_by_name: signerName,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setSigningDoc(null);
                    setSigData('');
                },
            },
        );
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <div>
                    <h3 className="text-base font-bold">
                        Consentimientos Informados, Recetas y Certificados
                    </h3>
                    <p className="text-xs text-muted-foreground">
                        Firma digital directa en pantalla e impresión inmediata
                        en PDF
                    </p>
                </div>
                {canEdit && (
                    <Button
                        onClick={() => setOpenCreate(true)}
                        className="h-10 gap-1.5 rounded-xl bg-[#0E8FA3] text-xs text-white hover:bg-[#0A6577]"
                    >
                        <Plus className="size-4" />
                        Nuevo documento
                    </Button>
                )}
            </div>

            {documents.length === 0 ? (
                <div className="rounded-2xl border border-dashed bg-card p-8 text-center text-sm text-muted-foreground">
                    No hay documentos clínicos emitidos aún.
                </div>
            ) : (
                <div className="grid gap-4 md:grid-cols-2">
                    {documents.map((doc) => (
                        <div
                            key={doc.id}
                            className="space-y-3 rounded-2xl border bg-card p-4 shadow-xs"
                        >
                            <div className="flex items-start justify-between gap-2">
                                <div>
                                    <Badge
                                        variant="secondary"
                                        className="mb-1 uppercase"
                                    >
                                        {doc.type}
                                    </Badge>
                                    <h4 className="font-bold">{doc.title}</h4>
                                    <div className="text-xs text-muted-foreground">
                                        Emitido:{' '}
                                        {new Date(doc.issued_at).toLocaleString(
                                            'es-PE',
                                        )}{' '}
                                        · {doc.creator?.name}
                                    </div>
                                </div>
                                {doc.signed_at ? (
                                    <Badge className="gap-1 bg-[#DCFCE7] text-[#166534] hover:bg-[#DCFCE7]">
                                        <CheckCircle2 className="size-3" />
                                        Firmado
                                    </Badge>
                                ) : (
                                    <Badge variant="outline">Sin firma</Badge>
                                )}
                            </div>

                            <p className="line-clamp-3 text-xs whitespace-pre-line text-muted-foreground">
                                {doc.content}
                            </p>

                            <div className="flex flex-wrap gap-2 pt-1">
                                <a
                                    href={`/patients/${patient.id}/documents/${doc.id}/pdf`}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex h-8 items-center gap-1 rounded-xl border px-3 text-xs font-medium hover:bg-accent"
                                >
                                    <FileDown className="size-3.5" />
                                    Descargar PDF
                                </a>
                                {!doc.signed_at && (
                                    <>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            className="h-8 gap-1 rounded-xl text-xs text-[#0E8FA3]"
                                            onClick={() => {
                                                setSigningDoc(doc);
                                                setSignerName(
                                                    patient.full_name,
                                                );
                                            }}
                                        >
                                            <FileSignature className="size-3.5" />
                                            Firmar en pantalla
                                        </Button>
                                        {canEdit && (
                                            <>
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="ghost"
                                                    className="h-8 gap-1 rounded-xl px-2.5 text-xs"
                                                    onClick={() =>
                                                        openEditDocument(doc)
                                                    }
                                                >
                                                    <Pencil className="size-3.5" />
                                                    Editar
                                                </Button>
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="ghost"
                                                    className="h-8 rounded-xl px-2 text-xs text-[#D64545]"
                                                    onClick={() =>
                                                        deleteDocument(doc.id)
                                                    }
                                                >
                                                    <Trash2 className="size-3.5" />
                                                </Button>
                                            </>
                                        )}
                                    </>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            <Dialog open={openCreate} onOpenChange={setOpenCreate}>
                <DialogContent className="max-h-[90vh] max-w-2xl overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Emitir documento clínico</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-1.5">
                        <span className="text-xs font-semibold text-muted-foreground">
                            Plantillas listas:
                        </span>
                        <div className="flex flex-wrap gap-1.5">
                            {templates.map((t) => (
                                <button
                                    key={t.title}
                                    type="button"
                                    onClick={() => applyTemplate(t)}
                                    className="rounded-full border bg-muted/40 px-2.5 py-1 text-xs font-medium hover:bg-[#E6F5F7]"
                                >
                                    {t.title}
                                </button>
                            ))}
                        </div>
                    </div>

                    <form onSubmit={submitCreate} className="space-y-3">
                        <div className="grid grid-cols-3 gap-2">
                            <div>
                                <Label>Tipo</Label>
                                <select
                                    value={form.data.type}
                                    onChange={(e) =>
                                        form.setData('type', e.target.value)
                                    }
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-2 text-sm"
                                >
                                    <option value="consentimiento">
                                        Consentimiento
                                    </option>
                                    <option value="receta">
                                        Receta médica
                                    </option>
                                    <option value="certificado">
                                        Certificado
                                    </option>
                                </select>
                            </div>
                            <div className="col-span-2">
                                <Label>Título *</Label>
                                <Input
                                    required
                                    value={form.data.title}
                                    onChange={(e) =>
                                        form.setData('title', e.target.value)
                                    }
                                />
                            </div>
                        </div>
                        <div>
                            <Label>Contenido del documento *</Label>
                            <textarea
                                required
                                rows={6}
                                value={form.data.content}
                                onChange={(e) =>
                                    form.setData('content', e.target.value)
                                }
                                className="mt-1 w-full rounded-xl border bg-background p-3 text-sm"
                            />
                        </div>

                        {form.data.type === 'consentimiento' && (
                            <div className="space-y-2 rounded-xl border bg-muted/20 p-3">
                                <Input
                                    value={form.data.signed_by_name}
                                    onChange={(e) =>
                                        form.setData(
                                            'signed_by_name',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Nombre de quien firma (Paciente o Apoderado)"
                                    className="h-9 text-xs"
                                />
                                <SignaturePad
                                    onChange={(data) =>
                                        form.setData('signature_data', data)
                                    }
                                />
                            </div>
                        )}

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
                                disabled={form.processing}
                                className="bg-[#0E8FA3] text-white"
                            >
                                Guardar documento
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog
                open={signingDoc !== null}
                onOpenChange={() => setSigningDoc(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            Firma del paciente: {signingDoc?.title}
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitSignature} className="space-y-3">
                        <div>
                            <Label>Nombre del firmante</Label>
                            <Input
                                required
                                value={signerName}
                                onChange={(e) => setSignerName(e.target.value)}
                            />
                        </div>
                        <SignaturePad onChange={setSigData} />
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setSigningDoc(null)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                disabled={!sigData}
                                className="bg-[#0E8FA3] text-white"
                            >
                                Confirmar firma
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Editar Documento no firmado */}
            <Dialog
                open={editingDoc !== null}
                onOpenChange={(open) => !open && setEditingDoc(null)}
            >
                <DialogContent className="max-w-xl">
                    <DialogHeader>
                        <DialogTitle>Editar documento clínico</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitEditDocument} className="space-y-3">
                        <div>
                            <Label>Título *</Label>
                            <Input
                                required
                                value={editForm.data.title}
                                onChange={(e) =>
                                    editForm.setData('title', e.target.value)
                                }
                            />
                        </div>
                        <div>
                            <Label>Contenido *</Label>
                            <textarea
                                required
                                rows={6}
                                value={editForm.data.content}
                                onChange={(e) =>
                                    editForm.setData('content', e.target.value)
                                }
                                className="mt-1 w-full rounded-xl border bg-background p-3 text-sm"
                            />
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setEditingDoc(null)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                disabled={editForm.processing}
                                className="bg-[#0E8FA3] text-white"
                            >
                                Guardar cambios
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
}

export function ImagesTab({
    patient,
    images,
    canEdit,
}: {
    patient: Patient;
    images: PatientImage[];
    canEdit: boolean;
}) {
    const [openUpload, setOpenUpload] = useState(false);
    const [compareMode, setCompareMode] = useState(false);
    const [sliderPos, setSliderPos] = useState(50);
    const [activeViewerImage, setActiveViewerImage] =
        useState<PatientImage | null>(null);
    const [beforeId, setBeforeId] = useState<number>(
        images[1]?.id ?? images[0]?.id ?? 0,
    );
    const [afterId, setAfterId] = useState<number>(images[0]?.id ?? 0);

    const form = useForm({
        category: 'radiografia',
        title: '',
        tooth_code: '',
        taken_at: new Date().toISOString().slice(0, 10),
        notes: '',
        image: null as File | null,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(`/patients/${patient.id}/images`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                setOpenUpload(false);
                form.reset();
            },
        });
    };

    const deleteImage = (id: number) => {
        router.delete(`/patients/${patient.id}/images/${id}`, {
            preserveScroll: true,
        });
    };

    const beforeImg = images.find((i) => i.id === beforeId);
    const afterImg = images.find((i) => i.id === afterId);

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 className="text-base font-bold">
                        Galería de Radiografías y Fotos Clínicas
                    </h3>
                    <p className="text-xs text-muted-foreground">
                        Captura directa desde cámara del celular/tablet o
                        archivo, con comparador Antes y Después
                    </p>
                </div>
                <div className="flex gap-2">
                    {images.length >= 2 && (
                        <Button
                            type="button"
                            variant={compareMode ? 'default' : 'outline'}
                            className={`h-10 gap-1.5 rounded-xl text-xs ${compareMode ? 'bg-[#0A6577] text-white' : ''}`}
                            onClick={() => setCompareMode((v) => !v)}
                        >
                            <ArrowLeftRight className="size-4" />
                            Comparador Antes / Después
                        </Button>
                    )}
                    {canEdit && (
                        <Button
                            onClick={() => setOpenUpload(true)}
                            className="h-10 gap-1.5 rounded-xl bg-[#0E8FA3] text-xs text-white hover:bg-[#0A6577]"
                        >
                            <Camera className="size-4" />
                            Subir / Tomar foto
                        </Button>
                    )}
                </div>
            </div>

            {compareMode && beforeImg && afterImg && (
                <div className="space-y-3 rounded-2xl border bg-card p-4 shadow-xs">
                    <div className="grid gap-3 sm:grid-cols-2">
                        <div>
                            <Label className="text-xs">Imagen ANTES</Label>
                            <select
                                value={beforeImg.id}
                                onChange={(e) =>
                                    setBeforeId(Number(e.target.value))
                                }
                                className="mt-1 h-9 w-full rounded-xl border bg-background px-3 text-xs"
                            >
                                {images.map((im) => (
                                    <option key={im.id} value={im.id}>
                                        {im.title} ({im.taken_at})
                                    </option>
                                ))}
                            </select>
                            <img
                                src={beforeImg.url}
                                alt={beforeImg.title}
                                className="mt-2 h-64 w-full rounded-xl border object-cover"
                            />
                        </div>
                        <div>
                            <Label className="text-xs">Imagen DESPUÉS</Label>
                            <select
                                value={afterImg.id}
                                onChange={(e) =>
                                    setAfterId(Number(e.target.value))
                                }
                                className="mt-1 h-9 w-full rounded-xl border bg-background px-3 text-xs"
                            >
                                {images.map((im) => (
                                    <option key={im.id} value={im.id}>
                                        {im.title} ({im.taken_at})
                                    </option>
                                ))}
                            </select>
                            <img
                                src={afterImg.url}
                                alt={afterImg.title}
                                className="mt-2 h-64 w-full rounded-xl border object-cover"
                            />
                        </div>
                    </div>
                    <div className="space-y-1.5 border-t pt-3">
                        <div className="flex items-center justify-between text-xs font-semibold">
                            <span>
                                Comparador interactivo superpuesto (Cortina
                                Antes / Después)
                            </span>
                            <span>{sliderPos}%</span>
                        </div>
                        <div className="relative h-64 w-full overflow-hidden rounded-xl border bg-black">
                            <img
                                src={afterImg.url}
                                alt={afterImg.title}
                                className="h-full w-full object-contain"
                            />
                            <img
                                src={beforeImg.url}
                                alt={beforeImg.title}
                                style={{
                                    clipPath: `inset(0 ${100 - sliderPos}% 0 0)`,
                                }}
                                className="absolute inset-0 h-full w-full object-contain"
                            />
                            <div
                                style={{ left: `${sliderPos}%` }}
                                className="pointer-events-none absolute top-0 bottom-0 w-0.5 bg-[#22D3EE]"
                            />
                        </div>
                        <input
                            type="range"
                            min={0}
                            max={100}
                            value={sliderPos}
                            onChange={(e) =>
                                setSliderPos(Number(e.target.value))
                            }
                            className="w-full"
                        />
                    </div>
                </div>
            )}

            {images.length === 0 ? (
                <div className="rounded-2xl border border-dashed bg-card p-8 text-center text-sm text-muted-foreground">
                    Sin radiografías ni fotos clínicas registradas aún.
                </div>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {images.map((img) => (
                        <div
                            key={img.id}
                            className="overflow-hidden rounded-2xl border bg-card shadow-xs"
                        >
                            <a href={img.url} target="_blank" rel="noreferrer">
                                <img
                                    src={img.url}
                                    alt={img.title}
                                    className="h-48 w-full object-cover transition-transform hover:scale-105"
                                />
                            </a>
                            <div className="space-y-1 p-3.5">
                                <div className="flex items-center justify-between gap-2">
                                    <Badge
                                        variant="secondary"
                                        className="text-[10px] uppercase"
                                    >
                                        {img.category.replace('_', ' ')}
                                    </Badge>
                                    {canEdit && (
                                        <button
                                            type="button"
                                            onClick={() => deleteImage(img.id)}
                                            className="text-muted-foreground hover:text-[#D64545]"
                                        >
                                            <Trash2 className="size-4" />
                                        </button>
                                    )}
                                </div>
                                <div className="text-sm font-bold">
                                    {img.title}
                                </div>
                                <div className="text-xs text-muted-foreground">
                                    Fecha: {img.taken_at}{' '}
                                    {img.tooth_code
                                        ? `· Pieza ${img.tooth_code}`
                                        : ''}
                                </div>
                                <div className="flex items-center justify-between pt-1.5">
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        onClick={() =>
                                            setActiveViewerImage(img)
                                        }
                                        className="h-7 gap-1 rounded-lg text-[11px] text-[#0A6577]"
                                    >
                                        <Ruler className="size-3" />
                                        Visor Rx / Medir
                                        {(img.annotations?.measurements
                                            ?.length ?? 0) > 0
                                            ? ` (${img.annotations?.measurements?.length})`
                                            : ''}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            <RadiographViewerModal
                patientId={patient.id}
                image={activeViewerImage}
                canEdit={canEdit}
                onClose={() => setActiveViewerImage(null)}
            />

            <Dialog open={openUpload} onOpenChange={setOpenUpload}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            Subir imagen clínica o radiografía
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submit} className="space-y-3">
                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <Label>Categoría</Label>
                                <select
                                    value={form.data.category}
                                    onChange={(e) =>
                                        form.setData('category', e.target.value)
                                    }
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                                >
                                    <option value="radiografia">
                                        Radiografía
                                    </option>
                                    <option value="intraoral">
                                        Foto intraoral
                                    </option>
                                    <option value="extraoral">
                                        Foto extraoral
                                    </option>
                                    <option value="antes_despues">
                                        Antes / Después
                                    </option>
                                    <option value="documento">Documento</option>
                                </select>
                            </div>
                            <div>
                                <Label>Fecha de toma</Label>
                                <Input
                                    type="date"
                                    required
                                    value={form.data.taken_at}
                                    onChange={(e) =>
                                        form.setData('taken_at', e.target.value)
                                    }
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-3 gap-2">
                            <div className="col-span-2">
                                <Label>Título *</Label>
                                <Input
                                    required
                                    placeholder="Ej: Rx Periapical pieza 46"
                                    value={form.data.title}
                                    onChange={(e) =>
                                        form.setData('title', e.target.value)
                                    }
                                />
                            </div>
                            <div>
                                <Label>Pieza</Label>
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
                        </div>
                        <div>
                            <Label>
                                Archivo de imagen (Cámara o Galería) *
                            </Label>
                            <Input
                                type="file"
                                accept="image/*"
                                capture="environment"
                                required
                                onChange={(e) =>
                                    form.setData(
                                        'image',
                                        e.target.files?.[0] ?? null,
                                    )
                                }
                            />
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setOpenUpload(false)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                disabled={form.processing}
                                className="bg-[#0E8FA3] text-white"
                            >
                                Subir imagen
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
}

export function PerioOrthoTab({
    patient,
    periodontograms,
    orthodonticControls,
    canEdit,
}: {
    patient: Patient;
    periodontograms: Periodontogram[];
    orthodonticControls: OrthodonticControl[];
    canEdit: boolean;
}) {
    const initialEntries: Record<string, PeriodontalToothEntry> = {};
    const latestPerio = periodontograms[0];
    for (const t of PERIO_TEETH) {
        initialEntries[t] = latestPerio?.entries?.[t] ?? {
            pd: [2, 2, 2, 2, 2, 2],
            bop: false,
            plaque: false,
            recession: 0,
            mobility: 0,
            furcation: 0,
        };
    }

    const [entries, setEntries] =
        useState<Record<string, PeriodontalToothEntry>>(initialEntries);
    const [perioNotes, setPerioNotes] = useState(latestPerio?.notes ?? '');
    const [openOrthoModal, setOpenOrthoModal] = useState(false);

    const orthoForm = useForm({
        control_date: new Date().toISOString().slice(0, 16),
        stage: 'Alineación y nivelación',
        upper_archwire: 'NiTi 0.014"',
        lower_archwire: 'NiTi 0.014"',
        elastics: '',
        hygiene_status: 'buena',
        clinical_notes: '',
        next_control_date: '',
    });

    const updatePd = (tooth: string, siteIdx: number, val: number) => {
        setEntries((prev) => {
            const cur = prev[tooth];
            const nextPd: [number, number, number, number, number, number] = [
                ...cur.pd,
            ] as [number, number, number, number, number, number];
            nextPd[siteIdx] = Math.max(0, Math.min(15, val));
            return { ...prev, [tooth]: { ...cur, pd: nextPd } };
        });
    };

    const toggleBool = (tooth: string, key: 'bop' | 'plaque') => {
        setEntries((prev) => ({
            ...prev,
            [tooth]: { ...prev[tooth], [key]: !prev[tooth][key] },
        }));
    };

    const savePerio = () => {
        router.post(
            `/patients/${patient.id}/periodontograms`,
            { entries, notes: perioNotes },
            { preserveScroll: true },
        );
    };

    const deletePeriodontogram = (id: number) => {
        router.delete(`/patients/${patient.id}/periodontograms/${id}`, {
            preserveScroll: true,
        });
    };

    const loadPerioSnapshot = (p: Periodontogram) => {
        const nextEntries: Record<string, PeriodontalToothEntry> = {};
        for (const t of PERIO_TEETH) {
            nextEntries[t] = p.entries?.[t] ?? {
                pd: [2, 2, 2, 2, 2, 2],
                bop: false,
                plaque: false,
                recession: 0,
                mobility: 0,
                furcation: 0,
            };
        }
        setEntries(nextEntries);
        setPerioNotes(p.notes ?? '');
    };

    const deleteOrthoControl = (id: number) => {
        router.delete(`/patients/${patient.id}/orthodontic-controls/${id}`, {
            preserveScroll: true,
        });
    };

    const submitOrtho = (e: React.FormEvent) => {
        e.preventDefault();
        orthoForm.post(`/patients/${patient.id}/orthodontic-controls`, {
            preserveScroll: true,
            onSuccess: () => {
                setOpenOrthoModal(false);
                orthoForm.reset();
            },
        });
    };

    const total = PERIO_TEETH.length;
    const bopPct = Math.round(
        (PERIO_TEETH.filter((t) => entries[t]?.bop).length / total) * 100,
    );
    const plaquePct = Math.round(
        (PERIO_TEETH.filter((t) => entries[t]?.plaque).length / total) * 100,
    );

    return (
        <div className="space-y-6">
            {/* Periodontograma */}
            <div className="rounded-2xl border bg-card p-5 shadow-xs">
                <div className="mb-4 flex flex-wrap items-center justify-between gap-2 border-b pb-3">
                    <div>
                        <h3 className="flex items-center gap-2 text-base font-bold">
                            <Activity className="size-5 text-[#0E8FA3]" />
                            Periodontograma (Sondaje 6 sitios · Sangrado · Placa
                            · Movilidad)
                        </h3>
                        <p className="text-xs text-muted-foreground">
                            Índice de sangrado actual:{' '}
                            <strong>{bopPct}%</strong> · Índice de placa:{' '}
                            <strong>{plaquePct}%</strong>
                        </p>
                    </div>
                    {canEdit && (
                        <Button
                            type="button"
                            onClick={savePerio}
                            className="h-9 rounded-xl bg-[#0E8FA3] px-4 text-xs text-white hover:bg-[#0A6577]"
                        >
                            Guardar periodontograma
                        </Button>
                    )}
                </div>

                <div className="max-h-96 overflow-auto rounded-xl border">
                    <table className="w-full text-left text-xs">
                        <thead className="sticky top-0 bg-muted text-[11px]">
                            <tr>
                                <th className="px-2.5 py-2">Pieza</th>
                                <th className="px-2.5 py-2">
                                    Vestibular (MV · V · DV)
                                </th>
                                <th className="px-2.5 py-2">
                                    Lingual/Pal. (ML · L · DL)
                                </th>
                                <th className="px-2.5 py-2 text-center">
                                    Sangrado (BOP)
                                </th>
                                <th className="px-2.5 py-2 text-center">
                                    Placa
                                </th>
                                <th className="px-2.5 py-2">Movilidad</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {PERIO_TEETH.map((tooth) => {
                                const row = entries[tooth];
                                const maxPd = Math.max(...row.pd);
                                return (
                                    <tr
                                        key={tooth}
                                        className={
                                            maxPd >= 5 ? 'bg-[#FEE2E2]/40' : ''
                                        }
                                    >
                                        <td className="px-2.5 py-1.5 font-bold">
                                            {tooth}
                                        </td>
                                        <td className="px-2.5 py-1.5">
                                            <div className="flex gap-1">
                                                {[0, 1, 2].map((i) => (
                                                    <input
                                                        key={i}
                                                        type="number"
                                                        min={0}
                                                        max={15}
                                                        disabled={!canEdit}
                                                        value={row.pd[i]}
                                                        onChange={(e) =>
                                                            updatePd(
                                                                tooth,
                                                                i,
                                                                Number(
                                                                    e.target
                                                                        .value,
                                                                ),
                                                            )
                                                        }
                                                        className={`h-7 w-10 rounded border text-center ${
                                                            row.pd[i] >= 5
                                                                ? 'border-[#D64545] bg-[#FEE2E2] font-bold text-[#991B1B]'
                                                                : row.pd[i] ===
                                                                    4
                                                                  ? 'border-[#F0A92E] bg-[#FEF3C7]'
                                                                  : 'bg-background'
                                                        }`}
                                                    />
                                                ))}
                                            </div>
                                        </td>
                                        <td className="px-2.5 py-1.5">
                                            <div className="flex gap-1">
                                                {[3, 4, 5].map((i) => (
                                                    <input
                                                        key={i}
                                                        type="number"
                                                        min={0}
                                                        max={15}
                                                        disabled={!canEdit}
                                                        value={row.pd[i]}
                                                        onChange={(e) =>
                                                            updatePd(
                                                                tooth,
                                                                i,
                                                                Number(
                                                                    e.target
                                                                        .value,
                                                                ),
                                                            )
                                                        }
                                                        className={`h-7 w-10 rounded border text-center ${
                                                            row.pd[i] >= 5
                                                                ? 'border-[#D64545] bg-[#FEE2E2] font-bold text-[#991B1B]'
                                                                : row.pd[i] ===
                                                                    4
                                                                  ? 'border-[#F0A92E] bg-[#FEF3C7]'
                                                                  : 'bg-background'
                                                        }`}
                                                    />
                                                ))}
                                            </div>
                                        </td>
                                        <td className="px-2.5 py-1.5 text-center">
                                            <input
                                                type="checkbox"
                                                disabled={!canEdit}
                                                checked={row.bop}
                                                onChange={() =>
                                                    toggleBool(tooth, 'bop')
                                                }
                                            />
                                        </td>
                                        <td className="px-2.5 py-1.5 text-center">
                                            <input
                                                type="checkbox"
                                                disabled={!canEdit}
                                                checked={row.plaque}
                                                onChange={() =>
                                                    toggleBool(tooth, 'plaque')
                                                }
                                            />
                                        </td>
                                        <td className="px-2.5 py-1.5">
                                            <select
                                                disabled={!canEdit}
                                                value={row.mobility}
                                                onChange={(e) =>
                                                    setEntries((prev) => ({
                                                        ...prev,
                                                        [tooth]: {
                                                            ...prev[tooth],
                                                            mobility: Number(
                                                                e.target.value,
                                                            ),
                                                        },
                                                    }))
                                                }
                                                className="h-7 rounded border bg-background px-1.5"
                                            >
                                                <option value={0}>G0</option>
                                                <option value={1}>G1</option>
                                                <option value={2}>G2</option>
                                                <option value={3}>G3</option>
                                            </select>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>

                <Input
                    disabled={!canEdit}
                    value={perioNotes}
                    onChange={(e) => setPerioNotes(e.target.value)}
                    placeholder="Observaciones periodontales..."
                    className="mt-3 h-9 text-xs"
                />

                {periodontograms.length > 0 && (
                    <div className="mt-4 space-y-2 border-t pt-3">
                        <div className="text-xs font-bold text-muted-foreground">
                            Registros periodontales guardados (
                            {periodontograms.length})
                        </div>
                        <div className="grid gap-2 sm:grid-cols-2">
                            {periodontograms.map((p) => (
                                <div
                                    key={p.id}
                                    className="flex items-center justify-between rounded-xl border bg-background px-3 py-2 text-xs"
                                >
                                    <div>
                                        <div className="font-semibold">
                                            {new Date(
                                                p.recorded_at,
                                            ).toLocaleString('es-PE')}
                                        </div>
                                        <div className="text-muted-foreground">
                                            Sangrado: {p.bleeding_index}% ·
                                            Placa: {p.plaque_index}% ·{' '}
                                            {p.creator?.name ?? '-'}
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            className="h-7 px-2 text-[11px]"
                                            onClick={() => loadPerioSnapshot(p)}
                                        >
                                            Cargar
                                        </Button>
                                        {canEdit && (
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="ghost"
                                                className="h-7 px-2 text-[#D64545]"
                                                onClick={() =>
                                                    deletePeriodontogram(p.id)
                                                }
                                            >
                                                <Trash2 className="size-3.5" />
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>

            {/* Controles de Ortodoncia */}
            <div className="rounded-2xl border bg-card p-5 shadow-xs">
                <div className="mb-4 flex items-center justify-between">
                    <div>
                        <h3 className="text-base font-bold">
                            Seguimiento de Ortodoncia
                        </h3>
                        <p className="text-xs text-muted-foreground">
                            Registro de arcos, elásticos, higiene y próximas
                            activaciones
                        </p>
                    </div>
                    {canEdit && (
                        <Button
                            onClick={() => setOpenOrthoModal(true)}
                            className="h-9 gap-1.5 rounded-xl bg-[#0E8FA3] text-xs text-white"
                        >
                            <Plus className="size-4" />
                            Nuevo control
                        </Button>
                    )}
                </div>

                {orthodonticControls.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Sin controles de ortodoncia registrados.
                    </p>
                ) : (
                    <div className="space-y-2.5">
                        {orthodonticControls.map((c) => (
                            <div
                                key={c.id}
                                className="rounded-xl border bg-background p-3.5 text-sm"
                            >
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <span className="font-bold text-[#0A6577]">
                                        {c.stage}
                                    </span>
                                    <div className="flex items-center gap-2">
                                        <span className="text-xs text-muted-foreground">
                                            {new Date(
                                                c.control_date,
                                            ).toLocaleDateString('es-PE')}{' '}
                                            · {c.dentist?.name}
                                        </span>
                                        {canEdit && (
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="ghost"
                                                className="h-7 px-2 text-[#D64545]"
                                                onClick={() =>
                                                    deleteOrthoControl(c.id)
                                                }
                                            >
                                                <Trash2 className="size-3.5" />
                                            </Button>
                                        )}
                                    </div>
                                </div>
                                <div className="mt-1 text-xs text-muted-foreground">
                                    Arco sup: {c.upper_archwire || '-'} · Arco
                                    inf: {c.lower_archwire || '-'} · Elásticos:{' '}
                                    {c.elastics || '-'} · Higiene:{' '}
                                    {c.hygiene_status}
                                </div>
                                <p className="mt-1.5 text-xs">
                                    {c.clinical_notes}
                                </p>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            <Dialog open={openOrthoModal} onOpenChange={setOpenOrthoModal}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Nuevo control de ortodoncia</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitOrtho} className="space-y-3">
                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <Label>Fecha de control</Label>
                                <Input
                                    type="datetime-local"
                                    required
                                    value={orthoForm.data.control_date}
                                    onChange={(e) =>
                                        orthoForm.setData(
                                            'control_date',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Etapa</Label>
                                <select
                                    value={orthoForm.data.stage}
                                    onChange={(e) =>
                                        orthoForm.setData(
                                            'stage',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-2 text-sm"
                                >
                                    <option value="Alineación y nivelación">
                                        Alineación y nivelación
                                    </option>
                                    <option value="Cierre de espacios">
                                        Cierre de espacios
                                    </option>
                                    <option value="Finalización">
                                        Finalización
                                    </option>
                                    <option value="Contención">
                                        Contención
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <Label>Arco superior</Label>
                                <Input
                                    value={orthoForm.data.upper_archwire}
                                    onChange={(e) =>
                                        orthoForm.setData(
                                            'upper_archwire',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Arco inferior</Label>
                                <Input
                                    value={orthoForm.data.lower_archwire}
                                    onChange={(e) =>
                                        orthoForm.setData(
                                            'lower_archwire',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <Label>Elásticos / Ligaduras</Label>
                                <Input
                                    value={orthoForm.data.elastics}
                                    onChange={(e) =>
                                        orthoForm.setData(
                                            'elastics',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Higiene oral</Label>
                                <select
                                    value={orthoForm.data.hygiene_status}
                                    onChange={(e) =>
                                        orthoForm.setData(
                                            'hygiene_status',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-2 text-sm"
                                >
                                    <option value="buena">Buena</option>
                                    <option value="regular">Regular</option>
                                    <option value="deficiente">
                                        Deficiente
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <Label>Notas clínicas *</Label>
                            <textarea
                                required
                                rows={3}
                                value={orthoForm.data.clinical_notes}
                                onChange={(e) =>
                                    orthoForm.setData(
                                        'clinical_notes',
                                        e.target.value,
                                    )
                                }
                                className="mt-1 w-full rounded-xl border bg-background p-2.5 text-sm"
                            />
                        </div>
                        <div>
                            <Label>Próximo control</Label>
                            <Input
                                type="date"
                                value={orthoForm.data.next_control_date}
                                onChange={(e) =>
                                    orthoForm.setData(
                                        'next_control_date',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setOpenOrthoModal(false)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                className="bg-[#0E8FA3] text-white"
                            >
                                Guardar control
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
}
