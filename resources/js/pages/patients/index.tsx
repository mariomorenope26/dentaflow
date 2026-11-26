import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ExternalLink,
    FileText,
    Plus,
    Search,
    Trash2,
    UserPlus,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
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
import type { Auth, Patient } from '@/types';

type PaginatedPatients = {
    data: Patient[];
    current_page: number;
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

type Props = {
    patients: PaginatedPatients;
    filters: { search: string; tag: string };
    availableTags: string[];
};

export default function PatientsIndex({
    patients,
    filters,
    availableTags,
}: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const canManage = auth.user?.can.manage_patients;
    const urlParams =
        typeof window !== 'undefined'
            ? new URLSearchParams(window.location.search)
            : null;
    const initialNew = urlParams?.get('new') === '1';
    const initialDni = urlParams?.get('dni') ?? '';

    const [openCreate, setOpenCreate] = useState(
        initialNew && Boolean(canManage),
    );
    const [search, setSearch] = useState(filters.search);
    const [lookingUpDni, setLookingUpDni] = useState(false);
    const [localMatch, setLocalMatch] = useState<{
        id: number;
        history_number: string;
        full_name: string;
    } | null>(null);
    const [dniError, setDniError] = useState<string | null>(null);
    const [deletingPatient, setDeletingPatient] = useState<Patient | null>(
        null,
    );

    const form = useForm({
        first_name: '',
        last_name: '',
        document_type: 'DNI',
        document_number: initialDni,
        birth_date: '',
        gender: 'F',
        phone: '',
        email: '',
        address: '',
        occupation: '',
        insurance_provider: '',
        guardian_name: '',
        guardian_phone: '',
        guardian_relationship: '',
        tags: [] as string[],
        notes: '',
    });

    const lookupDni = async (dniInput: string, openModalIfNew = false) => {
        const clean = dniInput.replace(/\D/g, '');
        if (!/^\d{8}$/.test(clean)) {
            toast.error('Ingresa un DNI válido de exactamente 8 dígitos.');
            return;
        }
        setLookingUpDni(true);
        setDniError(null);
        setLocalMatch(null);
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
                const msg = body.message ?? 'No se pudo consultar el DNI.';
                setDniError(msg);
                toast.error(msg);
                return;
            }
            if (body.found_local) {
                setLocalMatch(body.patient);
                toast.info(
                    `El paciente ${body.patient.full_name} ya se encuentra registrado.`,
                );
                return;
            }
            form.setData((prev) => ({
                ...prev,
                document_type: 'DNI',
                document_number: clean,
                first_name: body.data.first_name || prev.first_name,
                last_name: body.data.last_name || prev.last_name,
                address: body.data.address || prev.address,
            }));
            if (openModalIfNew) {
                setOpenCreate(true);
            }
            toast.success('Datos completados desde RENIEC (apiperu.dev).');
        } catch {
            toast.error('Error de conexión al consultar RENIEC.');
        } finally {
            setLookingUpDni(false);
        }
    };

    useEffect(() => {
        if (initialNew && /^\d{8}$/.test(initialDni)) {
            void lookupDni(initialDni, false);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const confirmDeletePatient = () => {
        if (!deletingPatient) return;
        router.delete(`/patients/${deletingPatient.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeletingPatient(null),
        });
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            '/patients',
            { search, tag: filters.tag },
            { preserveState: true },
        );
    };

    const toggleTagFilter = (tag: string) => {
        const nextTag = filters.tag === tag ? '' : tag;
        router.get(
            '/patients',
            { search, tag: nextTag },
            { preserveState: true },
        );
    };

    const toggleFormTag = (tag: string) => {
        form.setData(
            'tags',
            form.data.tags.includes(tag)
                ? form.data.tags.filter((t) => t !== tag)
                : [...form.data.tags, tag],
        );
    };

    const submitCreate = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/patients', {
            onSuccess: () => {
                setOpenCreate(false);
                form.reset();
            },
        });
    };

    return (
        <>
            <Head title="Pacientes" />
            <div className="mx-auto max-w-7xl space-y-5 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">
                            Pacientes
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {patients.total} historia(s) clínica(s) en{' '}
                            {auth.clinic?.name}
                        </p>
                    </div>
                    {canManage && (
                        <Button
                            onClick={() => setOpenCreate(true)}
                            className="h-11 gap-2 rounded-xl bg-[#0E8FA3] px-5 text-white hover:bg-[#0A6577]"
                        >
                            <UserPlus className="size-4" />
                            Nuevo paciente
                        </Button>
                    )}
                </div>

                {/* Buscador y filtros por etiqueta */}
                <div className="space-y-3 rounded-2xl border bg-card p-4 shadow-xs">
                    <form onSubmit={handleSearch} className="flex gap-2">
                        <div className="relative flex-1">
                            <Search className="pointer-events-none absolute top-3 left-3 size-4 text-muted-foreground" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Buscar por nombre, apellido, DNI, teléfono o N.º de Historia Clínica..."
                                className="h-10 rounded-xl pl-9"
                            />
                        </div>
                        <Button
                            type="submit"
                            variant="outline"
                            className="h-10 rounded-xl px-4"
                        >
                            Buscar
                        </Button>
                    </form>

                    <div className="flex flex-wrap items-center gap-2">
                        <span className="text-xs font-medium text-muted-foreground">
                            Etiquetas:
                        </span>
                        {availableTags.map((tag) => (
                            <button
                                key={tag}
                                type="button"
                                onClick={() => toggleTagFilter(tag)}
                                className={`rounded-full border px-3 py-1 text-xs font-medium transition-colors ${
                                    filters.tag === tag
                                        ? 'border-[#0E8FA3] bg-[#0E8FA3] text-white'
                                        : 'bg-background text-muted-foreground hover:bg-accent'
                                }`}
                            >
                                {tag}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Listado de pacientes */}
                <div className="overflow-hidden rounded-2xl border bg-card shadow-xs">
                    {patients.data.length === 0 ? (
                        <div className="space-y-3 p-10 text-center text-sm text-muted-foreground">
                            <p>
                                No se encontraron pacientes con esos criterios.
                            </p>
                            {canManage && /^\d{8}$/.test(search.trim()) && (
                                <div className="mx-auto max-w-md space-y-2 rounded-2xl border border-[#0E8FA3]/30 bg-[#E6F5F7]/60 p-4 text-xs text-foreground">
                                    <p className="font-semibold">
                                        ¿Deseas consultar el DNI{' '}
                                        <span className="font-mono font-bold">
                                            {search.trim()}
                                        </span>{' '}
                                        en RENIEC (apiperu.dev) para
                                        registrarlo?
                                    </p>
                                    <div className="flex flex-wrap justify-center gap-2">
                                        <Button
                                            type="button"
                                            size="sm"
                                            disabled={lookingUpDni}
                                            onClick={() =>
                                                void lookupDni(
                                                    search.trim(),
                                                    true,
                                                )
                                            }
                                            className="bg-[#0E8FA3] text-white hover:bg-[#0A6577]"
                                        >
                                            <Search className="mr-1.5 size-3.5" />
                                            {lookingUpDni
                                                ? 'Consultando RENIEC...'
                                                : 'Consultar DNI y registrar'}
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            onClick={() => {
                                                form.setData(
                                                    'document_number',
                                                    search.trim(),
                                                );
                                                setOpenCreate(true);
                                            }}
                                        >
                                            Registrar manualmente
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </div>
                    ) : (
                        <div className="divide-y">
                            {patients.data.map((patient) => {
                                const hasAlerts =
                                    Boolean(
                                        patient.medical_history?.allergies,
                                    ) ||
                                    (patient.medical_history?.medical_alerts
                                        ?.length ?? 0) > 0;
                                return (
                                    <div
                                        key={patient.id}
                                        className="flex flex-col justify-between gap-3 p-4 transition-colors hover:bg-muted/20 sm:flex-row sm:items-center"
                                    >
                                        <div className="space-y-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="rounded-lg bg-[#E6F5F7] px-2 py-0.5 text-xs font-bold text-[#0A6577]">
                                                    {patient.history_number}
                                                </span>
                                                <Link
                                                    href={`/patients/${patient.id}`}
                                                    className="text-base font-bold text-foreground hover:text-[#0E8FA3]"
                                                >
                                                    {patient.full_name}
                                                </Link>
                                                {hasAlerts && (
                                                    <span className="inline-flex items-center gap-1 rounded-full bg-[#FEE2E2] px-2 py-0.5 text-[11px] font-semibold text-[#D64545]">
                                                        <AlertTriangle className="size-3" />
                                                        Alerta médica
                                                    </span>
                                                )}
                                                {(patient.tags ?? []).map(
                                                    (t) => (
                                                        <Badge
                                                            key={t}
                                                            variant="secondary"
                                                            className="text-[11px]"
                                                        >
                                                            {t}
                                                        </Badge>
                                                    ),
                                                )}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {patient.document_type}{' '}
                                                {patient.document_number ??
                                                    'S/N'}{' '}
                                                ·{' '}
                                                {patient.age !== null
                                                    ? `${patient.age} años`
                                                    : 'Edad S/R'}{' '}
                                                · Tel: {patient.phone ?? '-'} ·{' '}
                                                {patient.clinical_evolutions_count ??
                                                    0}{' '}
                                                atención(es)
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-2">
                                            <Button
                                                asChild
                                                variant="outline"
                                                size="sm"
                                                className="rounded-xl"
                                            >
                                                <Link
                                                    href={`/patients/${patient.id}?tab=odontograma`}
                                                >
                                                    Odontograma
                                                </Link>
                                            </Button>
                                            <Button
                                                asChild
                                                size="sm"
                                                className="gap-1.5 rounded-xl bg-[#0E8FA3] text-white hover:bg-[#0A6577]"
                                            >
                                                <Link
                                                    href={`/patients/${patient.id}`}
                                                >
                                                    <FileText className="size-3.5" />
                                                    Abrir ficha
                                                </Link>
                                            </Button>
                                            {canManage && (
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="ghost"
                                                    title="Eliminar paciente"
                                                    className="h-8 rounded-xl px-2 text-[#D64545]"
                                                    onClick={() =>
                                                        setDeletingPatient(
                                                            patient,
                                                        )
                                                    }
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>

            {/* Modal Nuevo Paciente */}
            <Dialog open={openCreate} onOpenChange={setOpenCreate}>
                <DialogContent className="max-h-[90vh] max-w-2xl overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Plus className="size-5 text-[#0E8FA3]" />
                            Registrar nuevo paciente
                        </DialogTitle>
                        <DialogDescription>
                            Se autogenerará el número de historia clínica y su
                            odontograma inicial.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submitCreate} className="space-y-4">
                        {/* Búsqueda rápida por DNI (apiperu.dev) */}
                        <div className="rounded-xl border border-[#0E8FA3]/30 bg-[#E6F5F7]/50 p-3 dark:bg-cyan-950/20">
                            <div className="grid gap-3 sm:grid-cols-12">
                                <div className="sm:col-span-4">
                                    <Label>Tipo de documento</Label>
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
                                        <option value="DNI">DNI</option>
                                        <option value="CE">
                                            Carné de extranjería
                                        </option>
                                        <option value="PASAPORTE">
                                            Pasaporte
                                        </option>
                                        <option value="OTRO">Otro</option>
                                    </select>
                                </div>
                                <div className="sm:col-span-8">
                                    <Label>
                                        Número de documento (búsqueda RENIEC)
                                    </Label>
                                    <div className="mt-1 flex gap-2">
                                        <Input
                                            placeholder="Ej. 45123456"
                                            value={form.data.document_number}
                                            onChange={(e) => {
                                                setLocalMatch(null);
                                                setDniError(null);
                                                form.setData(
                                                    'document_number',
                                                    e.target.value,
                                                );
                                            }}
                                        />
                                        {form.data.document_type === 'DNI' && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                disabled={lookingUpDni}
                                                onClick={() =>
                                                    void lookupDni(
                                                        form.data
                                                            .document_number,
                                                    )
                                                }
                                                className="shrink-0 gap-1.5 border-[#0E8FA3] text-[#0A6577]"
                                            >
                                                <Search className="size-3.5" />
                                                {lookingUpDni
                                                    ? 'Buscando...'
                                                    : 'Buscar DNI'}
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {localMatch && (
                                <div className="mt-2 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-amber-400 bg-amber-50 px-3 py-2 text-xs text-amber-950">
                                    <span>
                                        Ya registrado:{' '}
                                        <strong>{localMatch.full_name}</strong>{' '}
                                        (HC: {localMatch.history_number})
                                    </span>
                                    <Link
                                        href={`/patients/${localMatch.id}`}
                                        className="inline-flex items-center gap-1 font-bold text-[#0A6577] underline"
                                    >
                                        Abrir ficha{' '}
                                        <ExternalLink className="size-3" />
                                    </Link>
                                </div>
                            )}

                            {dniError && (
                                <div className="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs text-[#D64545]">
                                    <span>{dniError}</span>
                                    <Link
                                        href="/clinic/settings?section=integraciones"
                                        className="font-semibold text-[#0E8FA3] underline"
                                    >
                                        Configurar token apiperu.dev
                                    </Link>
                                </div>
                            )}
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2">
                            <div>
                                <Label>Nombres *</Label>
                                <Input
                                    required
                                    value={form.data.first_name}
                                    onChange={(e) =>
                                        form.setData(
                                            'first_name',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.first_name} />
                            </div>
                            <div>
                                <Label>Apellidos *</Label>
                                <Input
                                    required
                                    value={form.data.last_name}
                                    onChange={(e) =>
                                        form.setData(
                                            'last_name',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.last_name} />
                            </div>
                            <div className="sm:col-span-2">
                                <Label>Dirección</Label>
                                <Input
                                    placeholder="Dirección domiciliaria (se completa con RENIEC si está disponible)"
                                    value={form.data.address}
                                    onChange={(e) =>
                                        form.setData('address', e.target.value)
                                    }
                                />
                            </div>
                            <div>
                                <Label>Fecha de nacimiento</Label>
                                <Input
                                    type="date"
                                    value={form.data.birth_date}
                                    onChange={(e) =>
                                        form.setData(
                                            'birth_date',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Sexo</Label>
                                <select
                                    value={form.data.gender}
                                    onChange={(e) =>
                                        form.setData('gender', e.target.value)
                                    }
                                    className="h-10 w-full rounded-md border bg-background px-3 text-sm"
                                >
                                    <option value="F">Femenino</option>
                                    <option value="M">Masculino</option>
                                    <option value="O">Otro</option>
                                </select>
                            </div>
                            <div>
                                <Label>Teléfono / WhatsApp</Label>
                                <Input
                                    value={form.data.phone}
                                    onChange={(e) =>
                                        form.setData('phone', e.target.value)
                                    }
                                />
                            </div>
                            <div>
                                <Label>Correo electrónico</Label>
                                <Input
                                    type="email"
                                    value={form.data.email}
                                    onChange={(e) =>
                                        form.setData('email', e.target.value)
                                    }
                                />
                            </div>
                            <div>
                                <Label>Ocupación</Label>
                                <Input
                                    value={form.data.occupation}
                                    onChange={(e) =>
                                        form.setData(
                                            'occupation',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Seguro médico</Label>
                                <Input
                                    placeholder="Particular / Rimac / Pacífico"
                                    value={form.data.insurance_provider}
                                    onChange={(e) =>
                                        form.setData(
                                            'insurance_provider',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>

                        <div className="rounded-xl border bg-muted/30 p-3">
                            <div className="mb-2 text-xs font-semibold text-muted-foreground">
                                Apoderado (si es menor de edad)
                            </div>
                            <div className="grid gap-2 sm:grid-cols-3">
                                <Input
                                    placeholder="Nombre completo"
                                    value={form.data.guardian_name}
                                    onChange={(e) =>
                                        form.setData(
                                            'guardian_name',
                                            e.target.value,
                                        )
                                    }
                                />
                                <Input
                                    placeholder="Parentesco (Madre/Padre)"
                                    value={form.data.guardian_relationship}
                                    onChange={(e) =>
                                        form.setData(
                                            'guardian_relationship',
                                            e.target.value,
                                        )
                                    }
                                />
                                <Input
                                    placeholder="Teléfono apoderado"
                                    value={form.data.guardian_phone}
                                    onChange={(e) =>
                                        form.setData(
                                            'guardian_phone',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>

                        <div>
                            <Label className="mb-1.5 block">Etiquetas</Label>
                            <div className="flex flex-wrap gap-2">
                                {availableTags.map((tag) => (
                                    <button
                                        key={tag}
                                        type="button"
                                        onClick={() => toggleFormTag(tag)}
                                        className={`rounded-full border px-3 py-1 text-xs font-medium ${
                                            form.data.tags.includes(tag)
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
                                onClick={() => setOpenCreate(false)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                disabled={form.processing}
                                className="bg-[#0E8FA3] text-white hover:bg-[#0A6577]"
                            >
                                {form.processing
                                    ? 'Registrando...'
                                    : 'Guardar paciente'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Confirmar Eliminación de Paciente */}
            <Dialog
                open={deletingPatient !== null}
                onOpenChange={(open) => !open && setDeletingPatient(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="text-[#D64545]">
                            Eliminar historia de paciente
                        </DialogTitle>
                        <DialogDescription>
                            ¿Seguro que deseas eliminar permanentemente a{' '}
                            <strong>{deletingPatient?.full_name}</strong> (HC:{' '}
                            {deletingPatient?.history_number}) y todo su
                            historial clínico, odontogramas, pagos y citas?
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setDeletingPatient(null)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={confirmDeletePatient}
                        >
                            Eliminar definitivamente
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
