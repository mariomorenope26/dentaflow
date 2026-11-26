import { Head, Link, router } from '@inertiajs/react';
import {
    Copy,
    HeartHandshake,
    MessageCircle,
    Search,
    Sparkles,
} from 'lucide-react';
import { useState } from 'react';
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

type CrmPatient = {
    id: number;
    history_number: string;
    full_name: string;
    phone: string | null;
    email: string | null;
    crm_stage: string;
    last_contacted_at: string | null;
    crm_notes: string | null;
    pending_budget: number;
    pending_balance: number;
    days_since_visit: number;
    overdue_count: number;
    suggested_campaign: string;
};

type Props = {
    patients: CrmPatient[];
    stageCounts: Record<string, number>;
    currencySymbol: string;
    onlineBookingUrl: string | null;
    filters: { stage: string; search: string };
};

const STAGES = [
    { value: 'nuevo', label: 'Nuevo contacto' },
    { value: 'presupuesto_pendiente', label: 'Presupuesto pendiente' },
    { value: 'en_tratamiento', label: 'En tratamiento' },
    { value: 'mantenimiento', label: 'Mantenimiento 6m' },
    { value: 'inactivo', label: 'Reactivación' },
];

const CAMPAIGN_LABELS: Record<string, string> = {
    presupuesto: 'Seguimiento de presupuesto',
    morosidad: 'Recordatorio amable de cuota/saldo',
    recall_6m: 'Control preventivo 6 meses',
    reactivacion: 'Reactivación paciente inactivo',
    post_atencion: 'Control post-atención',
};

