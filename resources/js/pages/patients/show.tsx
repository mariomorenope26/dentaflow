import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    CalendarDays,
    CalendarPlus,
    FileDown,
    FileText,
    Pencil,
    Phone,
    Search,
    ShieldAlert,
    Sparkles,
    Stethoscope,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { OdontogramChart } from '@/components/odontogram-chart';
import {
    MedicalHistoryTab,
    QuickAppointmentModal,
    TreatmentPlansTab,
} from '@/components/patient-clinical-tabs';
import {
    DocumentsTab,
    ImagesTab,
    PaymentsTab,
    PerioOrthoTab,
} from '@/components/patient-extra-tabs';
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
import type {
    Auth,
    FinancialSummary,
    OdontogramCondition,
    Patient,
    TimelineEvent,
    TreatmentCatalogItem,
} from '@/types';

type AiSummary = {
    provider: string;
    risk_level: string;
    headline: string;
    bullets: { label: string; detail: string; tone: string }[];
    recommended_next_step: string;
};

function PatientAiSummaryCard({ patientId }: { patientId: number }) {
    const [summary, setSummary] = useState<AiSummary | null>(null);
    const [loading, setLoading] = useState(false);

    const generate = async () => {
        setLoading(true);
        try {
            const xsrf = decodeURIComponent(
                document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '',
            );
            const res = await fetch(
                `/patients/${patientId}/ai-clinical-summary`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-XSRF-TOKEN': xsrf,
                    },
                },
            );
            const body = await res.json();
            if (!res.ok) {
                toast.error(body.message ?? 'No se pudo generar el resumen.');
                return;
            }
            setSummary(body);
        } catch {
            toast.error('Error al generar resumen clínico IA.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="rounded-2xl border border-[#0E8FA3]/30 bg-gradient-to-br from-[#F0FAFC] to-white p-5 shadow-xs dark:from-cyan-950/30 dark:to-card">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <div className="flex items-center gap-2">
                    <Sparkles className="size-4 text-[#0E8FA3]" />
                    <h3 className="text-sm font-bold text-[#062E39] dark:text-cyan-100">
                        Resumen Clínico IA Pre-Consulta
                    </h3>
                </div>
                <Button
                    type="button"
                    size="sm"
                    disabled={loading}
                    onClick={() => void generate()}
                    className="h-8 gap-1.5 bg-[#0E8FA3] text-xs text-white hover:bg-[#0A6577]"
                >
                    <Sparkles className="size-3.5" />
                    {loading
                        ? 'Analizando...'
                        : summary
                          ? 'Actualizar resumen'
                          : 'Generar resumen IA'}
                </Button>
            </div>

            {!summary ? (
                <p className="mt-2 text-xs text-muted-foreground">
                    Sintetiza en segundos alergias, riesgo ASA, hallazgos del
                    odontograma, última evolución, saldo y laboratorio pendiente
                    antes de iniciar la atención.
                </p>
            ) : (
                <div className="mt-3 space-y-2.5 text-xs">
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge
                            variant={
                                summary.risk_level === 'alto'
                                    ? 'destructive'
                                    : 'secondary'
                            }
                            className="uppercase"
                        >
                            Riesgo {summary.risk_level}
                        </Badge>
                        <span className="font-semibold text-foreground">
                            {summary.headline}
                        </span>
                    </div>
                    <div className="space-y-1.5">
                        {summary.bullets.map((b, i) => (
                            <div
                                key={i}
                                className="rounded-xl border bg-background/80 px-3 py-2"
                            >
                                <div className="font-bold text-[#0A6577] dark:text-cyan-300">
                                    {b.label}
                                </div>
                                <div className="text-muted-foreground">
                                    {b.detail}
                                </div>
                            </div>
                        ))}
                    </div>
                    <div className="rounded-xl border border-[#2E9E6B]/30 bg-[#E3F6F1]/60 px-3 py-2 text-[#166534] dark:bg-emerald-950/30 dark:text-emerald-200">
                        <strong>Siguiente paso sugerido: </strong>
                        {summary.recommended_next_step}
                    </div>
                </div>
            )}
        </div>
    );
}

