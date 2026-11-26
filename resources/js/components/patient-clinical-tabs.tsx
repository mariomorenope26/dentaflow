import { router, useForm } from '@inertiajs/react';
import {
    AlertTriangle,
    CalendarPlus,
    CheckCircle2,
    Clock,
    FileCheck2,
    Lock,
    MessageSquarePlus,
    Plus,
    Save,
    Sparkles,
    Trash2,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
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
import { queueOfflineMutation } from '@/lib/offline-store';
import type {
    ClinicalEvolution,
    MedicalAlert,
    MedicalHistory,
    Patient,
    TreatmentCatalogItem,
    TreatmentPlan,
} from '@/types';

type Cie10Item = { code: string; label: string };
type EvolutionTemplate = {
    title: string;
    procedure: string;
    notes: string;
    prescription: string;
};

const CHRONIC_OPTIONS = [
    'Hipertensión arterial',
    'Diabetes mellitus',
    'Tratamiento anticoagulante',
    'Cardiopatía',
    'Asma',
    'Trastorno de coagulación',
];

const HABIT_OPTIONS = [
    'Bruxismo nocturno',
    'Tabaquismo',
    'Onicofagia',
    'Respiración bucal',
];

const SYSTEMS_REVIEW_LIST = [
    ['cardiovascular', 'Cardiovascular'],
    ['respiratorio', 'Respiratorio'],
    ['endocrino', 'Endocrino / Metabólico'],
    ['hematologico', 'Hematológico / Coagulación'],
    ['neurologico', 'Neurológico'],
    ['renal_hepatico', 'Renal / Hepático'],
    ['inmunologico', 'Inmunológico / Alérgico'],
    ['digestivo', 'Digestivo'],
] as const;

export function MedicalHistoryTab({
    patient,
    cie10Codes,
    canEdit,
    evolutionTemplates,
}: {
    patient: Patient;
    cie10Codes: Cie10Item[];
    canEdit: boolean;
    evolutionTemplates: EvolutionTemplate[];
}) {
    const mh: Partial<MedicalHistory> = patient.medical_history ?? {};
    const [autoSaveState, setAutoSaveState] = useState<
        'idle' | 'saving' | 'saved'
    >('idle');
    const firstMount = useRef(true);

    const historyForm = useForm({
        chief_complaint: mh.chief_complaint ?? '',
        current_illness: mh.current_illness ?? '',
        medical_history_notes: mh.medical_history_notes ?? '',
        dental_history_notes: mh.dental_history_notes ?? '',
        family_history: mh.family_history ?? '',
        allergies: mh.allergies ?? '',
        current_medications: mh.current_medications ?? '',
        chronic_conditions: mh.chronic_conditions ?? ([] as string[]),
        review_of_systems:
            mh.review_of_systems ?? ({} as Record<string, boolean>),
        habits: mh.habits ?? ([] as string[]),
        is_pregnant: mh.is_pregnant ?? false,
        asa_classification: mh.asa_classification ?? 'ASA I',
        medical_alerts: mh.medical_alerts ?? ([] as MedicalAlert[]),
        vital_signs: {
            pa: mh.vital_signs?.pa ?? '',
            fc: mh.vital_signs?.fc ?? '',
            fr: mh.vital_signs?.fr ?? '',
            temp: mh.vital_signs?.temp ?? '',
            sato2: mh.vital_signs?.sato2 ?? '',
            weight_kg: mh.vital_signs?.weight_kg ?? '',
            height_cm: mh.vital_signs?.height_cm ?? '',
            bmi: mh.vital_signs?.bmi ?? '',
        },
        extraoral_exam: mh.extraoral_exam ?? '',
        intraoral_exam: mh.intraoral_exam ?? '',
        tmj_exam: mh.tmj_exam ?? '',
        soft_tissues_exam: mh.soft_tissues_exam ?? '',
        periodontal_exam: mh.periodontal_exam ?? '',
        occlusion_exam: mh.occlusion_exam ?? '',
        stomatognathic_exam:
            mh.stomatognathic_exam ??
            ({
                lips_cheeks: '',
                palate_tongue: '',
                floor_salivary: '',
                lymph_nodes: '',
                angle_class: 'Clase I',
            } as Record<string, string>),
        presumptive_diagnosis: mh.presumptive_diagnosis ?? '',
        definitive_diagnosis: mh.definitive_diagnosis ?? '',
        cie10_code: mh.cie10_code ?? '',
        expected_version: mh.version ?? 1,
    });

    const toggleSystemReview = (sysKey: string) => {
        historyForm.setData('review_of_systems', {
            ...historyForm.data.review_of_systems,
            [sysKey]: !historyForm.data.review_of_systems[sysKey],
        });
    };

    const updateAnthropometry = (
        field: 'weight_kg' | 'height_cm',
        value: string,
    ) => {
        const nextVitals = { ...historyForm.data.vital_signs, [field]: value };
        const w = Number.parseFloat(nextVitals.weight_kg);
        const hCm = Number.parseFloat(nextVitals.height_cm);
        if (w > 0 && hCm > 30) {
            const hM = hCm / 100;
            nextVitals.bmi = (w / (hM * hM)).toFixed(1);
        }
        historyForm.setData('vital_signs', nextVitals);
    };

    const [newAlertLabel, setNewAlertLabel] = useState('');
    const [newAlertSeverity, setNewAlertSeverity] = useState<
        'alta' | 'media' | 'baja'
    >('alta');

    const saveHistory = (silent = false) => {
        if (!canEdit || !navigator.onLine) return;
        setAutoSaveState('saving');
        historyForm.put(`/patients/${patient.id}/medical-history`, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                setAutoSaveState('saved');
                historyForm.setData(
                    'expected_version',
                    (historyForm.data.expected_version ?? 1) + 1,
                );
                if (!silent) toast.success('Historia clínica guardada.');
            },
            onError: () => setAutoSaveState('idle'),
        });
    };

    // Guardado automático en la historia clínica (Sección 7.1)
    useEffect(() => {
        if (firstMount.current) {
            firstMount.current = false;
            return;
        }
        if (!canEdit || !historyForm.isDirty) return;

        const timer = setTimeout(() => {
            saveHistory(true);
        }, 2500);
        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [historyForm.data]);

    const toggleArrayItem = (
        field: 'chronic_conditions' | 'habits',
        value: string,
    ) => {
        const list = historyForm.data[field];
        historyForm.setData(
            field,
            list.includes(value)
                ? list.filter((x) => x !== value)
                : [...list, value],
        );
    };

    const addMedicalAlert = () => {
        const label = newAlertLabel.trim();
        if (!label) return;
        historyForm.setData('medical_alerts', [
            ...historyForm.data.medical_alerts,
            { type: 'alerta', label, severity: newAlertSeverity },
        ]);
        setNewAlertLabel('');
    };

    const removeAlert = (idx: number) => {
        historyForm.setData(
            'medical_alerts',
            historyForm.data.medical_alerts.filter((_, i) => i !== idx),
        );
    };

    // Evoluciones clínicas
    const [openEvolModal, setOpenEvolModal] = useState(false);
    const [correctionTarget, setCorrectionTarget] =
        useState<ClinicalEvolution | null>(null);
    const [editingDraft, setEditingDraft] = useState<ClinicalEvolution | null>(
        null,
    );

    const evolForm = useForm({
        attended_at: new Date().toISOString().slice(0, 16),
        procedure_summary: '',
        clinical_notes: '',
        soap_subjective: '',
        soap_objective: '',
        soap_assessment: '',
        soap_plan: '',
        cie10_code: '',
        prescription_notes: '',
        parent_evolution_id: null as number | null,
        correction_reason: '',
        sign_now: true,
    });

    const openNewEvolution = () => {
        setCorrectionTarget(null);
        setEditingDraft(null);
        evolForm.setData({
            attended_at: new Date().toISOString().slice(0, 16),
            procedure_summary: '',
            clinical_notes: '',
            soap_subjective: '',
            soap_objective: '',
            soap_assessment: '',
            soap_plan: '',
            cie10_code: historyForm.data.cie10_code || '',
            prescription_notes: '',
            parent_evolution_id: null,
            correction_reason: '',
            sign_now: true,
        });
        setOpenEvolModal(true);
    };

    const openCorrection = (parent: ClinicalEvolution) => {
        setCorrectionTarget(parent);
        setEditingDraft(null);
        evolForm.setData({
            attended_at: new Date().toISOString().slice(0, 16),
            procedure_summary: `Corrección: ${parent.procedure_summary}`,
            clinical_notes: '',
            soap_subjective: '',
            soap_objective: '',
            soap_assessment: '',
            soap_plan: '',
            cie10_code: parent.cie10_code ?? '',
            prescription_notes: '',
            parent_evolution_id: parent.id,
            correction_reason: '',
            sign_now: true,
        });
        setOpenEvolModal(true);
    };

    const openDraftEdit = (draft: ClinicalEvolution) => {
        setCorrectionTarget(null);
        setEditingDraft(draft);
        evolForm.setData({
            attended_at: draft.attended_at.slice(0, 16),
            procedure_summary: draft.procedure_summary,
            clinical_notes: draft.clinical_notes,
            soap_subjective: draft.soap_subjective ?? '',
            soap_objective: draft.soap_objective ?? '',
            soap_assessment: draft.soap_assessment ?? '',
            soap_plan: draft.soap_plan ?? '',
            cie10_code: draft.cie10_code ?? '',
            prescription_notes: draft.prescription_notes ?? '',
            parent_evolution_id: null,
            correction_reason: '',
            sign_now: false,
        });
        setOpenEvolModal(true);
    };

    const applyTemplate = (tpl: EvolutionTemplate) => {
        evolForm.setData((prev) => ({
            ...prev,
            procedure_summary: tpl.procedure,
            clinical_notes: tpl.notes,
            prescription_notes: tpl.prescription,
        }));
    };

    const submitEvolution = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!navigator.onLine && !editingDraft) {
            await queueOfflineMutation(
                'clinical_evolution.create',
                `Evolución offline: ${evolForm.data.procedure_summary}`,
                {
                    patient_id: patient.id,
                    ...evolForm.data,
                },
            );
            toast.success(
                'Sin conexión: nota clínica guardada localmente para sincronizar.',
            );
            setOpenEvolModal(false);
            return;
        }

        if (editingDraft) {
            evolForm.patch(
                `/patients/${patient.id}/evolutions/${editingDraft.id}`,
                {
                    preserveScroll: true,
                    onSuccess: () => setOpenEvolModal(false),
                },
            );
            return;
        }

        evolForm.post(`/patients/${patient.id}/evolutions`, {
            preserveScroll: true,
            onSuccess: () => setOpenEvolModal(false),
        });
    };

    const signEvolution = (evol: ClinicalEvolution) => {
        router.post(
            `/patients/${patient.id}/evolutions/${evol.id}/sign`,
            {},
            { preserveScroll: true },
        );
    };

    const rootEvolutions = (patient.clinical_evolutions ?? []).filter(
        (e) => !e.parent_evolution_id,
    );

    return (
        <div className="space-y-6">
            {/* Sección Anamnesis + Examen + Diagnóstico */}
            <div className="rounded-2xl border bg-card p-5 shadow-xs">
                <div className="mb-4 flex flex-wrap items-center justify-between gap-2 border-b pb-3">
                    <div>
                        <h3 className="text-base font-bold">
                            Anamnesis, Examen Clínico y Diagnóstico
                        </h3>
                        <p className="text-xs text-muted-foreground">
                            {autoSaveState === 'saving'
                                ? 'Guardando cambios automáticamente...'
                                : autoSaveState === 'saved'
                                  ? 'Guardado automático al día'
                                  : 'Los cambios se guardan automáticamente al dejar de escribir'}
                        </p>
                    </div>
                    {canEdit && (
                        <Button
                            type="button"
                            size="sm"
                            disabled={historyForm.processing}
                            onClick={() => saveHistory(false)}
                            className="h-9 gap-1.5 rounded-xl bg-[#0E8FA3] text-white hover:bg-[#0A6577]"
                        >
                            <Save className="size-3.5" />
                            Guardar historia
                        </Button>
                    )}
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-3">
                        <div>
                            <Label className="text-xs font-semibold">
                                Motivo de consulta
                            </Label>
                            <textarea
                                rows={2}
                                disabled={!canEdit}
                                value={historyForm.data.chief_complaint}
                                onChange={(e) =>
                                    historyForm.setData(
                                        'chief_complaint',
                                        e.target.value,
                                    )
                                }
                                className="mt-1 w-full rounded-xl border bg-background p-2.5 text-sm"
                            />
                        </div>
                        <div>
                            <Label className="text-xs font-semibold">
                                Enfermedad actual
                            </Label>
                            <textarea
                                rows={2}
                                disabled={!canEdit}
                                value={historyForm.data.current_illness}
                                onChange={(e) =>
                                    historyForm.setData(
                                        'current_illness',
                                        e.target.value,
                                    )
                                }
                                className="mt-1 w-full rounded-xl border bg-background p-2.5 text-sm"
                            />
                        </div>
                        <div>
                            <Label className="text-xs font-semibold text-[#D64545]">
                                Alergias (alerta visible en toda la ficha)
                            </Label>
                            <Input
                                disabled={!canEdit}
                                value={historyForm.data.allergies}
                                onChange={(e) =>
                                    historyForm.setData(
                                        'allergies',
                                        e.target.value,
                                    )
                                }
                                placeholder="Ej: Penicilina, Látex, Lidocaína..."
                                className="mt-1 rounded-xl border-[#D64545]/40"
                            />
                        </div>
                        <div>
                            <Label className="text-xs font-semibold">
                                Medicación actual
                            </Label>
                            <Input
                                disabled={!canEdit}
                                value={historyForm.data.current_medications}
                                onChange={(e) =>
                                    historyForm.setData(
                                        'current_medications',
                                        e.target.value,
                                    )
                                }
                                placeholder="Ej: Warfarina, Metformina, Losartán..."
                                className="mt-1 rounded-xl"
                            />
                        </div>
                        <div>
                            <Label className="text-xs font-semibold">
                                Antecedentes médicos y odontológicos
                            </Label>
                            <textarea
                                rows={2}
                                disabled={!canEdit}
                                value={historyForm.data.medical_history_notes}
                                onChange={(e) =>
                                    historyForm.setData(
                                        'medical_history_notes',
                                        e.target.value,
                                    )
                                }
                                className="mt-1 w-full rounded-xl border bg-background p-2.5 text-sm"
                            />
                        </div>

                        <div className="grid gap-2 sm:grid-cols-2">
                            <div>
                                <Label className="text-xs font-semibold">
                                    Clasificación de Riesgo ASA (HCE)
                                </Label>
                                <select
                                    disabled={!canEdit}
                                    value={historyForm.data.asa_classification}
                                    onChange={(e) =>
                                        historyForm.setData(
                                            'asa_classification',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 h-9 w-full rounded-xl border bg-background px-2.5 text-xs"
                                >
                                    <option value="ASA I">
                                        ASA I — Paciente sano
                                    </option>
                                    <option value="ASA II">
                                        ASA II — Enf. sistémica leve
                                    </option>
                                    <option value="ASA III">
                                        ASA III — Enf. sistémica severa
                                    </option>
                                    <option value="ASA IV">
                                        ASA IV — Riesgo vital constante
                                    </option>
                                </select>
                            </div>
                            <div>
                                <Label className="text-xs font-semibold">
                                    Antecedentes familiares
                                </Label>
                                <Input
                                    disabled={!canEdit}
                                    placeholder="Ej: Diabetes materna, HTA..."
                                    value={historyForm.data.family_history}
                                    onChange={(e) =>
                                        historyForm.setData(
                                            'family_history',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 h-9 rounded-xl text-xs"
                                />
                            </div>
                        </div>

                        <div>
                            <Label className="text-xs font-semibold">
                                Revisión anamnésica por aparatos y sistemas
                                (HCE)
                            </Label>
                            <div className="mt-1.5 flex flex-wrap gap-1.5">
                                {SYSTEMS_REVIEW_LIST.map(([key, label]) => (
                                    <button
                                        key={key}
                                        type="button"
                                        disabled={!canEdit}
                                        onClick={() => toggleSystemReview(key)}
                                        className={`rounded-full border px-2.5 py-0.5 text-[11px] font-medium ${
                                            historyForm.data.review_of_systems[
                                                key
                                            ]
                                                ? 'border-amber-500 bg-amber-100 text-amber-950'
                                                : 'bg-background text-muted-foreground'
                                        }`}
                                    >
                                        {label}
                                    </button>
                                ))}
                            </div>
                        </div>

                        {/* Alertas médicas personalizadas */}
                        <div className="rounded-xl border border-[#D64545]/30 bg-[#FEF2F2]/60 p-3 dark:bg-red-950/20">
                            <div className="mb-2 flex items-center gap-1.5 text-xs font-bold text-[#D64545]">
                                <AlertTriangle className="size-3.5" />
                                Alertas médicas destacadas
                            </div>
                            <div className="flex flex-wrap gap-1.5">
                                {historyForm.data.medical_alerts.map(
                                    (alert, idx) => (
                                        <span
                                            key={`${alert.label}-${idx}`}
                                            className="inline-flex items-center gap-1.5 rounded-full bg-[#D64545] px-2.5 py-1 text-xs font-semibold text-white"
                                        >
                                            {alert.label}
                                            {canEdit && (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        removeAlert(idx)
                                                    }
                                                    className="ml-1 opacity-80 hover:opacity-100"
                                                >
                                                    ×
                                                </button>
                                            )}
                                        </span>
                                    ),
                                )}
                            </div>
                            {canEdit && (
                                <div className="mt-2.5 flex gap-2">
                                    <Input
                                        value={newAlertLabel}
                                        onChange={(e) =>
                                            setNewAlertLabel(e.target.value)
                                        }
                                        placeholder="Ej: Anticoagulantes, Diabetes..."
                                        className="h-8 bg-background text-xs"
                                    />
                                    <select
                                        value={newAlertSeverity}
                                        onChange={(e) =>
                                            setNewAlertSeverity(
                                                e.target.value as
                                                    | 'alta'
                                                    | 'media'
                                                    | 'baja',
                                            )
                                        }
                                        className="h-8 rounded-md border bg-background px-2 text-xs"
                                    >
                                        <option value="alta">Alta</option>
                                        <option value="media">Media</option>
                                    </select>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        className="h-8 text-xs"
                                        onClick={addMedicalAlert}
                                    >
                                        Agregar
                                    </Button>
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="space-y-3">
                        <div className="grid grid-cols-2 gap-2 sm:grid-cols-5">
                            <div>
                                <Label className="text-xs font-semibold">
                                    PA (mmHg)
                                </Label>
                                <Input
                                    disabled={!canEdit}
                                    placeholder="120/80"
                                    value={historyForm.data.vital_signs.pa}
                                    onChange={(e) =>
                                        historyForm.setData('vital_signs', {
                                            ...historyForm.data.vital_signs,
                                            pa: e.target.value,
                                        })
                                    }
                                    className="mt-1 h-9 rounded-xl text-xs"
                                />
                            </div>
                            <div>
                                <Label className="text-xs font-semibold">
                                    FC (lpm)
                                </Label>
                                <Input
                                    disabled={!canEdit}
                                    placeholder="72"
                                    value={historyForm.data.vital_signs.fc}
                                    onChange={(e) =>
                                        historyForm.setData('vital_signs', {
                                            ...historyForm.data.vital_signs,
                                            fc: e.target.value,
                                        })
                                    }
                                    className="mt-1 h-9 rounded-xl text-xs"
                                />
                            </div>
                            <div>
                                <Label className="text-xs font-semibold">
                                    FR (rpm)
                                </Label>
                                <Input
                                    disabled={!canEdit}
                                    placeholder="16"
                                    value={historyForm.data.vital_signs.fr}
                                    onChange={(e) =>
                                        historyForm.setData('vital_signs', {
                                            ...historyForm.data.vital_signs,
                                            fr: e.target.value,
                                        })
                                    }
                                    className="mt-1 h-9 rounded-xl text-xs"
                                />
                            </div>
                            <div>
                                <Label className="text-xs font-semibold">
                                    Temp (°C)
                                </Label>
                                <Input
                                    disabled={!canEdit}
                                    placeholder="36.6"
                                    value={historyForm.data.vital_signs.temp}
                                    onChange={(e) =>
                                        historyForm.setData('vital_signs', {
                                            ...historyForm.data.vital_signs,
                                            temp: e.target.value,
                                        })
                                    }
                                    className="mt-1 h-9 rounded-xl text-xs"
                                />
                            </div>
                            <div>
                                <Label className="text-xs font-semibold">
                                    SatO2 (%)
                                </Label>
                                <Input
                                    disabled={!canEdit}
                                    placeholder="98%"
                                    value={historyForm.data.vital_signs.sato2}
                                    onChange={(e) =>
                                        historyForm.setData('vital_signs', {
                                            ...historyForm.data.vital_signs,
                                            sato2: e.target.value,
                                        })
                                    }
                                    className="mt-1 h-9 rounded-xl text-xs"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-3 gap-2">
                            <div>
                                <Label className="text-xs font-semibold">
                                    Peso (kg)
                                </Label>
                                <Input
                                    disabled={!canEdit}
                                    placeholder="68"
                                    value={
                                        historyForm.data.vital_signs.weight_kg
                                    }
                                    onChange={(e) =>
                                        updateAnthropometry(
                                            'weight_kg',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 h-9 rounded-xl text-xs"
                                />
                            </div>
                            <div>
                                <Label className="text-xs font-semibold">
                                    Talla (cm)
                                </Label>
                                <Input
                                    disabled={!canEdit}
                                    placeholder="168"
                                    value={
                                        historyForm.data.vital_signs.height_cm
                                    }
                                    onChange={(e) =>
                                        updateAnthropometry(
                                            'height_cm',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 h-9 rounded-xl text-xs"
                                />
                            </div>
                            <div>
                                <Label className="text-xs font-semibold">
                                    IMC (kg/m²)
                                </Label>
                                <Input
                                    readOnly
                                    value={historyForm.data.vital_signs.bmi}
                                    placeholder="Auto"
                                    className="mt-1 h-9 rounded-xl bg-muted/40 text-xs font-semibold"
                                />
                            </div>
                        </div>

                        <div className="grid gap-2 sm:grid-cols-2">
                            <div>
                                <Label className="text-xs font-semibold">
                                    Examen extraoral
                                </Label>
                                <Input
                                    disabled={!canEdit}
                                    value={historyForm.data.extraoral_exam}
                                    onChange={(e) =>
                                        historyForm.setData(
                                            'extraoral_exam',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 rounded-xl text-xs"
                                />
                            </div>
                            <div>
                                <Label className="text-xs font-semibold">
                                    ATM (Apertura / Ruidos / Dolor)
                                </Label>
                                <Input
                                    disabled={!canEdit}
                                    value={historyForm.data.tmj_exam}
                                    onChange={(e) =>
                                        historyForm.setData(
                                            'tmj_exam',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 rounded-xl text-xs"
                                />
                            </div>
                            <div>
                                <Label className="text-xs font-semibold">
                                    Examen intraoral y tejidos blandos
                                </Label>
                                <Input
                                    disabled={!canEdit}
                                    value={historyForm.data.intraoral_exam}
                                    onChange={(e) =>
                                        historyForm.setData(
                                            'intraoral_exam',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 rounded-xl text-xs"
                                />
                            </div>
                            <div>
                                <Label className="text-xs font-semibold">
                                    Periodonto y encías
                                </Label>
                                <Input
                                    disabled={!canEdit}
                                    value={historyForm.data.periodontal_exam}
                                    onChange={(e) =>
                                        historyForm.setData(
                                            'periodontal_exam',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 rounded-xl text-xs"
                                />
                            </div>
                            <div className="sm:col-span-2">
                                <Label className="text-xs font-semibold">
                                    Oclusión (Relación molar / Overjet /
                                    Overbite)
                                </Label>
                                <Input
                                    disabled={!canEdit}
                                    value={historyForm.data.occlusion_exam}
                                    onChange={(e) =>
                                        historyForm.setData(
                                            'occlusion_exam',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 rounded-xl text-xs"
                                />
                            </div>
                            <div>
                                <Label className="text-xs font-semibold">
                                    Clasificación Molar Angle
                                </Label>
                                <select
                                    disabled={!canEdit}
                                    value={
                                        historyForm.data.stomatognathic_exam
                                            .angle_class ?? 'Clase I'
                                    }
                                    onChange={(e) =>
                                        historyForm.setData(
                                            'stomatognathic_exam',
                                            {
                                                ...historyForm.data
                                                    .stomatognathic_exam,
                                                angle_class: e.target.value,
                                            },
                                        )
                                    }
                                    className="mt-1 h-9 w-full rounded-xl border bg-background px-2 text-xs"
                                >
                                    <option value="Clase I">
                                        Angle Clase I (Normo-oclusión)
                                    </option>
                                    <option value="Clase II">
                                        Angle Clase II (Disto-oclusión)
                                    </option>
                                    <option value="Clase III">
                                        Angle Clase III (Mesio-oclusión)
                                    </option>
                                </select>
                            </div>
                            <div>
                                <Label className="text-xs font-semibold">
                                    Ganglios / Labios / Carrillos (HCE)
                                </Label>
                                <Input
                                    disabled={!canEdit}
                                    placeholder="Normales, sin adenopatías..."
                                    value={
                                        historyForm.data.stomatognathic_exam
                                            .lymph_nodes ?? ''
                                    }
                                    onChange={(e) =>
                                        historyForm.setData(
                                            'stomatognathic_exam',
                                            {
                                                ...historyForm.data
                                                    .stomatognathic_exam,
                                                lymph_nodes: e.target.value,
                                            },
                                        )
                                    }
                                    className="mt-1 h-9 rounded-xl text-xs"
                                />
                            </div>
                        </div>

                        <div>
                            <Label className="text-xs font-semibold">
                                Antecedentes crónicos y hábitos
                            </Label>
                            <div className="mt-1.5 flex flex-wrap gap-1.5">
                                {CHRONIC_OPTIONS.map((opt) => (
                                    <button
                                        key={opt}
                                        type="button"
                                        disabled={!canEdit}
                                        onClick={() =>
                                            toggleArrayItem(
                                                'chronic_conditions',
                                                opt,
                                            )
                                        }
                                        className={`rounded-full border px-2.5 py-0.5 text-[11px] font-medium ${
                                            historyForm.data.chronic_conditions.includes(
                                                opt,
                                            )
                                                ? 'border-[#D64545] bg-[#FEE2E2] text-[#991B1B]'
                                                : 'bg-background text-muted-foreground'
                                        }`}
                                    >
                                        {opt}
                                    </button>
                                ))}
                                {HABIT_OPTIONS.map((opt) => (
                                    <button
                                        key={opt}
                                        type="button"
                                        disabled={!canEdit}
                                        onClick={() =>
                                            toggleArrayItem('habits', opt)
                                        }
                                        className={`rounded-full border px-2.5 py-0.5 text-[11px] font-medium ${
                                            historyForm.data.habits.includes(
                                                opt,
                                            )
                                                ? 'border-[#0E8FA3] bg-[#E6F5F7] text-[#0A6577]'
                                                : 'bg-background text-muted-foreground'
                                        }`}
                                    >
                                        {opt}
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <div>
                                <Label className="text-xs font-semibold">
                                    Diagnóstico presuntivo
                                </Label>
                                <Input
                                    disabled={!canEdit}
                                    value={
                                        historyForm.data.presumptive_diagnosis
                                    }
                                    onChange={(e) =>
                                        historyForm.setData(
                                            'presumptive_diagnosis',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 rounded-xl text-xs"
                                />
                            </div>
                            <div className="grid gap-2 sm:grid-cols-3">
                                <div className="sm:col-span-1">
                                    <Label className="text-xs font-semibold">
                                        Código CIE-10
                                    </Label>
                                    <select
                                        disabled={!canEdit}
                                        value={historyForm.data.cie10_code}
                                        onChange={(e) =>
                                            historyForm.setData(
                                                'cie10_code',
                                                e.target.value,
                                            )
                                        }
                                        className="mt-1 h-9 w-full rounded-xl border bg-background px-2 text-xs"
                                    >
                                        <option value="">Seleccionar...</option>
                                        {cie10Codes.map((c) => (
                                            <option key={c.code} value={c.code}>
                                                {c.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div className="sm:col-span-2">
                                    <Label className="text-xs font-semibold">
                                        Diagnóstico definitivo
                                    </Label>
                                    <Input
                                        disabled={!canEdit}
                                        value={
                                            historyForm.data
                                                .definitive_diagnosis
                                        }
                                        onChange={(e) =>
                                            historyForm.setData(
                                                'definitive_diagnosis',
                                                e.target.value,
                                            )
                                        }
                                        className="mt-1 rounded-xl text-xs"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Evoluciones y trazabilidad */}
            <div className="rounded-2xl border bg-card p-5 shadow-xs">
                <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 className="text-base font-bold">
                            Notas de Evolución Clínica
                        </h3>
                        <p className="text-xs text-muted-foreground">
                            Los registros firmados quedan bloqueados; solo
                            admiten notas de corrección con trazabilidad.
                        </p>
                    </div>
                    {canEdit && (
                        <Button
                            type="button"
                            onClick={openNewEvolution}
                            className="h-10 gap-1.5 rounded-xl bg-[#0E8FA3] px-4 text-xs font-semibold text-white hover:bg-[#0A6577]"
                        >
                            <Plus className="size-4" />
                            Nueva atención
                        </Button>
                    )}
                </div>

                {rootEvolutions.length === 0 ? (
                    <div className="rounded-xl border border-dashed py-8 text-center text-sm text-muted-foreground">
                        Sin evoluciones registradas todavía.
                    </div>
                ) : (
                    <div className="space-y-3">
                        {rootEvolutions.map((ev) => (
                            <div
                                key={ev.id}
                                className="rounded-2xl border bg-background p-4"
                            >
                                <div className="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="text-sm font-bold text-foreground">
                                                {ev.procedure_summary}
                                            </span>
                                            {ev.is_locked ? (
                                                <Badge className="gap-1 bg-[#DCFCE7] text-[#166534] hover:bg-[#DCFCE7]">
                                                    <Lock className="size-3" />
                                                    Firmado y bloqueado
                                                </Badge>
                                            ) : (
                                                <Badge
                                                    variant="outline"
                                                    className="border-[#F0A92E] text-[#B45309]"
                                                >
                                                    Borrador editable
                                                </Badge>
                                            )}
                                        </div>
                                        <div className="mt-0.5 text-xs text-muted-foreground">
                                            {new Date(
                                                ev.attended_at,
                                            ).toLocaleString('es-PE')}{' '}
                                            · {ev.author?.name}{' '}
                                            {ev.author?.license_number
                                                ? `(${ev.author.license_number})`
                                                : ''}
                                        </div>
                                    </div>

                                    {canEdit && (
                                        <div className="flex gap-2">
                                            {!ev.is_locked ? (
                                                <>
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant="outline"
                                                        className="h-8 text-xs"
                                                        onClick={() =>
                                                            openDraftEdit(ev)
                                                        }
                                                    >
                                                        Editar
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        className="h-8 gap-1 bg-[#2E9E6B] text-xs text-white hover:bg-[#238056]"
                                                        onClick={() =>
                                                            signEvolution(ev)
                                                        }
                                                    >
                                                        <FileCheck2 className="size-3.5" />
                                                        Firmar y bloquear
                                                    </Button>
                                                </>
                                            ) : (
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="outline"
                                                    className="h-8 gap-1 text-xs"
                                                    onClick={() =>
                                                        openCorrection(ev)
                                                    }
                                                >
                                                    <MessageSquarePlus className="size-3.5" />
                                                    Agregar corrección
                                                </Button>
                                            )}
                                        </div>
                                    )}
                                </div>

                                <p className="mt-2.5 text-sm leading-relaxed whitespace-pre-line text-foreground">
                                    {ev.clinical_notes}
                                </p>

                                {(ev.soap_subjective ||
                                    ev.soap_objective ||
                                    ev.soap_assessment ||
                                    ev.soap_plan) && (
                                    <div className="mt-2.5 grid gap-1.5 rounded-xl border-l-4 border-[#0E8FA3] bg-muted/30 p-3 text-xs">
                                        {ev.soap_subjective && (
                                            <div>
                                                <strong className="text-[#0A6577]">
                                                    S (Subjetivo):
                                                </strong>{' '}
                                                {ev.soap_subjective}
                                            </div>
                                        )}
                                        {ev.soap_objective && (
                                            <div>
                                                <strong className="text-[#0A6577]">
                                                    O (Objetivo):
                                                </strong>{' '}
                                                {ev.soap_objective}
                                            </div>
                                        )}
                                        {ev.soap_assessment && (
                                            <div>
                                                <strong className="text-[#0A6577]">
                                                    A (Análisis{' '}
                                                    {ev.cie10_code
                                                        ? `[${ev.cie10_code}]`
                                                        : ''}
                                                    ):
                                                </strong>{' '}
                                                {ev.soap_assessment}
                                            </div>
                                        )}
                                        {ev.soap_plan && (
                                            <div>
                                                <strong className="text-[#0A6577]">
                                                    P (Plan):
                                                </strong>{' '}
                                                {ev.soap_plan}
                                            </div>
                                        )}
                                    </div>
                                )}

                                {ev.signature_hash && (
                                    <div className="mt-1.5 font-mono text-[10px] text-muted-foreground">
                                        Integridad SHA-256:{' '}
                                        {ev.signature_hash.slice(0, 24)}…
                                    </div>
                                )}

                                {ev.prescription_notes && (
                                    <div className="mt-2.5 rounded-xl bg-[#E6F5F7]/60 p-2.5 text-xs text-[#0A6577] dark:bg-cyan-950/30 dark:text-cyan-200">
                                        <strong>Indicaciones / Receta:</strong>{' '}
                                        {ev.prescription_notes}
                                    </div>
                                )}

                                {(ev.corrections ?? []).length > 0 && (
                                    <div className="mt-3 space-y-2 border-l-2 border-[#F0A92E] pl-3">
                                        {(ev.corrections ?? []).map((corr) => (
                                            <div
                                                key={corr.id}
                                                className="rounded-xl bg-[#FFFBEB] p-2.5 text-xs dark:bg-amber-950/20"
                                            >
                                                <div className="font-bold text-[#92400E] dark:text-amber-300">
                                                    Fe de erratas ·{' '}
                                                    {new Date(
                                                        corr.attended_at,
                                                    ).toLocaleString(
                                                        'es-PE',
                                                    )}{' '}
                                                    · {corr.author?.name}
                                                </div>
                                                <div className="mt-0.5 text-muted-foreground italic">
                                                    Motivo:{' '}
                                                    {corr.correction_reason}
                                                </div>
                                                <div className="mt-1 text-foreground">
                                                    {corr.clinical_notes}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </div>

            <Dialog open={openEvolModal} onOpenChange={setOpenEvolModal}>
                <DialogContent className="max-h-[90vh] max-w-xl overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>
                            {correctionTarget
                                ? 'Agregar nota de corrección (registro bloqueado)'
                                : editingDraft
                                  ? 'Editar borrador de evolución'
                                  : 'Registrar nueva atención clínica'}
                        </DialogTitle>
                    </DialogHeader>

                    {!correctionTarget && !editingDraft && (
                        <div className="space-y-1.5">
                            <span className="text-xs font-semibold text-muted-foreground">
                                Plantillas rápidas (registro en menos de 5 min):
                            </span>
                            <div className="flex flex-wrap gap-1.5">
                                {evolutionTemplates.map((tpl) => (
                                    <button
                                        key={tpl.title}
                                        type="button"
                                        onClick={() => applyTemplate(tpl)}
                                        className="rounded-full border bg-muted/40 px-2.5 py-1 text-xs font-medium hover:bg-[#E6F5F7] hover:text-[#0A6577]"
                                    >
                                        {tpl.title}
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}

                    <form
                        onSubmit={(e) => void submitEvolution(e)}
                        className="space-y-3"
                    >
                        {correctionTarget && (
                            <div>
                                <Label>Motivo de corrección *</Label>
                                <Input
                                    required
                                    value={evolForm.data.correction_reason}
                                    onChange={(e) =>
                                        evolForm.setData(
                                            'correction_reason',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Ej: Precisión de material utilizado"
                                />
                            </div>
                        )}
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div>
                                <Label>Fecha y hora</Label>
                                <Input
                                    type="datetime-local"
                                    required
                                    value={evolForm.data.attended_at}
                                    onChange={(e) =>
                                        evolForm.setData(
                                            'attended_at',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Procedimiento realizado *</Label>
                                <Input
                                    required
                                    value={evolForm.data.procedure_summary}
                                    onChange={(e) =>
                                        evolForm.setData(
                                            'procedure_summary',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Ej: Restauración resina pieza 46"
                                />
                            </div>
                        </div>
                        <div>
                            <Label>Nota de evolución *</Label>
                            <textarea
                                required
                                rows={3}
                                value={evolForm.data.clinical_notes}
                                onChange={(e) =>
                                    evolForm.setData(
                                        'clinical_notes',
                                        e.target.value,
                                    )
                                }
                                className="mt-1 w-full rounded-xl border bg-background p-2.5 text-sm"
                            />
                        </div>
                        <div className="space-y-2 rounded-xl border bg-muted/20 p-3">
                            <div className="flex items-center justify-between">
                                <span className="text-xs font-bold text-[#0A6577]">
                                    Estructura SOAP Homologada HCE (Opcional)
                                </span>
                                <Input
                                    placeholder="CIE-10 (ej. K02.1)"
                                    value={evolForm.data.cie10_code}
                                    onChange={(e) =>
                                        evolForm.setData(
                                            'cie10_code',
                                            e.target.value,
                                        )
                                    }
                                    className="h-7 w-36 text-xs"
                                />
                            </div>
                            <div className="grid gap-2 sm:grid-cols-2">
                                <Input
                                    placeholder="S: Síntomas reportados..."
                                    value={evolForm.data.soap_subjective}
                                    onChange={(e) =>
                                        evolForm.setData(
                                            'soap_subjective',
                                            e.target.value,
                                        )
                                    }
                                    className="h-8 text-xs"
                                />
                                <Input
                                    placeholder="O: Hallazgos clínicos objetivos..."
                                    value={evolForm.data.soap_objective}
                                    onChange={(e) =>
                                        evolForm.setData(
                                            'soap_objective',
                                            e.target.value,
                                        )
                                    }
                                    className="h-8 text-xs"
                                />
                                <Input
                                    placeholder="A: Diagnóstico / Evaluación..."
                                    value={evolForm.data.soap_assessment}
                                    onChange={(e) =>
                                        evolForm.setData(
                                            'soap_assessment',
                                            e.target.value,
                                        )
                                    }
                                    className="h-8 text-xs"
                                />
                                <Input
                                    placeholder="P: Plan / Próximo paso..."
                                    value={evolForm.data.soap_plan}
                                    onChange={(e) =>
                                        evolForm.setData(
                                            'soap_plan',
                                            e.target.value,
                                        )
                                    }
                                    className="h-8 text-xs"
                                />
                            </div>
                        </div>
                        <div>
                            <Label>Indicaciones / Receta</Label>
                            <textarea
                                rows={2}
                                value={evolForm.data.prescription_notes}
                                onChange={(e) =>
                                    evolForm.setData(
                                        'prescription_notes',
                                        e.target.value,
                                    )
                                }
                                className="mt-1 w-full rounded-xl border bg-background p-2.5 text-sm"
                            />
                        </div>

                        {!editingDraft && (
                            <label className="flex items-center gap-2 text-xs font-medium">
                                <input
                                    type="checkbox"
                                    checked={evolForm.data.sign_now}
                                    onChange={(e) =>
                                        evolForm.setData(
                                            'sign_now',
                                            e.target.checked,
                                        )
                                    }
                                />
                                Firmar y bloquear inmediatamente al guardar
                            </label>
                        )}

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setOpenEvolModal(false)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                disabled={evolForm.processing}
                                className="bg-[#0E8FA3] text-white hover:bg-[#0A6577]"
                            >
                                Guardar atención
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
}

export function TreatmentPlansTab({
    patient,
    plans,
    catalog,
    canEdit,
}: {
    patient: Patient;
    plans: TreatmentPlan[];
    catalog: TreatmentCatalogItem[];
    canEdit: boolean;
}) {
    const [openNewPlan, setOpenNewPlan] = useState(false);
    const [activePlanForItem, setActivePlanForItem] = useState<number | null>(
        null,
    );

    const planForm = useForm({
        title: `Plan de tratamiento · ${patient.history_number}`,
        status: 'borrador',
        discount_amount: '0',
        valid_until: '',
        notes: '',
    });

    const itemForm = useForm({
        treatment_catalog_id: '',
        tooth_code: '',
        surface: '',
        description: '',
        phase: 'Fase 2: Restauradora',
        priority: 'alta',
        quantity: 1,
        unit_price: '0',
        discount: '0',
    });

    const createPlan = (e: React.FormEvent) => {
        e.preventDefault();
        planForm.post(`/patients/${patient.id}/treatment-plans`, {
            preserveScroll: true,
            onSuccess: () => setOpenNewPlan(false),
        });
    };

    const selectCatalogItem = (id: string) => {
        const hit = catalog.find((c) => String(c.id) === id);
        itemForm.setData((prev) => ({
            ...prev,
            treatment_catalog_id: id,
            description: hit ? hit.name : prev.description,
            unit_price: hit ? String(hit.default_price) : prev.unit_price,
        }));
    };

    const addItem = (e: React.FormEvent) => {
        e.preventDefault();
        if (!activePlanForItem) return;
        itemForm.post(
            `/patients/${patient.id}/treatment-plans/${activePlanForItem}/items`,
            {
                preserveScroll: true,
                onSuccess: () => {
                    setActivePlanForItem(null);
                    itemForm.reset();
                },
            },
        );
    };

    const changeItemStatus = (
        itemId: number,
        status: 'pendiente' | 'en_curso' | 'realizado',
    ) => {
        router.patch(
            `/patients/${patient.id}/treatment-plan-items/${itemId}`,
            { status },
            { preserveScroll: true },
        );
    };

    const changePlanStatus = (planId: number, status: string) => {
        router.patch(
            `/patients/${patient.id}/treatment-plans/${planId}`,
            { status },
            { preserveScroll: true },
        );
    };

    const deletePlan = (planId: number) => {
        router.delete(`/patients/${patient.id}/treatment-plans/${planId}`, {
            preserveScroll: true,
        });
    };

    const deletePlanItem = (itemId: number) => {
        router.delete(
            `/patients/${patient.id}/treatment-plan-items/${itemId}`,
            { preserveScroll: true },
        );
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <div>
                    <h3 className="text-base font-bold">
                        Planes de Tratamiento y Presupuestos
                    </h3>
                    <p className="text-xs text-muted-foreground">
                        Al marcar un procedimiento como realizado, el
                        odontograma del paciente se actualiza automáticamente.
                    </p>
                </div>
                {canEdit && (
                    <Button
                        onClick={() => setOpenNewPlan(true)}
                        className="h-10 gap-1.5 rounded-xl bg-[#0E8FA3] text-xs text-white hover:bg-[#0A6577]"
                    >
                        <Plus className="size-4" />
                        Nuevo plan
                    </Button>
                )}
            </div>

            {plans.length === 0 ? (
                <div className="rounded-2xl border border-dashed bg-card p-8 text-center text-sm text-muted-foreground">
                    Sin planes de tratamiento registrados. Puedes crear uno o
                    convertir hallazgos desde el odontograma.
                </div>
            ) : (
                plans.map((plan) => {
                    const subtotal = plan.items.reduce(
                        (acc, it) =>
                            acc +
                            Number(it.unit_price) * it.quantity -
                            Number(it.discount),
                        0,
                    );
                    const total = Math.max(
                        0,
                        subtotal - Number(plan.discount_amount),
                    );

                    return (
                        <div
                            key={plan.id}
                            className="space-y-3 rounded-2xl border bg-card p-5 shadow-xs"
                        >
                            <div className="flex flex-wrap items-center justify-between gap-2 border-b pb-3">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <h4 className="text-base font-bold">
                                            {plan.title}
                                        </h4>
                                        <Badge
                                            variant="outline"
                                            className="capitalize"
                                        >
                                            {plan.status.replace('_', ' ')}
                                        </Badge>
                                    </div>
                                    <div className="text-xs text-muted-foreground">
                                        Total presupuesto:{' '}
                                        <strong className="text-foreground">
                                            S/ {total.toFixed(2)}
                                        </strong>
                                        {plan.valid_until
                                            ? ` · Vigencia: ${plan.valid_until}`
                                            : ''}
                                    </div>
                                </div>

                                {canEdit && (
                                    <div className="flex flex-wrap gap-2">
                                        {plan.status === 'borrador' && (
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                className="h-8 gap-1 text-xs text-[#2E9E6B]"
                                                onClick={() =>
                                                    changePlanStatus(
                                                        plan.id,
                                                        'aprobado',
                                                    )
                                                }
                                            >
                                                <CheckCircle2 className="size-3.5" />
                                                Aprobar presupuesto
                                            </Button>
                                        )}
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            className="h-8 gap-1 text-xs"
                                            onClick={() =>
                                                setActivePlanForItem(plan.id)
                                            }
                                        >
                                            <Plus className="size-3.5" />
                                            Agregar procedimiento
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            title="Eliminar plan"
                                            className="h-8 px-2 text-xs text-[#D64545]"
                                            onClick={() => deletePlan(plan.id)}
                                        >
                                            <Trash2 className="size-3.5" />
                                        </Button>
                                    </div>
                                )}
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="border-b text-xs text-muted-foreground">
                                        <tr>
                                            <th className="py-2 pr-3">Fase</th>
                                            <th className="py-2 pr-3">
                                                Procedimiento
                                            </th>
                                            <th className="py-2 pr-3">Pieza</th>
                                            <th className="py-2 pr-3">
                                                Importe
                                            </th>
                                            <th className="py-2 pr-3">
                                                Estado
                                            </th>
                                            {canEdit && (
                                                <th className="py-2 text-right">
                                                    Avance
                                                </th>
                                            )}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {plan.items.map((item) => (
                                            <tr key={item.id}>
                                                <td className="py-2.5 pr-3 text-xs text-muted-foreground">
                                                    {item.phase}
                                                </td>
                                                <td className="py-2.5 pr-3 font-medium">
                                                    {item.description}
                                                </td>
                                                <td className="py-2.5 pr-3 text-xs">
                                                    {item.tooth_code
                                                        ? `${item.tooth_code} ${item.surface ?? ''}`
                                                        : '-'}
                                                </td>
                                                <td className="py-2.5 pr-3 font-semibold">
                                                    S/{' '}
                                                    {(
                                                        Number(
                                                            item.unit_price,
                                                        ) *
                                                            item.quantity -
                                                        Number(item.discount)
                                                    ).toFixed(2)}
                                                </td>
                                                <td className="py-2.5 pr-3">
                                                    <Badge
                                                        variant="outline"
                                                        className={
                                                            item.status ===
                                                            'realizado'
                                                                ? 'border-[#2E9E6B] bg-[#DCFCE7] text-[#166534]'
                                                                : item.status ===
                                                                    'en_curso'
                                                                  ? 'border-[#F0A92E] bg-[#FEF3C7] text-[#92400E]'
                                                                  : ''
                                                        }
                                                    >
                                                        {item.status.replace(
                                                            '_',
                                                            ' ',
                                                        )}
                                                    </Badge>
                                                </td>
                                                {canEdit && (
                                                    <td className="py-2.5 text-right">
                                                        <div className="inline-flex items-center gap-1">
                                                            {item.status !==
                                                                'realizado' && (
                                                                <Button
                                                                    size="sm"
                                                                    variant="outline"
                                                                    className="h-7 gap-1 text-xs text-[#2E9E6B]"
                                                                    onClick={() =>
                                                                        changeItemStatus(
                                                                            item.id,
                                                                            'realizado',
                                                                        )
                                                                    }
                                                                >
                                                                    <Sparkles className="size-3" />
                                                                    Marcar
                                                                    realizado
                                                                </Button>
                                                            )}
                                                            <Button
                                                                size="sm"
                                                                variant="ghost"
                                                                title="Quitar ítem"
                                                                className="h-7 px-2 text-xs text-[#D64545]"
                                                                onClick={() =>
                                                                    deletePlanItem(
                                                                        item.id,
                                                                    )
                                                                }
                                                            >
                                                                <Trash2 className="size-3.5" />
                                                            </Button>
                                                        </div>
                                                    </td>
                                                )}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    );
                })
            )}

            <Dialog open={openNewPlan} onOpenChange={setOpenNewPlan}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Nuevo plan de tratamiento</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={createPlan} className="space-y-3">
                        <div>
                            <Label>Título del plan *</Label>
                            <Input
                                required
                                value={planForm.data.title}
                                onChange={(e) =>
                                    planForm.setData('title', e.target.value)
                                }
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label>Descuento global (S/)</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    value={planForm.data.discount_amount}
                                    onChange={(e) =>
                                        planForm.setData(
                                            'discount_amount',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Vigencia</Label>
                                <Input
                                    type="date"
                                    value={planForm.data.valid_until}
                                    onChange={(e) =>
                                        planForm.setData(
                                            'valid_until',
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
                                onClick={() => setOpenNewPlan(false)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                className="bg-[#0E8FA3] text-white"
                            >
                                Crear plan
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog
                open={activePlanForItem !== null}
                onOpenChange={() => setActivePlanForItem(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Agregar procedimiento al plan</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={addItem} className="space-y-3">
                        <div>
                            <Label>Desde catálogo del consultorio</Label>
                            <select
                                value={itemForm.data.treatment_catalog_id}
                                onChange={(e) =>
                                    selectCatalogItem(e.target.value)
                                }
                                className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                            >
                                <option value="">Personalizado...</option>
                                {catalog.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        [{c.category}] {c.name} — S/{' '}
                                        {Number(c.default_price).toFixed(2)}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <Label>Descripción *</Label>
                            <Input
                                required
                                value={itemForm.data.description}
                                onChange={(e) =>
                                    itemForm.setData(
                                        'description',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                        <div className="grid grid-cols-3 gap-2">
                            <div>
                                <Label>Pieza (FDI)</Label>
                                <Input
                                    placeholder="46"
                                    value={itemForm.data.tooth_code}
                                    onChange={(e) =>
                                        itemForm.setData(
                                            'tooth_code',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Cara</Label>
                                <Input
                                    placeholder="O / FULL"
                                    value={itemForm.data.surface}
                                    onChange={(e) =>
                                        itemForm.setData(
                                            'surface',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Precio (S/)</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    required
                                    value={itemForm.data.unit_price}
                                    onChange={(e) =>
                                        itemForm.setData(
                                            'unit_price',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <Label>Fase</Label>
                                <select
                                    value={itemForm.data.phase}
                                    onChange={(e) =>
                                        itemForm.setData(
                                            'phase',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                                >
                                    <option value="Urgencia">Urgencia</option>
                                    <option value="Fase 1: Higiénica">
                                        Fase 1: Higiénica
                                    </option>
                                    <option value="Fase 2: Restauradora">
                                        Fase 2: Restauradora
                                    </option>
                                    <option value="Fase 3: Rehabilitación/Mantenimiento">
                                        Fase 3: Rehabilitación
                                    </option>
                                </select>
                            </div>
                            <div>
                                <Label>Prioridad</Label>
                                <select
                                    value={itemForm.data.priority}
                                    onChange={(e) =>
                                        itemForm.setData(
                                            'priority',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                                >
                                    <option value="alta">Alta</option>
                                    <option value="media">Media</option>
                                    <option value="baja">Baja</option>
                                </select>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setActivePlanForItem(null)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                className="bg-[#0E8FA3] text-white"
                            >
                                Agregar procedimiento
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
}

export function QuickAppointmentModal({
    open,
    onOpenChange,
    patientId,
    dentists,
    chairs,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    patientId: number;
    dentists: { id: number; name: string }[];
    chairs: string[];
}) {
    const todayStr = new Date().toISOString().slice(0, 10);
    const form = useForm({
        patient_id: patientId,
        dentist_id: String(dentists[0]?.id ?? ''),
        chair: chairs[0] ?? 'Sillón 1',
        title: 'Control odontológico',
        date: todayStr,
        start_time: '10:00',
        end_time: '10:45',
        reason: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(
            '/agenda',
            {
                patient_id: patientId,
                dentist_id: Number(form.data.dentist_id),
                chair: form.data.chair,
                title: form.data.title,
                starts_at: `${form.data.date} ${form.data.start_time}:00`,
                ends_at: `${form.data.date} ${form.data.end_time}:00`,
                reason: form.data.reason,
            },
            {
                preserveScroll: true,
                onSuccess: () => onOpenChange(false),
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <CalendarPlus className="size-5 text-[#0E8FA3]" />
                        Nueva cita desde ficha del paciente
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-3">
                    <div>
                        <Label>Motivo / Título *</Label>
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
                            <Label>Profesional</Label>
                            <select
                                required
                                value={form.data.dentist_id}
                                onChange={(e) =>
                                    form.setData('dentist_id', e.target.value)
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
                            <Label>Sillón</Label>
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
                                    form.setData('start_time', e.target.value)
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
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            className="gap-1.5 bg-[#0E8FA3] text-white"
                        >
                            <Clock className="size-4" />
                            Agendar cita
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