export default function CrmIndex({
    patients,
    stageCounts,
    currencySymbol,
    onlineBookingUrl,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.search);
    const [draftTarget, setDraftTarget] = useState<CrmPatient | null>(null);
    const [campaignType, setCampaignType] = useState('presupuesto');
    const [draftMessage, setDraftMessage] = useState('');
    const [whatsappUrl, setWhatsappUrl] = useState<string | null>(null);
    const [generating, setGenerating] = useState(false);

    const applyFilter = (stage: string, q = search) => {
        router.get(
            '/crm',
            { stage: stage || undefined, search: q || undefined },
            { preserveState: true },
        );
    };

    const updateStage = (patientId: number, stage: string) => {
        router.patch(
            `/crm/patients/${patientId}`,
            { crm_stage: stage },
            { preserveScroll: true },
        );
    };

    const openWhatsappAssistant = async (
        patient: CrmPatient,
        type = patient.suggested_campaign,
    ) => {
        setDraftTarget(patient);
        setCampaignType(type);
        setGenerating(true);
        setDraftMessage('');
        setWhatsappUrl(null);
        try {
            const xsrf = decodeURIComponent(
                document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '',
            );
            const res = await fetch(
                `/crm/patients/${patient.id}/whatsapp-draft`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-XSRF-TOKEN': xsrf,
                    },
                    body: JSON.stringify({ campaign_type: type }),
                },
            );
            const body = await res.json();
            if (!res.ok) {
                toast.error(body.message ?? 'No se pudo redactar el mensaje.');
                return;
            }
            setDraftMessage(body.message ?? '');
            setWhatsappUrl(body.whatsapp_url ?? null);
        } catch {
            toast.error('Error al conectar con el asistente IA.');
        } finally {
            setGenerating(false);
        }
    };

    const copyBookingLink = () => {
        if (!onlineBookingUrl) return;
        void navigator.clipboard.writeText(onlineBookingUrl);
        toast.success('Enlace de Reserva Online 24/7 copiado al portapapeles.');
    };

    return (
        <>
            <Head title="CRM Odontológico y Retención WhatsApp" />
            <div className="mx-auto max-w-7xl space-y-6 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-bold">
                            <HeartHandshake className="size-6 text-[#0E8FA3]" />
                            CRM Odontológico y Retención WhatsApp
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Embudo de seguimiento de presupuestos, controles
                            preventivos cada 6 meses y mensajería IA (estilo
                            Doctocliq & Dentalink)
                        </p>
                    </div>
                    {onlineBookingUrl && (
                        <Button
                            type="button"
                            variant="outline"
                            onClick={copyBookingLink}
                            className="gap-1.5 rounded-xl border-[#0E8FA3]/40 text-xs text-[#0A6577]"
                        >
                            <Copy className="size-3.5" />
                            Copiar link Reserva Online 24/7
                        </Button>
                    )}
                </div>

                {/* Embudo de etapas */}
                <div className="grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    <button
                        type="button"
                        onClick={() => applyFilter('')}
                        className={`rounded-2xl border p-3.5 text-left transition ${
                            filters.stage === ''
                                ? 'border-[#0E8FA3] bg-[#E6F5F7]/60'
                                : 'bg-card hover:bg-muted/30'
                        }`}
                    >
                        <div className="text-[11px] font-semibold text-muted-foreground">
                            Todos activos
                        </div>
                        <div className="mt-1 text-2xl font-bold">
                            {Object.values(stageCounts).reduce(
                                (a, b) => a + b,
                                0,
                            )}
                        </div>
                    </button>
                    {STAGES.map((st) => (
                        <button
                            key={st.value}
                            type="button"
                            onClick={() => applyFilter(st.value)}
                            className={`rounded-2xl border p-3.5 text-left transition ${
                                filters.stage === st.value
                                    ? 'border-[#0E8FA3] bg-[#E6F5F7]/60'
                                    : 'bg-card hover:bg-muted/30'
                            }`}
                        >
                            <div className="text-[11px] font-semibold text-muted-foreground">
                                {st.label}
                            </div>
                            <div className="mt-1 text-2xl font-bold text-[#0A6577]">
                                {stageCounts[st.value] ?? 0}
                            </div>
                        </button>
                    ))}
                </div>

                {/* Buscador */}
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        applyFilter(filters.stage, search);
                    }}
                    className="flex flex-wrap gap-2 rounded-2xl border bg-card p-3 shadow-xs"
                >
                    <div className="relative flex-1">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Buscar paciente por nombre, DNI o teléfono..."
                            className="pl-9"
                        />
                    </div>
                    <Button
                        type="submit"
                        className="bg-[#0E8FA3] text-white hover:bg-[#0A6577]"
                    >
                        Filtrar
                    </Button>
                </form>

                {/* Tabla CRM */}
                <div className="overflow-hidden rounded-2xl border bg-card shadow-xs">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b bg-muted/40 text-xs text-muted-foreground">
                                <tr>
                                    <th className="px-4 py-3">Paciente</th>
                                    <th className="px-4 py-3">Etapa CRM</th>
                                    <th className="px-4 py-3">
                                        Presup. / Saldo
                                    </th>
                                    <th className="px-4 py-3">Últ. visita</th>
                                    <th className="px-4 py-3">
                                        Campaña sugerida
                                    </th>
                                    <th className="px-4 py-3 text-right">
                                        Asistente WhatsApp IA
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {patients.map((p) => (
                                    <tr
                                        key={p.id}
                                        className="hover:bg-muted/20"
                                    >
                                        <td className="px-4 py-3">
                                            <Link
                                                href={`/patients/${p.id}`}
                                                className="font-semibold hover:text-[#0E8FA3]"
                                            >
                                                {p.full_name}
                                            </Link>
                                            <div className="text-xs text-muted-foreground">
                                                {p.history_number} ·{' '}
                                                {p.phone ?? 'Sin teléfono'}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <select
                                                value={p.crm_stage}
                                                onChange={(e) =>
                                                    updateStage(
                                                        p.id,
                                                        e.target.value,
                                                    )
                                                }
                                                className="h-8 rounded-lg border bg-background px-2 text-xs font-medium"
                                            >
                                                {STAGES.map((st) => (
                                                    <option
                                                        key={st.value}
                                                        value={st.value}
                                                    >
                                                        {st.label}
                                                    </option>
                                                ))}
                                            </select>
                                        </td>
                                        <td className="px-4 py-3 text-xs">
                                            {p.pending_budget > 0 && (
                                                <div className="font-semibold text-[#D97706]">
                                                    Por aprobar:{' '}
                                                    {currencySymbol}{' '}
                                                    {p.pending_budget.toFixed(
                                                        2,
                                                    )}
                                                </div>
                                            )}
                                            {p.pending_balance > 0 ? (
                                                <div className="font-bold text-[#D64545]">
                                                    Saldo: {currencySymbol}{' '}
                                                    {p.pending_balance.toFixed(
                                                        2,
                                                    )}
                                                </div>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    Al día
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-xs text-muted-foreground">
                                            Hace {p.days_since_visit} día(s)
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge variant="outline">
                                                {CAMPAIGN_LABELS[
                                                    p.suggested_campaign
                                                ] ?? p.suggested_campaign}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <Button
                                                size="sm"
                                                onClick={() =>
                                                    void openWhatsappAssistant(
                                                        p,
                                                    )
                                                }
                                                className="gap-1.5 bg-[#2E9E6B] text-xs text-white hover:bg-[#238056]"
                                            >
                                                <Sparkles className="size-3.5" />
                                                Redactar IA
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <Dialog
                open={draftTarget !== null}
                onOpenChange={(open) => !open && setDraftTarget(null)}
            >
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Sparkles className="size-5 text-[#0E8FA3]" />
                            Asistente WhatsApp IA — {draftTarget?.full_name}
                        </DialogTitle>
                        <DialogDescription>
                            Personaliza el objetivo de contacto y genera un
                            mensaje listo para enviar por WhatsApp.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-3">
                        <div>
                            <Label>Tipo de campaña</Label>
                            <select
                                value={campaignType}
                                onChange={(e) => {
                                    const nextType = e.target.value;
                                    if (draftTarget) {
                                        void openWhatsappAssistant(
                                            draftTarget,
                                            nextType,
                                        );
                                    }
                                }}
                                className="mt-1 h-9 w-full rounded-md border bg-background px-3 text-sm"
                            >
                                {Object.entries(CAMPAIGN_LABELS).map(
                                    ([val, label]) => (
                                        <option key={val} value={val}>
                                            {label}
                                        </option>
                                    ),
                                )}
                            </select>
                        </div>

                        <div>
                            <Label>Mensaje redactado (editable)</Label>
                            <textarea
                                rows={5}
                                value={
                                    generating
                                        ? 'Redactando mensaje personalizado con IA...'
                                        : draftMessage
                                }
                                onChange={(e) =>
                                    setDraftMessage(e.target.value)
                                }
                                className="mt-1 w-full rounded-xl border bg-background p-3 text-sm"
                            />
                        </div>
                    </div>

                    <DialogFooter className="gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setDraftTarget(null)}
                        >
                            Cerrar
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => {
                                void navigator.clipboard.writeText(
                                    draftMessage,
                                );
                                toast.success('Mensaje copiado.');
                            }}
                        >
                            <Copy className="mr-1.5 size-4" />
                            Copiar texto
                        </Button>
                        {whatsappUrl && (
                            <a
                                href={whatsappUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex h-9 items-center gap-1.5 rounded-md bg-[#2E9E6B] px-4 text-xs font-semibold text-white hover:bg-[#238056]"
                            >
                                <MessageCircle className="size-4" />
                                Enviar por WhatsApp
                            </a>
                        )}
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
