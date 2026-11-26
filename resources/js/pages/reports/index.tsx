import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    BarChart3,
    CalendarX,
    FileSpreadsheet,
    TrendingUp,
    Users,
    Wallet,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Auth } from '@/types';

type Props = {
    filters: { start_date: string; end_date: string };
    summary: {
        total_income: number;
        payments_count: number;
        new_patients: number;
        appointments_total: number;
        appointments_attended: number;
        appointments_no_show: number;
        appointments_cancelled: number;
        no_show_rate: number;
        pending_debt_total: number;
    };
    incomeByMethod: { method: string; total: number }[];
    topProcedures: {
        description: string;
        total_count: number;
        total_amount: number;
    }[];
    pendingBalances: {
        patient_id: number;
        history_number: string;
        full_name: string;
        budgeted: number;
        paid: number;
        balance: number;
    }[];
    dentistCommissions: {
        dentist_id: number;
        name: string;
        specialty: string | null;
        appointments_attended: number;
        procedures_done: number;
        production_total: number;
        commission_rate: number;
        commission_amount: number;
    }[];
};

export default function ReportsIndex({
    filters,
    summary,
    incomeByMethod,
    topProcedures,
    pendingBalances,
    dentistCommissions,
}: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const currency = auth.clinic?.currency_symbol ?? 'S/';
    const [startDate, setStartDate] = useState(filters.start_date);
    const [endDate, setEndDate] = useState(filters.end_date);

    const applyFilter = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/reportes', { start_date: startDate, end_date: endDate });
    };

    const maxMethod = Math.max(1, ...incomeByMethod.map((m) => m.total));

    return (
        <>
            <Head title="Reportes del consultorio" />
            <div className="mx-auto max-w-7xl space-y-6 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-bold">
                            <BarChart3 className="size-6 text-[#0E8FA3]" />
                            Reportes clínicos y financieros
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Indicadores de productividad, asistencia,
                            tratamientos frecuentes y cuentas por cobrar
                        </p>
                    </div>
                    <a
                        href={`/reportes/export-csv?start_date=${startDate}&end_date=${endDate}`}
                        className="inline-flex h-10 items-center gap-2 rounded-xl bg-[#0E8FA3] px-4 text-xs font-semibold text-white hover:bg-[#0A6577]"
                    >
                        <FileSpreadsheet className="size-4" />
                        Exportar a Excel (CSV)
                    </a>
                </div>

                <form
                    onSubmit={applyFilter}
                    className="flex flex-wrap items-end gap-3 rounded-2xl border bg-card p-4 shadow-xs"
                >
                    <div>
                        <label className="text-xs font-semibold text-muted-foreground">
                            Desde
                        </label>
                        <Input
                            type="date"
                            value={startDate}
                            onChange={(e) => setStartDate(e.target.value)}
                            className="mt-1 h-9 w-40 rounded-xl text-xs"
                        />
                    </div>
                    <div>
                        <label className="text-xs font-semibold text-muted-foreground">
                            Hasta
                        </label>
                        <Input
                            type="date"
                            value={endDate}
                            onChange={(e) => setEndDate(e.target.value)}
                            className="mt-1 h-9 w-40 rounded-xl text-xs"
                        />
                    </div>
                    <Button
                        type="submit"
                        variant="outline"
                        className="h-9 rounded-xl text-xs"
                    >
                        Actualizar reporte
                    </Button>
                </form>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="rounded-2xl border bg-card p-4 shadow-xs">
                        <div className="flex items-center justify-between text-xs text-muted-foreground">
                            <span>Ingresos del periodo</span>
                            <TrendingUp className="size-4 text-[#2E9E6B]" />
                        </div>
                        <div className="mt-2 text-2xl font-bold text-[#2E9E6B]">
                            {currency} {summary.total_income.toFixed(2)}
                        </div>
                        <div className="mt-1 text-xs text-muted-foreground">
                            {summary.payments_count} cobro(s) registrados
                        </div>
                    </div>

                    <div className="rounded-2xl border bg-card p-4 shadow-xs">
                        <div className="flex items-center justify-between text-xs text-muted-foreground">
                            <span>Pacientes nuevos</span>
                            <Users className="size-4 text-[#0E8FA3]" />
                        </div>
                        <div className="mt-2 text-2xl font-bold">
                            {summary.new_patients}
                        </div>
                        <div className="mt-1 text-xs text-muted-foreground">
                            {summary.appointments_attended} cita(s) atendidas
                        </div>
                    </div>

                    <div className="rounded-2xl border bg-card p-4 shadow-xs">
                        <div className="flex items-center justify-between text-xs text-muted-foreground">
                            <span>Tasa de inasistencia</span>
                            <CalendarX className="size-4 text-[#F0A92E]" />
                        </div>
                        <div className="mt-2 text-2xl font-bold text-[#B45309]">
                            {summary.no_show_rate}%
                        </div>
                        <div className="mt-1 text-xs text-muted-foreground">
                            {summary.appointments_no_show} no asistió ·{' '}
                            {summary.appointments_cancelled} canceladas
                        </div>
                    </div>

                    <div className="rounded-2xl border bg-card p-4 shadow-xs">
                        <div className="flex items-center justify-between text-xs text-muted-foreground">
                            <span>Deudas por cobrar</span>
                            <Wallet className="size-4 text-[#D64545]" />
                        </div>
                        <div className="mt-2 text-2xl font-bold text-[#D64545]">
                            {currency} {summary.pending_debt_total.toFixed(2)}
                        </div>
                        <div className="mt-1 text-xs text-muted-foreground">
                            {pendingBalances.length} paciente(s) con saldo
                        </div>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <div className="rounded-2xl border bg-card p-5 shadow-xs">
                        <h2 className="mb-4 text-base font-bold">
                            Ingresos por medio de pago
                        </h2>
                        <div className="space-y-3">
                            {incomeByMethod.map((row) => (
                                <div key={row.method} className="space-y-1">
                                    <div className="flex justify-between text-xs font-semibold">
                                        <span>{row.method}</span>
                                        <span>
                                            {currency} {row.total.toFixed(2)}
                                        </span>
                                    </div>
                                    <div className="h-2.5 overflow-hidden rounded-full bg-muted">
                                        <div
                                            className="h-full rounded-full bg-[#0E8FA3]"
                                            style={{
                                                width: `${Math.round((row.total / maxMethod) * 100)}%`,
                                            }}
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="rounded-2xl border bg-card p-5 shadow-xs">
                        <h2 className="mb-4 text-base font-bold">
                            Tratamientos más frecuentes
                        </h2>
                        {topProcedures.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Sin procedimientos en este periodo.
                            </p>
                        ) : (
                            <div className="divide-y text-sm">
                                {topProcedures.map((proc, idx) => (
                                    <div
                                        key={idx}
                                        className="flex items-center justify-between py-2"
                                    >
                                        <span className="truncate pr-3 font-medium">
                                            {proc.description}
                                        </span>
                                        <span className="shrink-0 text-xs font-bold text-[#0A6577]">
                                            {proc.total_count}x · {currency}{' '}
                                            {Number(proc.total_amount).toFixed(
                                                2,
                                            )}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>

                <div className="rounded-2xl border bg-card p-5 shadow-xs">
                    <h2 className="mb-3 text-base font-bold">
                        Detalle de cuentas por cobrar
                    </h2>
                    {pendingBalances.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No hay saldos pendientes.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b text-xs text-muted-foreground">
                                    <tr>
                                        <th className="py-2 pr-3">Historia</th>
                                        <th className="py-2 pr-3">Paciente</th>
                                        <th className="py-2 pr-3">
                                            Presupuestado
                                        </th>
                                        <th className="py-2 pr-3">Pagado</th>
                                        <th className="py-2 text-right">
                                            Saldo pendiente
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {pendingBalances.map((b) => (
                                        <tr key={b.patient_id}>
                                            <td className="py-2 pr-3 font-mono text-xs">
                                                {b.history_number}
                                            </td>
                                            <td className="py-2 pr-3 font-semibold">
                                                <Link
                                                    href={`/patients/${b.patient_id}?tab=pagos`}
                                                    className="hover:text-[#0E8FA3]"
                                                >
                                                    {b.full_name}
                                                </Link>
                                            </td>
                                            <td className="py-2 pr-3">
                                                {currency}{' '}
                                                {b.budgeted.toFixed(2)}
                                            </td>
                                            <td className="py-2 pr-3 text-[#2E9E6B]">
                                                {currency} {b.paid.toFixed(2)}
                                            </td>
                                            <td className="py-2 text-right font-bold text-[#D64545]">
                                                {currency}{' '}
                                                {b.balance.toFixed(2)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {/* Liquidación de Comisiones por Odontólogo */}
                <div className="rounded-2xl border bg-card p-5 shadow-xs">
                    <h2 className="mb-1 text-base font-bold">
                        Producción y Comisiones por Odontólogo
                    </h2>
                    <p className="mb-3 text-xs text-muted-foreground">
                        Cálculo según procedimientos marcados como realizados y
                        porcentaje de comisión configurado en Equipo y Roles.
                    </p>
                    {dentistCommissions.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No hay datos de producción en este rango.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b text-xs text-muted-foreground">
                                    <tr>
                                        <th className="py-2 pr-3">
                                            Odontólogo
                                        </th>
                                        <th className="py-2 pr-3">
                                            Citas atendidas
                                        </th>
                                        <th className="py-2 pr-3">
                                            Proc. realizados
                                        </th>
                                        <th className="py-2 pr-3">
                                            Producción
                                        </th>
                                        <th className="py-2 pr-3">
                                            % Comisión
                                        </th>
                                        <th className="py-2 text-right">
                                            Honorarios estimados
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {dentistCommissions.map((dc) => (
                                        <tr key={dc.dentist_id}>
                                            <td className="py-2.5 pr-3">
                                                <div className="font-semibold">
                                                    {dc.name}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {dc.specialty ??
                                                        'Odontología General'}
                                                </div>
                                            </td>
                                            <td className="py-2.5 pr-3">
                                                {dc.appointments_attended}
                                            </td>
                                            <td className="py-2.5 pr-3">
                                                {dc.procedures_done}
                                            </td>
                                            <td className="py-2.5 pr-3 font-semibold">
                                                {currency}{' '}
                                                {dc.production_total.toFixed(2)}
                                            </td>
                                            <td className="py-2.5 pr-3">
                                                {dc.commission_rate.toFixed(1)}%
                                            </td>
                                            <td className="py-2.5 text-right font-bold text-[#0A6577]">
                                                {currency}{' '}
                                                {dc.commission_amount.toFixed(
                                                    2,
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
