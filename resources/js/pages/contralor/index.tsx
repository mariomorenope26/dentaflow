import { Head, Link, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowRight,
    CheckCircle2,
    FileWarning,
    ShieldAlert,
    Sparkles,
    Wallet,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Auth } from '@/types';

type Issue = {
    id: string;
    category: string;
    severity: 'alta' | 'media' | 'baja';
    title: string;
    detail: string;
    patient_name: string | null;
    action_label: string;
    action_url: string;
};

type Props = {
    audit: {
        compliance_score: number;
        total_patients_audited: number;
        total_issues: number;
        estimated_unbudgeted_value: number;
        counts: {
            missing_consent: number;
            unsigned_evolutions: number;
            incomplete_histories: number;
            unbudgeted_findings: number;
            lab_alerts: number;
            overdue_installments: number;
        };
        issues: Issue[];
    };
    clinicName: string;
};

export default function ContralorIndex({ audit, clinicName }: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const currency = auth.clinic?.currency_symbol ?? 'S/';
    const [severityFilter, setSeverityFilter] = useState<string>('todas');

    const filteredIssues = useMemo(() => {
        if (severityFilter === 'todas') return audit.issues;
        return audit.issues.filter((i) => i.severity === severityFilter);
    }, [audit.issues, severityFilter]);

    const scoreColor =
        audit.compliance_score >= 85
            ? 'text-[#2E9E6B]'
            : audit.compliance_score >= 65
              ? 'text-[#D97706]'
              : 'text-[#D64545]';

    const severityStyle = (sev: Issue['severity']) => {
        if (sev === 'alta') {
            return 'border-[#D64545]/40 bg-[#FEF2F2] text-[#991B1B]';
        }
        if (sev === 'media') {
            return 'border-[#F0A92E]/40 bg-[#FFFBEB] text-[#92400E]';
        }
        return 'border-[#0E8FA3]/30 bg-[#E6F5F7] text-[#0A6577]';
    };

    return (
        <>
            <Head title="Contralor IA · Auditoría Automática" />
            <div className="mx-auto max-w-7xl space-y-6 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 rounded-3xl bg-gradient-to-br from-[#062E39] via-[#094352] to-[#0E8FA3] p-6 text-white shadow-md sm:flex-row sm:items-center">
                    <div className="space-y-2">
                        <span className="inline-flex items-center gap-1.5 rounded-full border border-white/25 bg-white/10 px-3 py-1 text-xs font-semibold">
                            <Sparkles className="size-3.5 text-[#5EEAD4]" />
                            Contralor IA · Supervisión Inteligente
                        </span>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Auditoría Clínica, Legal y Financiera — {clinicName}
                        </h1>
                        <p className="max-w-2xl text-xs leading-relaxed text-cyan-100/90 sm:text-sm">
                            Escaneo automático en tiempo real de consentimientos
                            pendientes, notas SOAP sin firmar, historias HCE
                            incompletas, hallazgos sin presupuestar y entregas
                            de laboratorio.
                        </p>
                    </div>
                    <div className="shrink-0 rounded-2xl border border-white/20 bg-white/10 px-6 py-4 text-center backdrop-blur-md">
                        <div className="text-[11px] font-semibold tracking-wider text-cyan-100 uppercase">
                            Cumplimiento
                        </div>
                        <div className="mt-1 text-4xl font-extrabold text-[#5EEAD4]">
                            {audit.compliance_score}%
                        </div>
                        <div className="mt-0.5 text-[11px] text-cyan-100/80">
                            {audit.total_patients_audited} fichas auditadas
                        </div>
                    </div>
                </div>

                {/* KPIs resumidos */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="rounded-2xl border bg-card p-4 shadow-xs">
                        <div className="flex items-center justify-between text-xs text-muted-foreground">
                            <span>Consentimientos faltantes</span>
                            <ShieldAlert className="size-4 text-[#D64545]" />
                        </div>
                        <div
                            className={`mt-2 text-2xl font-bold ${scoreColor}`}
                        >
                            {audit.counts.missing_consent}
                        </div>
                        <div className="text-[11px] text-muted-foreground">
                            Planes activos sin firma legal
                        </div>
                    </div>

                    <div className="rounded-2xl border bg-card p-4 shadow-xs">
                        <div className="flex items-center justify-between text-xs text-muted-foreground">
                            <span>Notas SOAP sin firmar</span>
                            <FileWarning className="size-4 text-[#D97706]" />
                        </div>
                        <div className="mt-2 text-2xl font-bold">
                            {audit.counts.unsigned_evolutions}
                        </div>
                        <div className="text-[11px] text-muted-foreground">
                            Borradores pendientes de sellado SHA-256
                        </div>
                    </div>

                    <div className="rounded-2xl border bg-card p-4 shadow-xs">
                        <div className="flex items-center justify-between text-xs text-muted-foreground">
                            <span>Historias HCE incompletas</span>
                            <AlertTriangle className="size-4 text-[#0E8FA3]" />
                        </div>
                        <div className="mt-2 text-2xl font-bold">
                            {audit.counts.incomplete_histories}
                        </div>
                        <div className="text-[11px] text-muted-foreground">
                            Falta motivo de consulta o ASA
                        </div>
                    </div>

                    <div className="rounded-2xl border bg-card p-4 shadow-xs">
                        <div className="flex items-center justify-between text-xs text-muted-foreground">
                            <span>Potencial sin presupuestar</span>
                            <Wallet className="size-4 text-[#2E9E6B]" />
                        </div>
                        <div className="mt-2 text-2xl font-bold text-[#2E9E6B]">
                            {currency}{' '}
                            {audit.estimated_unbudgeted_value.toFixed(2)}
                        </div>
                        <div className="text-[11px] text-muted-foreground">
                            {audit.counts.unbudgeted_findings} hallazgo(s) en
                            odontograma
                        </div>
                    </div>
                </div>

                {/* Filtros de severidad */}
                <div className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border bg-card p-4 shadow-xs">
                    <div className="flex flex-wrap gap-2">
                        {(
                            [
                                ['todas', `Todas (${audit.issues.length})`],
                                ['alta', 'Prioridad Alta'],
                                ['media', 'Prioridad Media'],
                                ['baja', 'Preventivas / Stock'],
                            ] as const
                        ).map(([key, label]) => (
                            <button
                                key={key}
                                type="button"
                                onClick={() => setSeverityFilter(key)}
                                className={`rounded-full px-3.5 py-1.5 text-xs font-semibold transition ${
                                    severityFilter === key
                                        ? 'bg-[#0E8FA3] text-white'
                                        : 'border bg-muted/40 text-muted-foreground hover:bg-muted'
                                }`}
                            >
                                {label}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Listado de hallazgos accionables */}
                {filteredIssues.length === 0 ? (
                    <div className="flex flex-col items-center justify-center rounded-2xl border bg-card p-12 text-center shadow-xs">
                        <CheckCircle2 className="mb-3 size-10 text-[#2E9E6B]" />
                        <h2 className="text-lg font-bold">
                            ¡Excelente control del consultorio!
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            No existen observaciones pendientes en esta
                            categoría.
                        </p>
                    </div>
                ) : (
                    <div className="space-y-3">
                        {filteredIssues.map((issue) => (
                            <div
                                key={issue.id}
                                className="flex flex-col justify-between gap-4 rounded-2xl border bg-card p-4 shadow-xs sm:flex-row sm:items-center"
                            >
                                <div className="space-y-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span
                                            className={`rounded-full border px-2.5 py-0.5 text-[11px] font-bold uppercase ${severityStyle(issue.severity)}`}
                                        >
                                            {issue.severity}
                                        </span>
                                        <Badge variant="outline">
                                            {issue.category}
                                        </Badge>
                                        {issue.patient_name && (
                                            <span className="text-xs font-semibold text-[#0A6577]">
                                                Paciente: {issue.patient_name}
                                            </span>
                                        )}
                                    </div>
                                    <div className="text-sm font-bold text-foreground">
                                        {issue.title}
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        {issue.detail}
                                    </p>
                                </div>

                                <Link
                                    href={issue.action_url}
                                    className="shrink-0"
                                >
                                    <Button
                                        size="sm"
                                        className="gap-1.5 bg-[#0E8FA3] text-white hover:bg-[#0A6577]"
                                    >
                                        {issue.action_label}
                                        <ArrowRight className="size-3.5" />
                                    </Button>
                                </Link>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
