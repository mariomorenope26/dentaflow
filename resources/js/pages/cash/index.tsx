import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    FileCode2,
    FileDown,
    Lock,
    MessageCircle,
    Plus,
    Printer,
    Search,
    Wallet,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
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
import type { Auth, CashClosing, Payment } from '@/types';

type Props = {
    date: string;
    payments: Payment[];
    totals: {
        efectivo: number;
        tarjeta: number;
        yape_plin: number;
        transferencia: number;
        total: number;
    };
    closing: CashClosing | null;
    recentClosings: CashClosing[];
    patients: {
        id: number;
        first_name: string;
        last_name: string;
        full_name: string;
        history_number: string;
    }[];
    debtors: {
        patient_id: number;
        history_number: string;
        full_name: string;
        budgeted: number;
        paid: number;
        balance: number;
    }[];
    overdueInstallments: {
        id: number;
        patient_id: number;
        patient_name: string;
        patient_history: string;
        patient_phone: string | null;
        plan_title: string | null;
        installment_number: number;
        due_date: string;
        balance: number;
        is_overdue: boolean;
    }[];
};

export default function CashIndex({
    date,
    payments,
    totals,
    closing,
    recentClosings,
    patients,
    debtors,
    overdueInstallments,
}: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const currency = auth.clinic?.currency_symbol ?? 'S/';
    const preferredThermalSize =
        auth.clinic?.print_settings?.thermal_width === '58mm' ? '58' : '80';
    const activePayments = payments.filter((p) => p.status === 'registrado');
    const methodStats = {
        efectivo: {
            amount: totals.efectivo,
            count: activePayments.filter((p) => p.payment_method === 'efectivo')
                .length,
        },
        yape_plin: {
            amount: totals.yape_plin,
            count: activePayments.filter(
                (p) => p.payment_method === 'yape_plin',
            ).length,
        },
        tarjeta: {
            amount: totals.tarjeta,
            count: activePayments.filter((p) => p.payment_method === 'tarjeta')
                .length,
        },
        transferencia: {
            amount: totals.transferencia,
            count: activePayments.filter(
                (p) => p.payment_method === 'transferencia',
            ).length,
        },
    };
    const [openPay, setOpenPay] = useState(false);
    const [openClose, setOpenClose] = useState(false);
    const [lookingUpRuc, setLookingUpRuc] = useState(false);

    const payForm = useForm({
        patient_id: String(patients[0]?.id ?? ''),
        amount: '',
        payment_method: 'efectivo',
        document_type: 'boleta',
        customer_doc_type: '1',
        customer_doc_number: '',
        customer_name: '',
        customer_address: '',
        concept: 'Pago por atención odontológica',
    });

    const lookupRuc = async () => {
        const clean = payForm.data.customer_doc_number.replace(/\D/g, '');
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
            payForm.setData((prev) => ({
                ...prev,
                customer_doc_type: '6',
                customer_doc_number: clean,
                customer_name: body.data.name || prev.customer_name,
                customer_address: body.data.address || prev.customer_address,
            }));
            toast.success(`SUNAT RUC OK: ${body.data.name}`);
        } catch {
            toast.error('Error al consultar RUC.');
        } finally {
            setLookingUpRuc(false);
        }
    };

    const closeForm = useForm({
        closing_date: date,
        opening_amount: '0',
        notes: '',
    });

    const submitPayment = (e: React.FormEvent) => {
        e.preventDefault();
        if (!payForm.data.patient_id) return;
        payForm.post(`/patients/${payForm.data.patient_id}/payments`, {
            preserveScroll: true,
            onSuccess: () => {
                setOpenPay(false);
                payForm.reset('amount');
            },
        });
    };

    const submitClose = (e: React.FormEvent) => {
        e.preventDefault();
        closeForm.post('/caja/close', {
            preserveScroll: true,
            onSuccess: () => setOpenClose(false),
        });
    };

    return (
        <>
            <Head title="Caja y cobros" />
            <div className="mx-auto max-w-7xl space-y-6 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-2xl font-bold">
                            Caja diaria y cobros
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Control de ingresos por medio de pago, comprobantes
                            y cuentas por cobrar
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Input
                            type="date"
                            value={date}
                            onChange={(e) =>
                                router.get('/caja', { date: e.target.value })
                            }
                            className="h-10 w-40 rounded-xl text-xs"
                        />
                        <div className="inline-flex rounded-xl border bg-background p-0.5">
                            <a
                                href={`/caja/thermal-summary?date=${date}&size=80`}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex h-9 items-center gap-1.5 rounded-lg px-3 text-xs font-semibold hover:bg-accent"
                            >
                                <Printer className="size-3.5 text-[#0E8FA3]" />
                                Arqueo 80mm
                            </a>
                            <a
                                href={`/caja/thermal-summary?date=${date}&size=58`}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex h-9 items-center rounded-lg border-l px-2.5 text-xs font-semibold text-muted-foreground hover:bg-accent hover:text-foreground"
                            >
                                58mm
                            </a>
                        </div>
                        {!closing && (
                            <Button
                                variant="outline"
                                className="h-10 gap-1.5 rounded-xl"
                                onClick={() => setOpenClose(true)}
                            >
                                <Lock className="size-4 text-[#0E8FA3]" />
                                Cerrar caja del día
                            </Button>
                        )}
                        <Button
                            onClick={() => setOpenPay(true)}
                            className="h-10 gap-1.5 rounded-xl bg-[#0E8FA3] text-white hover:bg-[#0A6577]"
                        >
                            <Plus className="size-4" />
                            Nuevo cobro
                        </Button>
                    </div>
                </div>

                {closing && (
                    <div className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[#2E9E6B]/40 bg-[#ECFDF5] px-4 py-3 text-sm text-[#166534]">
                        <div className="flex items-center gap-2">
                            <CheckCircle2 className="size-5 shrink-0" />
                            <span>
                                Caja del {date} cerrada por{' '}
                                <strong>{closing.closer?.name}</strong> · Total
                                registrado:{' '}
                                <strong>
                                    {currency}{' '}
                                    {Number(closing.grand_total).toFixed(2)}
                                </strong>
                            </span>
                        </div>
                        <div className="flex items-center gap-2">
                            <a
                                href={`/caja/closings/${closing.id}/thermal?size=80`}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex h-8 items-center gap-1 rounded-xl border border-[#2E9E6B]/40 bg-white px-3 text-xs font-bold text-[#166534] hover:bg-emerald-50"
                            >
                                <Printer className="size-3.5" />
                                Cierre Térmico 80mm
                            </a>
                            <a
                                href={`/caja/closings/${closing.id}/thermal?size=58`}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex h-8 items-center rounded-xl border border-[#2E9E6B]/40 bg-white px-2.5 text-xs font-semibold text-[#166534] hover:bg-emerald-50"
                            >
                                58mm
                            </a>
                        </div>
                    </div>
                )}

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div className="rounded-2xl border bg-card p-4 shadow-xs">
                        <div className="text-xs text-muted-foreground">
                            Total del día ({activePayments.length} ops.)
                        </div>
                        <div className="mt-1 text-2xl font-bold text-[#0A6577]">
                            {currency} {totals.total.toFixed(2)}
                        </div>
                        <div className="mt-2 text-[11px] text-muted-foreground">
                            Medios de pago consolidados
                        </div>
                    </div>
                    {(
                        [
                            ['Efectivo', methodStats.efectivo],
                            ['Yape / Plin', methodStats.yape_plin],
                            ['Tarjeta POS', methodStats.tarjeta],
                            ['Transferencia', methodStats.transferencia],
                        ] as const
                    ).map(([label, stat]) => {
                        const pct =
                            totals.total > 0
                                ? Math.round((stat.amount / totals.total) * 100)
                                : 0;
                        return (
                            <div
                                key={label}
                                className="rounded-2xl border bg-card p-4 shadow-xs"
                            >
                                <div className="flex items-center justify-between text-xs text-muted-foreground">
                                    <span>{label}</span>
                                    <span className="font-semibold">
                                        {stat.count} op. ({pct}%)
                                    </span>
                                </div>
                                <div className="mt-1 text-xl font-bold">
                                    {currency} {stat.amount.toFixed(2)}
                                </div>
                                <div className="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                    <div
                                        className="h-full rounded-full bg-[#0E8FA3]"
                                        style={{ width: `${pct}%` }}
                                    />
                                </div>
                            </div>
                        );
                    })}
                </div>

                <div className="grid gap-6 lg:grid-cols-12">
                    <div className="rounded-2xl border bg-card p-5 shadow-xs lg:col-span-8">
                        <h2 className="mb-3 text-base font-bold">
                            Movimientos del día ({payments.length})
                        </h2>
                        {payments.length === 0 ? (
                            <p className="py-8 text-center text-sm text-muted-foreground">
                                No hay cobros registrados en esta fecha.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="border-b text-xs text-muted-foreground">
                                        <tr>
                                            <th className="py-2 pr-3">N.º</th>
                                            <th className="py-2 pr-3">
                                                Paciente
                                            </th>
                                            <th className="py-2 pr-3">
                                                Concepto
                                            </th>
                                            <th className="py-2 pr-3">Medio</th>
                                            <th className="py-2 pr-3">Monto</th>
                                            <th className="py-2 text-right">
                                                Comprobantes
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
                                                <td className="py-2.5 pr-3">
                                                    <Link
                                                        href={`/patients/${p.patient_id}?tab=pagos`}
                                                        className="font-semibold hover:text-[#0E8FA3]"
                                                    >
                                                        {p.patient?.full_name}
                                                    </Link>
                                                </td>
                                                <td className="py-2.5 pr-3 text-xs">
                                                    {p.concept}
                                                </td>
                                                <td className="py-2.5 pr-3">
                                                    <Badge
                                                        variant="outline"
                                                        className="text-[10px] uppercase"
                                                    >
                                                        {p.payment_method.replace(
                                                            '_',
                                                            ' / ',
                                                        )}
                                                    </Badge>
                                                </td>
                                                <td className="py-2.5 pr-3 font-bold">
                                                    {currency}{' '}
                                                    {Number(p.amount).toFixed(
                                                        2,
                                                    )}
                                                </td>
                                                <td className="py-2.5 text-right">
                                                    <div className="inline-flex items-center gap-1.5">
                                                        <a
                                                            href={`/payments/${p.id}/thermal?size=${preferredThermalSize}`}
                                                            target="_blank"
                                                            rel="noreferrer"
                                                            title={`Ticket térmico ${preferredThermalSize}mm`}
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
                                                                title="XML UBL 2.1 SUNAT"
                                                                className="inline-flex h-7 items-center gap-1 rounded-lg border px-2 text-xs hover:bg-accent"
                                                            >
                                                                <FileCode2 className="size-3 text-[#0E8FA3]" />
                                                                UBL
                                                            </a>
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

                    <div className="space-y-4 lg:col-span-4">
                        <div className="rounded-2xl border bg-card p-5 shadow-xs">
                            <h2 className="mb-3 flex items-center gap-2 text-base font-bold">
                                <AlertTriangle className="size-4 text-[#D64545]" />
                                Cuotas y Morosidad ({overdueInstallments.length}
                                )
                            </h2>
                            {overdueInstallments.length === 0 ? (
                                <p className="text-xs text-muted-foreground">
                                    Sin cuotas vencidas ni próximas en 7 días.
                                </p>
                            ) : (
                                <div className="space-y-2 text-xs">
                                    {overdueInstallments.map((inst) => {
                                        const waDigits =
                                            inst.patient_phone?.replace(
                                                /\D/g,
                                                '',
                                            ) ?? '';
                                        const waNumber =
                                            waDigits.length === 9 &&
                                            waDigits.startsWith('9')
                                                ? `51${waDigits}`
                                                : waDigits;
                                        const waText = encodeURIComponent(
                                            `Hola ${inst.patient_name}, le recordamos su Cuota N.º ${inst.installment_number} (${inst.due_date}) por ${currency} ${inst.balance.toFixed(2)}.`,
                                        );
                                        return (
                                            <div
                                                key={inst.id}
                                                className={`rounded-xl border p-2.5 ${inst.is_overdue ? 'border-rose-300 bg-rose-50/70' : 'bg-background'}`}
                                            >
                                                <div className="flex items-center justify-between gap-2">
                                                    <Link
                                                        href={`/patients/${inst.patient_id}?tab=pagos`}
                                                        className="font-bold hover:text-[#0E8FA3]"
                                                    >
                                                        {inst.patient_name}
                                                    </Link>
                                                    <span className="font-bold text-[#D64545]">
                                                        {currency}{' '}
                                                        {inst.balance.toFixed(
                                                            2,
                                                        )}
                                                    </span>
                                                </div>
                                                <div className="mt-0.5 flex items-center justify-between text-[11px] text-muted-foreground">
                                                    <span>
                                                        Cuota #
                                                        {
                                                            inst.installment_number
                                                        }{' '}
                                                        · Vence {inst.due_date}{' '}
                                                        {inst.is_overdue
                                                            ? '(VENCIDA)'
                                                            : ''}
                                                    </span>
                                                    {waNumber && (
                                                        <a
                                                            href={`https://wa.me/${waNumber}?text=${waText}`}
                                                            target="_blank"
                                                            rel="noreferrer"
                                                            className="inline-flex items-center gap-1 font-semibold text-[#2E9E6B] hover:underline"
                                                        >
                                                            <MessageCircle className="size-3" />
                                                            Recordar
                                                        </a>
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </div>

                        <div className="rounded-2xl border bg-card p-5 shadow-xs">
                            <h2 className="mb-3 text-base font-bold">
                                Pacientes con saldo pendiente
                            </h2>
                            {debtors.length === 0 ? (
                                <p className="text-xs text-muted-foreground">
                                    Sin deudas pendientes.
                                </p>
                            ) : (
                                <div className="space-y-2">
                                    {debtors.map((d) => (
                                        <Link
                                            key={d.patient_id}
                                            href={`/patients/${d.patient_id}?tab=pagos`}
                                            className="flex items-center justify-between rounded-xl border p-3 text-xs hover:bg-accent/50"
                                        >
                                            <div>
                                                <div className="font-bold">
                                                    {d.full_name}
                                                </div>
                                                <div className="text-muted-foreground">
                                                    {d.history_number}
                                                </div>
                                            </div>
                                            <div className="font-bold text-[#D64545]">
                                                {currency}{' '}
                                                {d.balance.toFixed(2)}
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            )}
                        </div>

                        <div className="rounded-2xl border bg-card p-5 shadow-xs">
                            <h2 className="mb-3 text-base font-bold">
                                Últimos cierres de caja
                            </h2>
                            {recentClosings.length === 0 ? (
                                <p className="text-xs text-muted-foreground">
                                    Sin cierres previos.
                                </p>
                            ) : (
                                <div className="space-y-2 text-xs">
                                    {recentClosings.map((c) => (
                                        <div
                                            key={c.id}
                                            className="flex items-center justify-between gap-2 rounded-xl border p-2.5"
                                        >
                                            <div>
                                                <div className="font-bold">
                                                    {c.closing_date.slice(
                                                        0,
                                                        10,
                                                    )}
                                                </div>
                                                <div className="text-muted-foreground">
                                                    {c.closer?.name}
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <span className="font-bold text-[#0A6577]">
                                                    {currency}{' '}
                                                    {Number(
                                                        c.grand_total,
                                                    ).toFixed(2)}
                                                </span>
                                                <a
                                                    href={`/caja/closings/${c.id}/thermal?size=80`}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    title="Reporte térmico 80mm"
                                                    className="inline-flex h-7 items-center gap-1 rounded-lg border px-2 text-[11px] font-semibold hover:bg-accent"
                                                >
                                                    <Printer className="size-3 text-[#0E8FA3]" />
                                                    80mm
                                                </a>
                                                <a
                                                    href={`/caja/closings/${c.id}/thermal?size=58`}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    title="Reporte térmico 58mm"
                                                    className="inline-flex h-7 items-center rounded-lg border px-1.5 text-[11px] text-muted-foreground hover:bg-accent"
                                                >
                                                    58mm
                                                </a>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            <Dialog open={openPay} onOpenChange={setOpenPay}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Wallet className="size-5 text-[#0E8FA3]" />
                            Registrar cobro en caja
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitPayment} className="space-y-3">
                        <div>
                            <Label>Paciente *</Label>
                            <select
                                required
                                value={payForm.data.patient_id}
                                onChange={(e) =>
                                    payForm.setData(
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
                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <Label>Monto ({currency}) *</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    min="0.50"
                                    required
                                    value={payForm.data.amount}
                                    onChange={(e) =>
                                        payForm.setData(
                                            'amount',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Comprobante</Label>
                                <select
                                    value={payForm.data.document_type}
                                    onChange={(e) =>
                                        payForm.setData(
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
                                value={payForm.data.payment_method}
                                onChange={(e) =>
                                    payForm.setData(
                                        'payment_method',
                                        e.target.value,
                                    )
                                }
                                className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                            >
                                <option value="efectivo">Efectivo</option>
                                <option value="yape_plin">Yape / Plin</option>
                                <option value="tarjeta">Tarjeta POS</option>
                                <option value="transferencia">
                                    Transferencia
                                </option>
                            </select>
                        </div>
                        <div>
                            <Label>Concepto *</Label>
                            <Input
                                required
                                value={payForm.data.concept}
                                onChange={(e) =>
                                    payForm.setData('concept', e.target.value)
                                }
                            />
                        </div>
                        {payForm.data.document_type === 'factura' && (
                            <div className="space-y-2 rounded-xl border border-[#0E8FA3]/30 bg-[#E6F5F7]/40 p-3">
                                <div className="text-xs font-bold text-[#0A6577]">
                                    Datos Facturación Electrónica SUNAT UBL 2.1
                                </div>
                                <div className="flex gap-2">
                                    <Input
                                        placeholder="RUC cliente (11 dígitos)"
                                        maxLength={11}
                                        value={payForm.data.customer_doc_number}
                                        onChange={(e) =>
                                            payForm.setData(
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
                                        <Search className="size-3.5 text-[#0E8FA3]" />
                                        {lookingUpRuc
                                            ? 'Buscando...'
                                            : 'Buscar RUC'}
                                    </Button>
                                </div>
                                <Input
                                    placeholder="Razón Social *"
                                    required
                                    value={payForm.data.customer_name}
                                    onChange={(e) =>
                                        payForm.setData(
                                            'customer_name',
                                            e.target.value,
                                        )
                                    }
                                />
                                <Input
                                    placeholder="Dirección fiscal"
                                    value={payForm.data.customer_address}
                                    onChange={(e) =>
                                        payForm.setData(
                                            'customer_address',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        )}
                        {payForm.data.document_type !== 'recibo' &&
                            Number(payForm.data.amount) > 0 && (
                                <div className="rounded-xl bg-muted/40 px-3 py-2 text-xs text-muted-foreground">
                                    Desglose SUNAT · Op. Gravada:{' '}
                                    <strong>
                                        {currency}{' '}
                                        {(
                                            Number(payForm.data.amount) / 1.18
                                        ).toFixed(2)}
                                    </strong>{' '}
                                    + IGV (18%):{' '}
                                    <strong>
                                        {currency}{' '}
                                        {(
                                            Number(payForm.data.amount) -
                                            Number(payForm.data.amount) / 1.18
                                        ).toFixed(2)}
                                    </strong>
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
                                disabled={payForm.processing}
                                className="bg-[#0E8FA3] text-white"
                            >
                                Registrar pago
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={openClose} onOpenChange={setOpenClose}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            Confirmar cierre de caja ({date})
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitClose} className="space-y-3">
                        <div className="rounded-xl bg-muted/40 p-3 text-sm">
                            Total del día a cerrar:{' '}
                            <strong>
                                {currency} {totals.total.toFixed(2)}
                            </strong>
                        </div>
                        <div>
                            <Label>Monto de apertura (caja chica)</Label>
                            <Input
                                type="number"
                                step="0.01"
                                value={closeForm.data.opening_amount}
                                onChange={(e) =>
                                    closeForm.setData(
                                        'opening_amount',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                        <div>
                            <Label>Observaciones del cierre</Label>
                            <Input
                                value={closeForm.data.notes}
                                onChange={(e) =>
                                    closeForm.setData('notes', e.target.value)
                                }
                            />
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setOpenClose(false)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                className="bg-[#0E8FA3] text-white"
                            >
                                Confirmar cierre
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