type Props = {
    patient: Patient;
    timeline: TimelineEvent[];
    dentists: { id: number; name: string; specialty?: string | null }[];
    treatmentCatalog: TreatmentCatalogItem[];
    odontogramConditions: OdontogramCondition[];
    cie10Codes: { code: string; label: string }[];
    evolutionTemplates: {
        title: string;
        procedure: string;
        notes: string;
        prescription: string;
    }[];
    documentTemplates: { type: string; title: string; content: string }[];
    financialSummary: FinancialSummary;
    initialTab: string;
};

type TabKey =
    | 'resumen'
    | 'historia'
    | 'odontograma'
    | 'perio'
    | 'tratamientos'
    | 'pagos'
    | 'imagenes'
    | 'documentos'
    | 'citas';

export default function PatientShow({
    patient,
    timeline,
    dentists,
    treatmentCatalog,
    odontogramConditions,
    cie10Codes,
    evolutionTemplates,
    documentTemplates,
    financialSummary,
    initialTab,
}: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const canEditClinical = Boolean(auth.user?.can.edit_clinical);
    const canManagePatients = Boolean(auth.user?.can.manage_patients);
    const canManageAppointments = Boolean(auth.user?.can.manage_appointments);
    const canManageCash = Boolean(auth.user?.can.manage_cash);
    const currency = auth.clinic?.currency_symbol ?? 'S/';

    const validTabs: TabKey[] = [
        'resumen',
        'historia',
        'odontograma',
        'perio',
        'tratamientos',
        'pagos',
        'imagenes',
        'documentos',
        'citas',
    ];
    const [activeTab, setActiveTab] = useState<TabKey>(
        validTabs.includes(initialTab as TabKey)
            ? (initialTab as TabKey)
            : 'resumen',
    );
    const [openNewAppointment, setOpenNewAppointment] = useState(false);
    const [openEditPatient, setOpenEditPatient] = useState(false);
    const [openDeletePatient, setOpenDeletePatient] = useState(false);
    const [lookingUpDni, setLookingUpDni] = useState(false);

    const patientTags = [
        'Ortodoncia',
        'Implante',
        'Endodoncia',
        'Prioritario',
        'Alergia',
        'Control',
        'VIP',
    ];

    const editForm = useForm({
        first_name: patient.first_name,
        last_name: patient.last_name,
        document_type: patient.document_type || 'DNI',
        document_number: patient.document_number ?? '',
        birth_date: patient.birth_date ? patient.birth_date.slice(0, 10) : '',
        gender: patient.gender ?? 'F',
        blood_type: patient.blood_type ?? '',
        phone: patient.phone ?? '',
        email: patient.email ?? '',
        address: patient.address ?? '',
        occupation: patient.occupation ?? '',
        insurance_provider: patient.insurance_provider ?? '',
        insurance_number: patient.insurance_number ?? '',
        guardian_name: patient.guardian_name ?? '',
        guardian_phone: patient.guardian_phone ?? '',
        guardian_relationship: patient.guardian_relationship ?? '',
        emergency_contact_name: patient.emergency_contact_name ?? '',
        emergency_contact_phone: patient.emergency_contact_phone ?? '',
        tags: patient.tags ?? ([] as string[]),
        notes: patient.notes ?? '',
    });

    const openEditModal = () => {
        editForm.setData({
            first_name: patient.first_name,
            last_name: patient.last_name,
            document_type: patient.document_type || 'DNI',
            document_number: patient.document_number ?? '',
            birth_date: patient.birth_date
                ? patient.birth_date.slice(0, 10)
                : '',
            gender: patient.gender ?? 'F',
            blood_type: patient.blood_type ?? '',
            phone: patient.phone ?? '',
            email: patient.email ?? '',
            address: patient.address ?? '',
            occupation: patient.occupation ?? '',
            insurance_provider: patient.insurance_provider ?? '',
            insurance_number: patient.insurance_number ?? '',
            guardian_name: patient.guardian_name ?? '',
            guardian_phone: patient.guardian_phone ?? '',
            guardian_relationship: patient.guardian_relationship ?? '',
            emergency_contact_name: patient.emergency_contact_name ?? '',
            emergency_contact_phone: patient.emergency_contact_phone ?? '',
            tags: patient.tags ?? [],
            notes: patient.notes ?? '',
        });
        setOpenEditPatient(true);
    };

    const lookupEditDni = async () => {
        const clean = editForm.data.document_number.replace(/\D/g, '');
        if (!/^\d{8}$/.test(clean)) {
            toast.error('Ingresa un DNI válido de 8 dígitos.');
            return;
        }
        setLookingUpDni(true);
        try {
            const xsrf = decodeURIComponent(
                document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '',
            );
            const res = await fetch('/api-integrations/dni-lookup', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': xsrf,
                },
                body: JSON.stringify({ dni: clean }),
            });
            const body = await res.json();
            if (!res.ok) {
                toast.error(body.message ?? 'No se pudo consultar el DNI.');
                return;
            }
            if (body.found_local) {
                if (body.patient.id === patient.id) {
                    toast.info('Este DNI ya pertenece a esta ficha.');
                } else {
                    toast.warning(
                        `Ese DNI ya está asignado a ${body.patient.full_name}.`,
                    );
                }
                return;
            }
            editForm.setData((prev) => ({
                ...prev,
                first_name: body.data.first_name || prev.first_name,
                last_name: body.data.last_name || prev.last_name,
                address: body.data.address || prev.address,
            }));
            toast.success('Datos completados desde RENIEC.');
        } catch {
            toast.error('Error al consultar RENIEC.');
        } finally {
            setLookingUpDni(false);
        }
    };

    const submitEditPatient = (e: React.FormEvent) => {
        e.preventDefault();
        editForm.patch(`/patients/${patient.id}`, {
            preserveScroll: true,
            onSuccess: () => setOpenEditPatient(false),
        });
    };

    const confirmDeletePatient = () => {
        router.delete(`/patients/${patient.id}`);
    };

    const alerts = patient.medical_history?.medical_alerts ?? [];
    const allergies = patient.medical_history?.allergies;
    const hasAnyAlert = alerts.length > 0 || Boolean(allergies);

    const defaultNotation =
        (auth.clinic?.settings?.odontogram_notation as
            | 'FDI'
            | 'UNIVERSAL'
            | undefined) ?? 'FDI';

    const initials =
        `${patient.first_name[0] ?? ''}${patient.last_name[0] ?? ''}`.toUpperCase();

    return (
        <>
            <Head title={`${patient.full_name} (${patient.history_number})`} />
            <div className="mx-auto max-w-7xl space-y-5 p-4 md:p-6">
                {/* Cabecera de la ficha clínica */}
                <div className="rounded-2xl border bg-card p-5 shadow-xs">
                    <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                        <div className="flex items-start gap-4">
                            <div className="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-[#0E8FA3] text-lg font-bold text-white">
                                {initials}
                            </div>
                            <div className="space-y-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="rounded-lg bg-[#E6F5F7] px-2.5 py-0.5 text-xs font-bold text-[#0A6577]">
                                        {patient.history_number}
                                    </span>
                                    <Badge
                                        variant="outline"
                                        className="border-[#0E8FA3]/40 text-[10px] text-[#0A6577]"
                                    >
                                        HCE HL7 FHIR R4
                                    </Badge>
                                    {patient.blood_type && (
                                        <Badge className="bg-rose-100 text-[10px] text-rose-800 hover:bg-rose-100">
                                            Grupo {patient.blood_type}
                                        </Badge>
                                    )}
                                    <h1 className="text-xl font-bold text-foreground sm:text-2xl">
                                        {patient.full_name}
                                    </h1>
                                    {(patient.tags ?? []).map((t) => (
                                        <Badge key={t} variant="secondary">
                                            {t}
                                        </Badge>
                                    ))}
                                </div>
                                <div className="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                                    <span>
                                        {patient.document_type}{' '}
                                        {patient.document_number ?? 'S/N'}
                                    </span>
                                    <span>
                                        {patient.age !== null
                                            ? `${patient.age} años`
                                            : 'Edad S/R'}
                                    </span>
                                    {patient.phone && (
                                        <span className="inline-flex items-center gap-1">
                                            <Phone className="size-3" />
                                            {patient.phone}
                                        </span>
                                    )}
                                    <span>
                                        Seguro:{' '}
                                        {patient.insurance_provider ||
                                            'Particular'}
                                    </span>
                                    <span className="font-semibold text-foreground">
                                        Saldo: {currency}{' '}
                                        {financialSummary.balance_due.toFixed(
                                            2,
                                        )}
                                    </span>
                                    {patient.guardian_name && (
                                        <span>
                                            Apoderado: {patient.guardian_name} (
                                            {patient.guardian_phone ?? '-'})
                                        </span>
                                    )}
                                    {patient.emergency_contact_name && (
                                        <span>
                                            Emergencia:{' '}
                                            {patient.emergency_contact_name} (
                                            {patient.emergency_contact_phone ??
                                                '-'}
                                            )
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            {canManagePatients && (
                                <>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        className="h-10 gap-1.5 rounded-xl text-xs"
                                        onClick={openEditModal}
                                    >
                                        <Pencil className="size-3.5 text-[#0E8FA3]" />
                                        Editar datos
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        className="h-10 gap-1.5 rounded-xl border-[#D64545]/40 text-xs text-[#D64545] hover:bg-red-50"
                                        onClick={() =>
                                            setOpenDeletePatient(true)
                                        }
                                    >
                                        <Trash2 className="size-3.5" />
                                        Eliminar
                                    </Button>
                                </>
                            )}

                            {canManageAppointments && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    className="h-10 gap-1.5 rounded-xl text-xs"
                                    onClick={() => setOpenNewAppointment(true)}
                                >
                                    <CalendarPlus className="size-4 text-[#0E8FA3]" />
                                    Nueva cita
                                </Button>
                            )}

                            <a
                                href={`/patients/${patient.id}/pdf/history`}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex h-10 items-center gap-1.5 rounded-xl border bg-background px-3.5 text-xs font-semibold hover:bg-accent"
                            >
                                <FileDown className="size-4 text-[#0E8FA3]" />
                                Imprimir / PDF Historia
                            </a>

                            <a
                                href={`/patients/${patient.id}/fhir-bundle`}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex h-10 items-center gap-1.5 rounded-xl border bg-background px-3 text-xs font-semibold hover:bg-accent"
                                title="Descargar Historia Clínica Electrónica interoperable HL7 FHIR R4 (JSON)"
                            >
                                <FileText className="size-4 text-[#0E8FA3]" />
                                HL7 FHIR
                            </a>

                            {canEditClinical && (
                                <Button
                                    type="button"
                                    size="sm"
                                    onClick={() => setActiveTab('historia')}
                                    className="h-10 gap-1.5 rounded-xl bg-[#0E8FA3] px-4 text-xs font-semibold text-white hover:bg-[#0A6577]"
                                >
                                    <Stethoscope className="size-4" />
                                    Nueva atención
                                </Button>
                            )}
                        </div>
                    </div>

                    {hasAnyAlert && (
                        <div className="mt-4 flex flex-wrap items-center gap-2 rounded-xl border border-[#D64545]/50 bg-[#FEF2F2] px-4 py-2.5 text-xs text-[#991B1B] dark:bg-red-950/30 dark:text-red-200">
                            <span className="inline-flex items-center gap-1.5 font-bold">
                                <ShieldAlert className="size-4 text-[#D64545]" />
                                ALERTAS MÉDICAS:
                            </span>
                            {allergies && (
                                <span className="rounded-full bg-[#D64545] px-2.5 py-0.5 font-semibold text-white">
                                    Alergia: {allergies}
                                </span>
                            )}
                            {alerts.map((al, idx) => (
                                <span
                                    key={`${al.label}-${idx}`}
                                    className="inline-flex items-center gap-1 rounded-full border border-[#D64545] bg-white px-2.5 py-0.5 font-semibold text-[#D64545] dark:bg-red-950"
                                >
                                    <AlertTriangle className="size-3" />
                                    {al.label}
                                </span>
                            ))}
                        </div>
                    )}
                </div>

                {/* Pestañas clínicas */}
                <div className="flex gap-1 overflow-x-auto rounded-2xl border bg-card p-1.5 shadow-xs">
                    {(
                        [
                            ['resumen', 'Resumen'],
                            ['historia', 'Historia clínica'],
                            ['odontograma', 'Odontograma'],
                            ['perio', 'Perio y Ortodoncia'],
                            ['tratamientos', 'Tratamientos'],
                            ['pagos', 'Pagos y Saldo'],
                            ['imagenes', 'Imágenes'],
                            ['documentos', 'Documentos'],
                            ['citas', 'Citas'],
                        ] as const
                    ).map(([key, label]) => (
                        <button
                            key={key}
                            type="button"
                            onClick={() => setActiveTab(key)}
                            className={`min-h-10 shrink-0 rounded-xl px-3.5 py-2 text-xs font-semibold transition-colors sm:text-sm ${
                                activeTab === key
                                    ? 'bg-[#0E8FA3] text-white shadow-xs'
                                    : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground'
                            }`}
                        >
                            {label}
                        </button>
                    ))}
                </div>

                {activeTab === 'resumen' && (
                    <div className="grid gap-6 lg:grid-cols-12">
                        <div className="space-y-4 lg:col-span-7">
                            <div className="rounded-2xl border bg-card p-5 shadow-xs">
                                <h3 className="mb-3 text-base font-bold">
                                    Línea de tiempo del paciente
                                </h3>
                                {timeline.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        Sin movimientos registrados aún.
                                    </p>
                                ) : (
                                    <div className="space-y-3 border-l-2 border-[#0E8FA3]/30 pl-4">
                                        {timeline.map((item) => (
                                            <div
                                                key={item.id}
                                                className="relative rounded-xl border bg-background p-3"
                                            >
                                                <div className="flex items-center justify-between text-xs">
                                                    <Badge
                                                        variant="secondary"
                                                        className="capitalize"
                                                    >
                                                        {item.kind}
                                                    </Badge>
                                                    <span className="text-muted-foreground">
                                                        {new Date(
                                                            item.date,
                                                        ).toLocaleString(
                                                            'es-PE',
                                                        )}
                                                    </span>
                                                </div>
                                                <div className="mt-1.5 text-sm font-semibold">
                                                    {item.title}
                                                </div>
                                                <div className="mt-0.5 text-xs text-muted-foreground">
                                                    {item.detail}
                                                </div>
                                                {item.actor && (
                                                    <div className="mt-1 text-[11px] font-medium text-[#0A6577]">
                                                        Profesional:{' '}
                                                        {item.actor}
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="space-y-4 lg:col-span-5">
                            <PatientAiSummaryCard patientId={patient.id} />

                            <div className="rounded-2xl border bg-card p-5 shadow-xs">
                                <h3 className="mb-3 text-base font-bold">
                                    Resumen diagnóstico
                                </h3>
                                <dl className="space-y-2 text-sm">
                                    <div>
                                        <dt className="text-xs font-semibold text-muted-foreground">
                                            Motivo de consulta
                                        </dt>
                                        <dd className="mt-0.5">
                                            {patient.medical_history
                                                ?.chief_complaint ||
                                                'No registrado'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs font-semibold text-muted-foreground">
                                            Diagnóstico definitivo
                                        </dt>
                                        <dd className="mt-0.5 font-medium">
                                            {patient.medical_history?.cie10_code
                                                ? `[${patient.medical_history.cie10_code}] `
                                                : ''}
                                            {patient.medical_history
                                                ?.definitive_diagnosis ||
                                                patient.medical_history
                                                    ?.presumptive_diagnosis ||
                                                'Pendiente de diagnóstico'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs font-semibold text-muted-foreground">
                                            Medicación habitual
                                        </dt>
                                        <dd className="mt-0.5">
                                            {patient.medical_history
                                                ?.current_medications ||
                                                'Ninguna'}
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <div className="rounded-2xl border bg-card p-5 shadow-xs">
                                <h3 className="mb-3 text-base font-bold">
                                    Accesos directos
                                </h3>
                                <div className="grid grid-cols-2 gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="h-11 justify-start gap-2 rounded-xl text-xs"
                                        onClick={() =>
                                            setActiveTab('odontograma')
                                        }
                                    >
                                        <FileText className="size-4 text-[#0E8FA3]" />
                                        Ver Odontograma
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="h-11 justify-start gap-2 rounded-xl text-xs"
                                        onClick={() => setActiveTab('pagos')}
                                    >
                                        <CalendarDays className="size-4 text-[#2E9E6B]" />
                                        Ver Pagos y Saldo
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {activeTab === 'historia' && (
                    <MedicalHistoryTab
                        patient={patient}
                        cie10Codes={cie10Codes}
                        canEdit={canEditClinical}
                        evolutionTemplates={evolutionTemplates}
                    />
                )}

                {activeTab === 'odontograma' && (
                    <OdontogramChart
                        patientId={patient.id}
                        odontograms={patient.odontograms ?? []}
                        conditions={odontogramConditions}
                        canEdit={canEditClinical}
                        defaultNotation={defaultNotation}
                    />
                )}

                {activeTab === 'perio' && (
                    <PerioOrthoTab
                        patient={patient}
                        periodontograms={patient.periodontograms ?? []}
                        orthodonticControls={patient.orthodontic_controls ?? []}
                        canEdit={canEditClinical}
                    />
                )}

                {activeTab === 'tratamientos' && (
                    <TreatmentPlansTab
                        patient={patient}
                        plans={patient.treatment_plans ?? []}
                        catalog={treatmentCatalog}
                        canEdit={canEditClinical}
                    />
                )}

                {activeTab === 'pagos' && (
                    <PaymentsTab
                        patient={patient}
                        payments={patient.payments ?? []}
                        plans={patient.treatment_plans ?? []}
                        summary={financialSummary}
                        canManageCash={canManageCash}
                    />
                )}

                {activeTab === 'imagenes' && (
                    <ImagesTab
                        patient={patient}
                        images={patient.images ?? []}
                        canEdit={canEditClinical}
                    />
                )}

                {activeTab === 'documentos' && (
                    <DocumentsTab
                        patient={patient}
                        documents={patient.clinical_documents ?? []}
                        templates={documentTemplates}
                        canEdit={canEditClinical}
                    />
                )}

                {activeTab === 'citas' && (
                    <div className="rounded-2xl border bg-card p-5 shadow-xs">
                        <div className="mb-4 flex items-center justify-between">
                            <h3 className="text-base font-bold">
                                Historial y próximas citas
                            </h3>
                            {canManageAppointments && (
                                <Button
                                    size="sm"
                                    className="gap-1.5 rounded-xl bg-[#0E8FA3] text-white"
                                    onClick={() => setOpenNewAppointment(true)}
                                >
                                    <CalendarPlus className="size-4" />
                                    Agendar cita
                                </Button>
                            )}
                        </div>
                        {(patient.appointments ?? []).length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Sin citas registradas.
                            </p>
                        ) : (
                            <div className="divide-y">
                                {(patient.appointments ?? []).map((appt) => (
                                    <div
                                        key={appt.id}
                                        className="flex flex-wrap items-center justify-between gap-2 py-3"
                                    >
                                        <div>
                                            <div className="font-semibold">
                                                {appt.title}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {new Date(
                                                    appt.starts_at,
                                                ).toLocaleString('es-PE')}{' '}
                                                · {appt.chair} ·{' '}
                                                {appt.dentist?.name}
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Badge
                                                variant="outline"
                                                className="capitalize"
                                            >
                                                {appt.status.replace('_', ' ')}
                                            </Badge>
                                            <Link
                                                href="/agenda"
                                                className="text-xs font-semibold text-[#0E8FA3]"
                                            >
                                                Ir a agenda
                                            </Link>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}
            </div>

            <QuickAppointmentModal
                open={openNewAppointment}
                onOpenChange={setOpenNewAppointment}
                patientId={patient.id}
                dentists={dentists}
                chairs={auth.clinic?.chairs ?? ['Sillón 1']}
            />

            {/* Modal Editar Datos de Filiación */}
            <Dialog open={openEditPatient} onOpenChange={setOpenEditPatient}>
                <DialogContent className="max-h-[90vh] max-w-2xl overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>
                            Editar filiación ({patient.history_number})
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitEditPatient} className="space-y-4">
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div>
                                <Label>Nombres *</Label>
                                <Input
                                    required
                                    value={editForm.data.first_name}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'first_name',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Apellidos *</Label>
                                <Input
                                    required
                                    value={editForm.data.last_name}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'last_name',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Tipo de documento</Label>
                                <select
                                    value={editForm.data.document_type}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'document_type',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                                >
                                    <option value="DNI">DNI</option>
                                    <option value="CE">
                                        Carné de extranjería
                                    </option>
                                    <option value="PASAPORTE">Pasaporte</option>
                                    <option value="OTRO">Otro</option>
                                </select>
                            </div>
                            <div>
                                <Label>Número de documento</Label>
                                <div className="mt-1 flex gap-2">
                                    <Input
                                        value={editForm.data.document_number}
                                        onChange={(e) =>
                                            editForm.setData(
                                                'document_number',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    {editForm.data.document_type === 'DNI' && (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            disabled={lookingUpDni}
                                            onClick={() => void lookupEditDni()}
                                        >
                                            <Search className="size-3.5" />
                                        </Button>
                                    )}
                                </div>
                            </div>
                            <div>
                                <Label>Fecha de nacimiento</Label>
                                <Input
                                    type="date"
                                    value={editForm.data.birth_date}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'birth_date',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Sexo</Label>
                                <select
                                    value={editForm.data.gender}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'gender',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                                >
                                    <option value="F">Femenino</option>
                                    <option value="M">Masculino</option>
                                    <option value="O">Otro</option>
                                </select>
                            </div>
                            <div>
                                <Label>Grupo Sanguíneo / Factor Rh (HCE)</Label>
                                <select
                                    value={editForm.data.blood_type}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'blood_type',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                                >
                                    <option value="">No registrado</option>
                                    {[
                                        'O+',
                                        'O-',
                                        'A+',
                                        'A-',
                                        'B+',
                                        'B-',
                                        'AB+',
                                        'AB-',
                                    ].map((bt) => (
                                        <option key={bt} value={bt}>
                                            {bt}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label>Teléfono / WhatsApp</Label>
                                <Input
                                    value={editForm.data.phone}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'phone',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Correo electrónico</Label>
                                <Input
                                    type="email"
                                    value={editForm.data.email}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'email',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="sm:col-span-2">
                                <Label>Dirección</Label>
                                <Input
                                    value={editForm.data.address}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'address',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Ocupación</Label>
                                <Input
                                    value={editForm.data.occupation}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'occupation',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Seguro médico</Label>
                                <Input
                                    value={editForm.data.insurance_provider}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'insurance_provider',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>

                        <div className="grid gap-2 sm:grid-cols-3">
                            <Input
                                placeholder="Nombre apoderado"
                                value={editForm.data.guardian_name}
                                onChange={(e) =>
                                    editForm.setData(
                                        'guardian_name',
                                        e.target.value,
                                    )
                                }
                            />
                            <Input
                                placeholder="Parentesco"
                                value={editForm.data.guardian_relationship}
                                onChange={(e) =>
                                    editForm.setData(
                                        'guardian_relationship',
                                        e.target.value,
                                    )
                                }
                            />
                            <Input
                                placeholder="Teléfono apoderado"
                                value={editForm.data.guardian_phone}
                                onChange={(e) =>
                                    editForm.setData(
                                        'guardian_phone',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>

                        <div className="grid gap-2 sm:grid-cols-2">
                            <Input
                                placeholder="Contacto de emergencia (Nombre)"
                                value={editForm.data.emergency_contact_name}
                                onChange={(e) =>
                                    editForm.setData(
                                        'emergency_contact_name',
                                        e.target.value,
                                    )
                                }
                            />
                            <Input
                                placeholder="Teléfono de emergencia"
                                value={editForm.data.emergency_contact_phone}
                                onChange={(e) =>
                                    editForm.setData(
                                        'emergency_contact_phone',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>

                        <div>
                            <Label className="mb-1.5 block">Etiquetas</Label>
                            <div className="flex flex-wrap gap-2">
                                {patientTags.map((tag) => (
                                    <button
                                        key={tag}
                                        type="button"
                                        onClick={() =>
                                            editForm.setData(
                                                'tags',
                                                editForm.data.tags.includes(tag)
                                                    ? editForm.data.tags.filter(
                                                          (t) => t !== tag,
                                                      )
                                                    : [
                                                          ...editForm.data.tags,
                                                          tag,
                                                      ],
                                            )
                                        }
                                        className={`rounded-full border px-3 py-1 text-xs font-medium ${
                                            editForm.data.tags.includes(tag)
                                                ? 'border-[#0E8FA3] bg-[#0E8FA3] text-white'
                                                : 'bg-background text-muted-foreground'
                                        }`}
                                    >
                                        {tag}
                                    </button>
                                ))}
                            </div>
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setOpenEditPatient(false)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                disabled={editForm.processing}
                                className="bg-[#0E8FA3] text-white hover:bg-[#0A6577]"
                            >
                                Guardar cambios
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Confirmar Eliminación */}
            <Dialog
                open={openDeletePatient}
                onOpenChange={setOpenDeletePatient}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="text-[#D64545]">
                            Eliminar paciente
                        </DialogTitle>
                        <DialogDescription>
                            ¿Confirmas que deseas eliminar la ficha completa de{' '}
                            <strong>{patient.full_name}</strong> (
                            {patient.history_number})? Se eliminarán también sus
                            odontogramas, evoluciones, documentos, citas y
                            pagos.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpenDeletePatient(false)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={confirmDeletePatient}
                        >
                            Eliminar paciente
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
