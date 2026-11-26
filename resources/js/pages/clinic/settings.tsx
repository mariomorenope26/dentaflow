import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    Building2,
    CheckCircle2,
    Database,
    Download,
    Eye,
    EyeOff,
    HardDriveUpload,
    ImagePlus,
    KeyRound,
    Pencil,
    Printer,
    RotateCcw,
    Save,
    Search,
    ShieldAlert,
    Trash2,
    UserPlus,
    Users,
} from 'lucide-react';
import { useState } from 'react';
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
import type { Auth, PrintSettings } from '@/types';

type Member = {
    id: number;
    name: string;
    email: string;
    role: string;
    role_label: string;
    specialty: string | null;
    license_number: string | null;
    phone: string | null;
    commission_rate: number;
    is_active: boolean;
    is_protected_admin?: boolean;
};

type Props = {
    clinic: {
        id: number;
        name: string;
        ruc: string | null;
        slogan: string | null;
        phone: string | null;
        email: string | null;
        address: string | null;
        logo_url: string | null;
        primary_color: string;
        currency_symbol: string;
        chairs: string[];
        odontogram_notation: 'FDI' | 'UNIVERSAL';
        auto_lock_minutes: number;
        has_apiperu_token: boolean;
        apiperu_token_masked: string | null;
        ai_provider?: 'local' | 'gemini' | 'openai';
        has_ai_api_key?: boolean;
        ai_api_key_masked?: string | null;
        online_booking_enabled?: boolean;
        print_settings: PrintSettings;
    };
    members: Member[];
    roles: { value: string; label: string }[];
    systemStats: {
        patients: number;
        appointments: number;
        payments: number;
        db_driver: string;
    };
    initialSection: string;
};

type SectionKey =
    | 'consultorio'
    | 'impresiones'
    | 'integraciones'
    | 'equipo'
    | 'respaldo';

export default function ClinicSettings({
    clinic,
    members,
    roles,
    systemStats,
    initialSection,
}: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const validSections: SectionKey[] = [
        'consultorio',
        'impresiones',
        'integraciones',
        'equipo',
        'respaldo',
    ];
    const [activeSection, setActiveSection] = useState<SectionKey>(
        validSections.includes(initialSection as SectionKey)
            ? (initialSection as SectionKey)
            : 'consultorio',
    );

    // Datos generales del consultorio + logotipo + impresiones + token apiperu.dev
    const [name, setName] = useState(clinic.name);
    const [ruc, setRuc] = useState(clinic.ruc ?? '');
    const [slogan, setSlogan] = useState(clinic.slogan ?? '');
    const [phone, setPhone] = useState(clinic.phone ?? '');
    const [email, setEmail] = useState(clinic.email ?? '');
    const [address, setAddress] = useState(clinic.address ?? '');
    const [primaryColor, setPrimaryColor] = useState(clinic.primary_color);
    const [currencySymbol, setCurrencySymbol] = useState(
        clinic.currency_symbol || 'S/',
    );
    const [chairsText, setChairsText] = useState(clinic.chairs.join(', '));
    const [odontogramNotation, setOdontogramNotation] = useState<
        'FDI' | 'UNIVERSAL'
    >(clinic.odontogram_notation);
    const [autoLockMinutes, setAutoLockMinutes] = useState(
        clinic.auto_lock_minutes,
    );

    // Logotipo
    const [logoFile, setLogoFile] = useState<File | null>(null);
    const [logoPreview, setLogoPreview] = useState<string | null>(
        clinic.logo_url,
    );
    const [removeLogo, setRemoveLogo] = useState(false);

    // Impresiones
    const [printSettings, setPrintSettings] = useState<PrintSettings>(
        clinic.print_settings,
    );

    // apiperu.dev token + prueba en vivo + IA + Reserva Online
    const [apiperuToken, setApiperuToken] = useState('');
    const [showToken, setShowToken] = useState(false);
    const [clearApiperuToken, setClearApiperuToken] = useState(false);
    const [aiProvider, setAiProvider] = useState<'local' | 'gemini' | 'openai'>(
        clinic.ai_provider ?? 'local',
    );
    const [aiApiKey, setAiApiKey] = useState('');
    const [clearAiApiKey, setClearAiApiKey] = useState(false);
    const [onlineBookingEnabled, setOnlineBookingEnabled] = useState<boolean>(
        clinic.online_booking_enabled ?? true,
    );
    const [savingSettings, setSavingSettings] = useState(false);

    const [testDni, setTestDni] = useState('');
    const [testingDni, setTestingDni] = useState(false);
    const [testResult, setTestResult] = useState<string | null>(null);

    // Equipo CRUD
    const [openInvite, setOpenInvite] = useState(false);
    const [editingMember, setEditingMember] = useState<Member | null>(null);
    const [deletingMember, setDeletingMember] = useState<Member | null>(null);

    // Respaldo y Reinicio
    const [restoreFile, setRestoreFile] = useState<File | null>(null);
    const [restorePassword, setRestorePassword] = useState('');
    const [restoring, setRestoring] = useState(false);

    const [openResetModal, setOpenResetModal] = useState(false);
    const [resetConfirmation, setResetConfirmation] = useState('');
    const [resetPassword, setResetPassword] = useState('');
    const [keepCatalog, setKeepCatalog] = useState(true);
    const [resetting, setResetting] = useState(false);

    const userForm = useForm({
        name: '',
        email: '',
        role: 'odontologo',
        specialty: '',
        license_number: '',
        phone: '',
        commission_rate: '40',
        password: '',
    });

    const editUserForm = useForm({
        name: '',
        email: '',
        role: 'odontologo',
        specialty: '',
        license_number: '',
        phone: '',
        commission_rate: '40',
        password: '',
        is_active: true,
    });

    const onSelectLogo = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;
        setLogoFile(file);
        setRemoveLogo(false);
        setLogoPreview(URL.createObjectURL(file));
    };

    const handleRemoveLogo = () => {
        setLogoFile(null);
        setLogoPreview(null);
        setRemoveLogo(true);
    };

    const saveAllSettings = (e?: React.FormEvent) => {
        e?.preventDefault();
        const chairList = chairsText
            .split(',')
            .map((s) => s.trim())
            .filter(Boolean);

        setSavingSettings(true);

        const payload: Record<string, unknown> = {
            _method: 'patch',
            name,
            ruc: ruc || '',
            slogan: slogan || '',
            phone: phone || '',
            email: email || '',
            address: address || '',
            primary_color: primaryColor,
            currency_symbol: currencySymbol,
            chairs: chairList.length > 0 ? chairList : ['Sillón 1'],
            odontogram_notation: odontogramNotation,
            auto_lock_minutes: autoLockMinutes,
            remove_logo: removeLogo ? 1 : 0,
            clear_apiperu_token: clearApiperuToken ? 1 : 0,
            ai_provider: aiProvider,
            clear_ai_api_key: clearAiApiKey ? 1 : 0,
            online_booking_enabled: onlineBookingEnabled ? 1 : 0,
            print_settings: {
                paper_size: printSettings.paper_size,
                thermal_width: printSettings.thermal_width,
                show_logo: printSettings.show_logo ? 1 : 0,
                show_cop: printSettings.show_cop ? 1 : 0,
                header_text: printSettings.header_text,
                footer_text: printSettings.footer_text,
            },
        };

        if (logoFile) {
            payload.logo = logoFile;
        }
        if (apiperuToken.trim() !== '') {
            payload.apiperu_token = apiperuToken.trim();
        }
        if (aiApiKey.trim() !== '') {
            payload.ai_api_key = aiApiKey.trim();
        }

        router.post('/clinic/settings', payload as never, {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => setSavingSettings(false),
            onSuccess: () => {
                setLogoFile(null);
                setRemoveLogo(false);
                setApiperuToken('');
                setClearApiperuToken(false);
                setAiApiKey('');
                setClearAiApiKey(false);
            },
        });
    };

    const handleTestDni = async () => {
        if (!/^\d{8}$/.test(testDni.trim())) {
            toast.error('Ingresa un DNI válido de 8 dígitos para probar.');
            return;
        }
        setTestingDni(true);
        setTestResult(null);
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
                body: JSON.stringify({ dni: testDni.trim() }),
            });
            const body = await res.json();
            if (!res.ok) {
                setTestResult(
                    `Error (${res.status}): ${body.message ?? 'No se pudo consultar'}`,
                );
                return;
            }
            if (body.found_local) {
                setTestResult(
                    `Encontrado en pacientes locales: ${body.patient.full_name} (HC: ${body.patient.history_number})`,
                );
            } else {
                setTestResult(
                    `RENIEC (apiperu.dev) OK: ${body.data.full_name}${body.data.address ? ` · Dir: ${body.data.address}` : ''}`,
                );
            }
        } catch {
            setTestResult('Error de red al conectar con el servidor.');
        } finally {
            setTestingDni(false);
        }
    };

    const createUser = (e: React.FormEvent) => {
        e.preventDefault();
        userForm.post('/clinic/users', {
            preserveScroll: true,
            onSuccess: () => {
                setOpenInvite(false);
                userForm.reset();
            },
        });
    };

    const openEditMember = (m: Member) => {
        setEditingMember(m);
        editUserForm.setData({
            name: m.name,
            email: m.email,
            role: m.role,
            specialty: m.specialty ?? '',
            license_number: m.license_number ?? '',
            phone: m.phone ?? '',
            commission_rate: String(m.commission_rate ?? 40),
            password: '',
            is_active: m.is_active,
        });
    };

    const submitEditMember = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingMember) return;
        editUserForm.patch(`/clinic/users/${editingMember.id}`, {
            preserveScroll: true,
            onSuccess: () => setEditingMember(null),
        });
    };

    const toggleMemberStatus = (m: Member) => {
        router.patch(
            `/clinic/users/${m.id}`,
            { is_active: !m.is_active },
            { preserveScroll: true },
        );
    };

    const confirmDeleteMember = () => {
        if (!deletingMember) return;
        router.delete(`/clinic/users/${deletingMember.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeletingMember(null),
        });
    };

    const submitRestore = (e: React.FormEvent) => {
        e.preventDefault();
        if (!restoreFile || !restorePassword) {
            toast.error('Selecciona un archivo .json e ingresa tu contraseña.');
            return;
        }
        setRestoring(true);
        router.post(
            '/clinic/backup/restore',
            {
                backup_file: restoreFile,
                password: restorePassword,
            } as never,
            {
                forceFormData: true,
                preserveScroll: true,
                onFinish: () => setRestoring(false),
                onSuccess: () => {
                    setRestoreFile(null);
                    setRestorePassword('');
                },
            },
        );
    };

    const submitReset = (e: React.FormEvent) => {
        e.preventDefault();
        setResetting(true);
        router.post(
            '/clinic/backup/reset',
            {
                confirmation: resetConfirmation,
                password: resetPassword,
                keep_catalog: keepCatalog,
            },
            {
                onFinish: () => setResetting(false),
                onSuccess: () => {
                    setOpenResetModal(false);
                    setResetConfirmation('');
                    setResetPassword('');
                },
            },
        );
    };

    return (
        <>
            <Head title="Ajustes del sistema" />
            <div className="mx-auto max-w-6xl space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">
                        Centro de Ajustes del Consultorio
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Configura la identidad y logotipo, impresiones y
                        tickets, integración RENIEC (apiperu.dev), equipo y
                        copias de seguridad
                    </p>
                </div>

                {/* Navegación por pestañas */}
                <div className="flex gap-1.5 overflow-x-auto rounded-2xl border bg-card p-1.5 shadow-xs">
                    {(
                        [
                            ['consultorio', 'Consultorio y Logo', Building2],
                            ['impresiones', 'Impresiones y Formatos', Printer],
                            [
                                'integraciones',
                                'Búsqueda DNI (apiperu.dev)',
                                KeyRound,
                            ],
                            ['equipo', 'Equipo y Roles', Users],
                            ['respaldo', 'Respaldo y Reinicio', Database],
                        ] as const
                    ).map(([key, label, Icon]) => (
                        <button
                            key={key}
                            type="button"
                            onClick={() => setActiveSection(key)}
                            className={`inline-flex min-h-10 shrink-0 items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-semibold transition-colors sm:text-sm ${
                                activeSection === key
                                    ? 'bg-[#0E8FA3] text-white shadow-xs'
                                    : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground'
                            }`}
                        >
                            <Icon className="size-4" />
                            {label}
                        </button>
                    ))}
                </div>

                {/* TAB 1: CONSULTORIO Y LOGOTIPO */}
                {activeSection === 'consultorio' && (
                    <form
                        onSubmit={saveAllSettings}
                        className="space-y-6 rounded-2xl border bg-card p-5 shadow-xs"
                    >
                        <div className="flex flex-col gap-5 border-b pb-5 sm:flex-row sm:items-center">
                            <div className="flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed bg-muted/30">
                                {logoPreview ? (
                                    <img
                                        src={logoPreview}
                                        alt="Logotipo del consultorio"
                                        className="h-full w-full object-contain p-1"
                                    />
                                ) : (
                                    <ImagePlus className="size-8 text-muted-foreground" />
                                )}
                            </div>
                            <div className="space-y-2">
                                <h2 className="text-base font-bold">
                                    Logotipo del consultorio
                                </h2>
                                <p className="text-xs text-muted-foreground">
                                    Se mostrará en la barra lateral, historias
                                    clínicas PDF, consentimientos y tickets
                                    térmicos. Formato PNG/JPG/WebP (máx. 2 MB).
                                </p>
                                <div className="flex flex-wrap items-center gap-2">
                                    <label className="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl border bg-background px-3 text-xs font-semibold hover:bg-accent">
                                        <ImagePlus className="size-3.5 text-[#0E8FA3]" />
                                        {logoPreview
                                            ? 'Cambiar logotipo'
                                            : 'Subir logotipo'}
                                        <input
                                            type="file"
                                            accept="image/png,image/jpeg,image/webp"
                                            className="hidden"
                                            onChange={onSelectLogo}
                                        />
                                    </label>
                                    {logoPreview && (
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="ghost"
                                            onClick={handleRemoveLogo}
                                            className="h-9 text-xs text-[#D64545]"
                                        >
                                            <Trash2 className="mr-1 size-3.5" />
                                            Quitar logotipo
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <Label>Nombre del consultorio *</Label>
                                <Input
                                    required
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
                                />
                            </div>
                            <div>
                                <Label>RUC / Identificación tributaria</Label>
                                <Input
                                    value={ruc}
                                    onChange={(e) => setRuc(e.target.value)}
                                />
                            </div>
                            <div className="sm:col-span-2">
                                <Label>
                                    Lema / Especialidades (cabecera de
                                    documentos)
                                </Label>
                                <Input
                                    value={slogan}
                                    onChange={(e) => setSlogan(e.target.value)}
                                />
                            </div>
                            <div>
                                <Label>Teléfono</Label>
                                <Input
                                    value={phone}
                                    onChange={(e) => setPhone(e.target.value)}
                                />
                            </div>
                            <div>
                                <Label>Correo institucional</Label>
                                <Input
                                    type="email"
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                />
                            </div>
                            <div className="sm:col-span-2">
                                <Label>Dirección</Label>
                                <Input
                                    value={address}
                                    onChange={(e) => setAddress(e.target.value)}
                                />
                            </div>
                            <div className="sm:col-span-2">
                                <Label>
                                    Sillones / Unidades dentales (separados por
                                    coma)
                                </Label>
                                <Input
                                    value={chairsText}
                                    onChange={(e) =>
                                        setChairsText(e.target.value)
                                    }
                                />
                            </div>
                            <div>
                                <Label>
                                    Numeración por defecto del odontograma
                                </Label>
                                <select
                                    value={odontogramNotation}
                                    onChange={(e) =>
                                        setOdontogramNotation(
                                            e.target.value as
                                                | 'FDI'
                                                | 'UNIVERSAL',
                                        )
                                    }
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                                >
                                    <option value="FDI">
                                        FDI (Estándar internacional)
                                    </option>
                                    <option value="UNIVERSAL">
                                        Universal (1-32 / A-T)
                                    </option>
                                </select>
                            </div>
                            <div>
                                <Label>
                                    Bloqueo automático por inactividad (minutos)
                                </Label>
                                <Input
                                    type="number"
                                    min={1}
                                    max={240}
                                    value={autoLockMinutes}
                                    onChange={(e) =>
                                        setAutoLockMinutes(
                                            Number(e.target.value),
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Símbolo de moneda</Label>
                                <Input
                                    value={currencySymbol}
                                    onChange={(e) =>
                                        setCurrencySymbol(e.target.value)
                                    }
                                />
                            </div>
                            <div>
                                <Label>
                                    Color institucional (PDF y membretes)
                                </Label>
                                <div className="mt-1 flex items-center gap-3">
                                    <Input
                                        type="color"
                                        value={primaryColor}
                                        onChange={(e) =>
                                            setPrimaryColor(e.target.value)
                                        }
                                        className="h-10 w-20 p-1"
                                    />
                                    <span className="font-mono text-xs text-muted-foreground">
                                        {primaryColor}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div className="flex justify-end border-t pt-4">
                            <Button
                                type="submit"
                                disabled={savingSettings}
                                className="gap-1.5 bg-[#0E8FA3] text-white hover:bg-[#0A6577]"
                            >
                                <Save className="size-4" />
                                {savingSettings
                                    ? 'Guardando...'
                                    : 'Guardar datos del consultorio'}
                            </Button>
                        </div>
                    </form>
                )}

                {/* TAB 2: IMPRESIONES Y FORMATOS */}
                {activeSection === 'impresiones' && (
                    <form
                        onSubmit={saveAllSettings}
                        className="grid gap-6 lg:grid-cols-12"
                    >
                        <div className="space-y-4 rounded-2xl border bg-card p-5 shadow-xs lg:col-span-7">
                            <div className="flex items-center gap-2 border-b pb-3">
                                <Printer className="size-5 text-[#0E8FA3]" />
                                <h2 className="text-base font-bold">
                                    Formatos de impresión y ticketera
                                </h2>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <Label>
                                        Tamaño de hoja clínica (HC /
                                        Consentimientos / Odontograma)
                                    </Label>
                                    <select
                                        value={printSettings.paper_size}
                                        onChange={(e) =>
                                            setPrintSettings((p) => ({
                                                ...p,
                                                paper_size: e.target.value as
                                                    | 'a4'
                                                    | 'letter',
                                            }))
                                        }
                                        className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                                    >
                                        <option value="a4">
                                            A4 (210 × 297 mm) — Recomendado
                                        </option>
                                        <option value="letter">
                                            Carta / Letter (216 × 279 mm)
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <Label>
                                        Ancho predeterminado impresora térmica
                                        (Caja)
                                    </Label>
                                    <select
                                        value={printSettings.thermal_width}
                                        onChange={(e) =>
                                            setPrintSettings((p) => ({
                                                ...p,
                                                thermal_width: e.target
                                                    .value as '80mm' | '58mm',
                                            }))
                                        }
                                        className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                                    >
                                        <option value="80mm">
                                            80 mm (Ticketera estándar POS)
                                        </option>
                                        <option value="58mm">
                                            58 mm (Ticketera compacta / móvil)
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div className="space-y-2.5 rounded-xl border bg-muted/20 p-3.5 text-sm">
                                <label className="flex items-center gap-2.5">
                                    <input
                                        type="checkbox"
                                        checked={printSettings.show_logo}
                                        onChange={(e) =>
                                            setPrintSettings((p) => ({
                                                ...p,
                                                show_logo: e.target.checked,
                                            }))
                                        }
                                    />
                                    <span>
                                        Incluir logotipo del consultorio en PDF
                                        y tickets térmicos
                                    </span>
                                </label>
                                <label className="flex items-center gap-2.5">
                                    <input
                                        type="checkbox"
                                        checked={printSettings.show_cop}
                                        onChange={(e) =>
                                            setPrintSettings((p) => ({
                                                ...p,
                                                show_cop: e.target.checked,
                                            }))
                                        }
                                    />
                                    <span>
                                        Mostrar número de colegiatura (COP) del
                                        odontólogo en historias y recetas
                                    </span>
                                </label>
                            </div>

                            <div>
                                <Label>
                                    Texto superior personalizado (opcional en
                                    membretes y tickets)
                                </Label>
                                <Input
                                    placeholder="Ej. Atención Lun-Sáb 9:00 a.m. a 8:00 p.m. · Citas al WhatsApp"
                                    value={printSettings.header_text}
                                    onChange={(e) =>
                                        setPrintSettings((p) => ({
                                            ...p,
                                            header_text: e.target.value,
                                        }))
                                    }
                                />
                            </div>

                            <div>
                                <Label>
                                    Mensaje de pie de página (recetas, recibos y
                                    cierres)
                                </Label>
                                <Input
                                    placeholder="Gracias por confiar su salud bucal en nosotros."
                                    value={printSettings.footer_text}
                                    onChange={(e) =>
                                        setPrintSettings((p) => ({
                                            ...p,
                                            footer_text: e.target.value,
                                        }))
                                    }
                                />
                            </div>

                            <div className="flex justify-end border-t pt-4">
                                <Button
                                    type="submit"
                                    disabled={savingSettings}
                                    className="gap-1.5 bg-[#0E8FA3] text-white hover:bg-[#0A6577]"
                                >
                                    <Save className="size-4" />
                                    Guardar formatos de impresión
                                </Button>
                            </div>
                        </div>

                        {/* Vista previa del ticket térmico */}
                        <div className="rounded-2xl border bg-muted/30 p-5 lg:col-span-5">
                            <div className="mb-3 flex items-center justify-between">
                                <span className="text-xs font-bold tracking-wider text-muted-foreground uppercase">
                                    Vista previa Ticket (
                                    {printSettings.thermal_width})
                                </span>
                                <Badge variant="outline">
                                    {printSettings.paper_size.toUpperCase()}
                                </Badge>
                            </div>

                            <div
                                className={`mx-auto rounded-lg border bg-white p-4 font-mono text-[11px] text-slate-900 shadow-sm ${
                                    printSettings.thermal_width === '58mm'
                                        ? 'max-w-[235px]'
                                        : 'max-w-[300px]'
                                }`}
                            >
                                {printSettings.show_logo && logoPreview && (
                                    <div className="mb-2 flex justify-center">
                                        <img
                                            src={logoPreview}
                                            alt="Logo preview"
                                            className="max-h-10 object-contain"
                                        />
                                    </div>
                                )}
                                <div className="text-center">
                                    <div className="text-xs font-bold uppercase">
                                        {name}
                                    </div>
                                    {ruc && (
                                        <div className="text-[10px] text-slate-500">
                                            RUC: {ruc}
                                        </div>
                                    )}
                                    {printSettings.header_text && (
                                        <div className="mt-0.5 text-[9px] text-slate-500 italic">
                                            {printSettings.header_text}
                                        </div>
                                    )}
                                </div>
                                <div className="my-2 rounded bg-slate-900 py-1 text-center text-[10px] font-bold text-white">
                                    REPORTE DE CIERRE DE CAJA
                                </div>
                                <div className="space-y-1 border-b border-dashed border-slate-300 pb-2 text-[10px]">
                                    <div className="flex justify-between">
                                        <span>Efectivo</span>
                                        <span>{currencySymbol} 450.00</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span>Yape / Plin</span>
                                        <span>{currencySymbol} 320.00</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span>Tarjeta POS</span>
                                        <span>{currencySymbol} 280.00</span>
                                    </div>
                                </div>
                                <div className="mt-2 flex justify-between text-xs font-bold">
                                    <span>TOTAL RECAUDADO</span>
                                    <span>{currencySymbol} 1,050.00</span>
                                </div>
                                {printSettings.footer_text && (
                                    <div className="mt-3 border-t border-dashed border-slate-300 pt-2 text-center text-[9px] text-slate-500">
                                        {printSettings.footer_text}
                                    </div>
                                )}
                            </div>
                        </div>
                    </form>
                )}

                {/* TAB 3: INTEGRACIÓN APIPERU.DEV */}
                {activeSection === 'integraciones' && (
                    <div className="space-y-5">
                        <form
                            onSubmit={saveAllSettings}
                            className="space-y-4 rounded-2xl border bg-card p-5 shadow-xs"
                        >
                            <div className="flex flex-wrap items-center justify-between gap-2 border-b pb-3">
                                <div className="flex items-center gap-2">
                                    <KeyRound className="size-5 text-[#0E8FA3]" />
                                    <div>
                                        <h2 className="text-base font-bold">
                                            Token de consulta RENIEC
                                            (apiperu.dev)
                                        </h2>
                                        <p className="text-xs text-muted-foreground">
                                            Permite buscar pacientes por DNI y
                                            autocompletar nombres, apellidos y
                                            dirección al registrar fichas
                                            nuevas.
                                        </p>
                                    </div>
                                </div>
                                {clinic.has_apiperu_token ? (
                                    <Badge className="gap-1 bg-[#DCFCE7] text-[#166534] hover:bg-[#DCFCE7]">
                                        <CheckCircle2 className="size-3.5" />
                                        Token configurado (
                                        {clinic.apiperu_token_masked})
                                    </Badge>
                                ) : (
                                    <Badge
                                        variant="outline"
                                        className="border-amber-400 bg-amber-50 text-amber-800"
                                    >
                                        Sin token configurado
                                    </Badge>
                                )}
                            </div>

                            <div>
                                <Label>
                                    {clinic.has_apiperu_token
                                        ? 'Reemplazar Token Bearer de apiperu.dev'
                                        : 'Token Bearer de apiperu.dev'}
                                </Label>
                                <div className="mt-1 flex gap-2">
                                    <Input
                                        type={showToken ? 'text' : 'password'}
                                        placeholder={
                                            clinic.has_apiperu_token
                                                ? 'Deja en blanco para conservar el token actual o pega uno nuevo...'
                                                : 'Pega aquí tu API Token de apiperu.dev'
                                        }
                                        value={apiperuToken}
                                        onChange={(e) =>
                                            setApiperuToken(e.target.value)
                                        }
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => setShowToken((v) => !v)}
                                    >
                                        {showToken ? (
                                            <EyeOff className="size-4" />
                                        ) : (
                                            <Eye className="size-4" />
                                        )}
                                    </Button>
                                </div>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    El token se almacena cifrado en la base de
                                    datos y nunca se expone en el navegador.
                                </p>
                            </div>

                            {clinic.has_apiperu_token && (
                                <label className="flex items-center gap-2 text-xs text-[#D64545]">
                                    <input
                                        type="checkbox"
                                        checked={clearApiperuToken}
                                        onChange={(e) =>
                                            setClearApiperuToken(
                                                e.target.checked,
                                            )
                                        }
                                    />
                                    Eliminar token guardado actualmente
                                </label>
                            )}

                            <div className="flex justify-end">
                                <Button
                                    type="submit"
                                    disabled={savingSettings}
                                    className="gap-1.5 bg-[#0E8FA3] text-white hover:bg-[#0A6577]"
                                >
                                    <Save className="size-4" />
                                    Guardar token apiperu.dev
                                </Button>
                            </div>
                        </form>

                        {/* Probador en vivo de consulta DNI */}
                        <div className="space-y-3 rounded-2xl border bg-card p-5 shadow-xs">
                            <h3 className="text-sm font-bold">
                                Probar búsqueda por DNI en vivo
                            </h3>
                            <p className="text-xs text-muted-foreground">
                                Primero verifica si el paciente ya existe en tu
                                consultorio para evitar historias duplicadas; si
                                es nuevo, consulta directamente a apiperu.dev.
                            </p>
                            <div className="flex max-w-md gap-2">
                                <Input
                                    maxLength={8}
                                    placeholder="Ingresa un DNI de 8 dígitos..."
                                    value={testDni}
                                    onChange={(e) =>
                                        setTestDni(
                                            e.target.value.replace(/\D/g, ''),
                                        )
                                    }
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={testingDni}
                                    onClick={() => void handleTestDni()}
                                    className="gap-1.5"
                                >
                                    <Search className="size-4 text-[#0E8FA3]" />
                                    {testingDni
                                        ? 'Consultando...'
                                        : 'Consultar'}
                                </Button>
                            </div>
                            {testResult && (
                                <div className="rounded-xl border bg-muted/40 px-3 py-2 text-xs font-medium">
                                    {testResult}
                                </div>
                            )}
                        </div>

                        {/* Motor de Inteligencia Artificial y Reserva Online 24/7 */}
                        <form
                            onSubmit={saveAllSettings}
                            className="space-y-4 rounded-2xl border bg-card p-5 shadow-xs"
                        >
                            <div className="flex flex-wrap items-center justify-between gap-2 border-b pb-3">
                                <div>
                                    <h2 className="text-base font-bold">
                                        Inteligencia Artificial Clínica y
                                        Reserva Online 24/7
                                    </h2>
                                    <p className="text-xs text-muted-foreground">
                                        Motor integrado local listo para usar
                                        sin costo adicional, o conecta tu clave
                                        opcional de Gemini / OpenAI y activa el
                                        portal público de citas.
                                    </p>
                                </div>
                                <Badge className="bg-[#E6F5F7] text-[#0A6577] hover:bg-[#E6F5F7]">
                                    Motor activo:{' '}
                                    {aiProvider === 'local'
                                        ? 'IA Clínica Integrada'
                                        : aiProvider.toUpperCase()}
                                </Badge>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <Label>Proveedor de IA Clínica</Label>
                                    <select
                                        value={aiProvider}
                                        onChange={(e) =>
                                            setAiProvider(
                                                e.target.value as
                                                    | 'local'
                                                    | 'gemini'
                                                    | 'openai',
                                            )
                                        }
                                        className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                                    >
                                        <option value="local">
                                            Motor Clínico IA Integrado
                                            (Inmediato / Sin costo)
                                        </option>
                                        <option value="gemini">
                                            Google Gemini API (Texto + Visión
                                            Rx)
                                        </option>
                                        <option value="openai">
                                            OpenAI GPT-4o mini
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <Label>
                                        {clinic.has_ai_api_key
                                            ? `Reemplazar API Key IA (${clinic.ai_api_key_masked})`
                                            : 'API Key externa de IA (opcional)'}
                                    </Label>
                                    <Input
                                        type="password"
                                        disabled={aiProvider === 'local'}
                                        placeholder={
                                            aiProvider === 'local'
                                                ? 'No requiere clave externa'
                                                : 'Pega aquí tu API Key secreta'
                                        }
                                        value={aiApiKey}
                                        onChange={(e) =>
                                            setAiApiKey(e.target.value)
                                        }
                                    />
                                </div>
                            </div>

                            {clinic.has_ai_api_key && (
                                <label className="flex items-center gap-2 text-xs text-[#D64545]">
                                    <input
                                        type="checkbox"
                                        checked={clearAiApiKey}
                                        onChange={(e) =>
                                            setClearAiApiKey(e.target.checked)
                                        }
                                    />
                                    Eliminar clave de IA guardada
                                </label>
                            )}

                            <label className="flex items-center gap-2 rounded-xl border bg-muted/20 p-3 text-xs font-medium">
                                <input
                                    type="checkbox"
                                    checked={onlineBookingEnabled}
                                    onChange={(e) =>
                                        setOnlineBookingEnabled(
                                            e.target.checked,
                                        )
                                    }
                                />
                                Habilitar portal público de Reserva de Citas
                                Online 24/7 (/reservar-cita)
                            </label>

                            <div className="flex justify-end">
                                <Button
                                    type="submit"
                                    disabled={savingSettings}
                                    className="gap-1.5 bg-[#0E8FA3] text-white hover:bg-[#0A6577]"
                                >
                                    <Save className="size-4" />
                                    Guardar configuración IA y Reserva
                                </Button>
                            </div>
                        </form>
                    </div>
                )}

                {/* TAB 4: EQUIPO Y PERMISOS */}
                {activeSection === 'equipo' && (
                    <div className="rounded-2xl border bg-card p-5 shadow-xs">
                        <div className="mb-4 flex items-center justify-between border-b pb-3">
                            <div className="flex items-center gap-2">
                                <Users className="size-5 text-[#0E8FA3]" />
                                <h2 className="text-base font-bold">
                                    Equipo del consultorio y permisos por rol (
                                    {members.length})
                                </h2>
                            </div>
                            <Button
                                onClick={() => setOpenInvite(true)}
                                className="gap-1.5 bg-[#0E8FA3] text-xs text-white hover:bg-[#0A6577]"
                            >
                                <UserPlus className="size-4" />
                                Agregar usuario
                            </Button>
                        </div>

                        <div className="divide-y">
                            {members.map((m) => (
                                <div
                                    key={m.id}
                                    className="flex flex-wrap items-center justify-between gap-3 py-3"
                                >
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="font-semibold">
                                                {m.name}
                                            </span>
                                            <Badge variant="secondary">
                                                {m.role_label}
                                            </Badge>
                                            {(m.is_protected_admin ||
                                                m.role ===
                                                    'admin_consultorio') && (
                                                <Badge className="bg-amber-100 text-amber-800 hover:bg-amber-100">
                                                    Rol protegido
                                                </Badge>
                                            )}
                                            {m.id === auth.user?.id && (
                                                <Badge variant="outline">
                                                    Tu cuenta
                                                </Badge>
                                            )}
                                            {!m.is_active && (
                                                <Badge variant="destructive">
                                                    Desactivado
                                                </Badge>
                                            )}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {m.email}
                                            {m.specialty
                                                ? ` · ${m.specialty}`
                                                : ''}
                                            {m.license_number
                                                ? ` · ${m.license_number}`
                                                : ''}
                                            {m.phone ? ` · ${m.phone}` : ''} ·
                                            Comisión: {m.commission_rate}%
                                        </div>
                                    </div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            className="h-8 gap-1 text-xs"
                                            onClick={() => openEditMember(m)}
                                        >
                                            <Pencil className="size-3.5" />
                                            Editar
                                        </Button>
                                        {m.id !== auth.user?.id &&
                                            m.role !== 'admin_consultorio' && (
                                                <>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        className="h-8 text-xs"
                                                        onClick={() =>
                                                            toggleMemberStatus(
                                                                m,
                                                            )
                                                        }
                                                    >
                                                        {m.is_active
                                                            ? 'Desactivar'
                                                            : 'Reactivar'}
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        className="h-8 text-xs text-[#D64545]"
                                                        onClick={() =>
                                                            setDeletingMember(m)
                                                        }
                                                    >
                                                        <Trash2 className="size-3.5" />
                                                    </Button>
                                                </>
                                            )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* TAB 5: RESPALDO, RESTAURACIÓN Y REINICIO */}
                {activeSection === 'respaldo' && (
                    <div className="space-y-6">
                        {/* Métricas actuales */}
                        <div className="grid gap-4 sm:grid-cols-4">
                            <div className="rounded-2xl border bg-card p-4 shadow-xs">
                                <div className="text-xs text-muted-foreground">
                                    Pacientes registrados
                                </div>
                                <div className="mt-1 text-2xl font-bold text-[#0A6577]">
                                    {systemStats.patients}
                                </div>
                            </div>
                            <div className="rounded-2xl border bg-card p-4 shadow-xs">
                                <div className="text-xs text-muted-foreground">
                                    Citas almacenadas
                                </div>
                                <div className="mt-1 text-2xl font-bold">
                                    {systemStats.appointments}
                                </div>
                            </div>
                            <div className="rounded-2xl border bg-card p-4 shadow-xs">
                                <div className="text-xs text-muted-foreground">
                                    Pagos registrados
                                </div>
                                <div className="mt-1 text-2xl font-bold text-[#2E9E6B]">
                                    {systemStats.payments}
                                </div>
                            </div>
                            <div className="rounded-2xl border bg-card p-4 shadow-xs">
                                <div className="text-xs text-muted-foreground">
                                    Motor de base de datos
                                </div>
                                <div className="mt-1 text-lg font-bold uppercase">
                                    {systemStats.db_driver}
                                </div>
                            </div>
                        </div>

                        <div className="grid gap-6 lg:grid-cols-2">
                            {/* Exportar */}
                            <div className="space-y-4 rounded-2xl border bg-card p-5 shadow-xs">
                                <div className="flex items-center gap-2 border-b pb-3">
                                    <Download className="size-5 text-[#0E8FA3]" />
                                    <h2 className="text-base font-bold">
                                        Exportar copia de seguridad
                                    </h2>
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    El respaldo portable en formato JSON incluye
                                    firma de integridad (HMAC-SHA256) y contiene
                                    consultorio, usuarios, catálogo, pacientes,
                                    historias, odontogramas, evoluciones, planes
                                    de tratamiento, citas, caja e imágenes.
                                </p>
                                <div className="flex flex-wrap gap-3">
                                    <a
                                        href="/clinic/backup/export-json"
                                        className="inline-flex h-10 items-center gap-2 rounded-xl bg-[#0E8FA3] px-4 text-xs font-semibold text-white hover:bg-[#0A6577]"
                                    >
                                        <Download className="size-4" />
                                        Descargar Respaldo JSON (Restaurable)
                                    </a>
                                    {systemStats.db_driver === 'mysql' && (
                                        <a
                                            href="/clinic/backup/export-sql"
                                            className="inline-flex h-10 items-center gap-2 rounded-xl border bg-background px-4 text-xs font-semibold hover:bg-accent"
                                        >
                                            <Database className="size-4 text-[#0E8FA3]" />
                                            Exportar Volcado MySQL (.sql)
                                        </a>
                                    )}
                                </div>
                            </div>

                            {/* Restaurar */}
                            <form
                                onSubmit={submitRestore}
                                className="space-y-3 rounded-2xl border bg-card p-5 shadow-xs"
                            >
                                <div className="flex items-center gap-2 border-b pb-3">
                                    <HardDriveUpload className="size-5 text-[#0E8FA3]" />
                                    <h2 className="text-base font-bold">
                                        Restaurar sistema desde respaldo (.JSON)
                                    </h2>
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Esta acción reemplazará los datos actuales
                                    por los contenidos en el archivo de respaldo
                                    verificado.
                                </p>
                                <div>
                                    <Label>Archivo de respaldo (.json)</Label>
                                    <Input
                                        type="file"
                                        accept=".json,application/json"
                                        required
                                        onChange={(e) =>
                                            setRestoreFile(
                                                e.target.files?.[0] ?? null,
                                            )
                                        }
                                        className="mt-1"
                                    />
                                </div>
                                <div>
                                    <Label>
                                        Contraseña del administrador para
                                        autorizar
                                    </Label>
                                    <Input
                                        type="password"
                                        required
                                        value={restorePassword}
                                        onChange={(e) =>
                                            setRestorePassword(e.target.value)
                                        }
                                    />
                                </div>
                                <div className="flex justify-end">
                                    <Button
                                        type="submit"
                                        disabled={restoring}
                                        variant="outline"
                                        className="gap-1.5 border-[#0E8FA3] text-[#0A6577]"
                                    >
                                        <HardDriveUpload className="size-4" />
                                        {restoring
                                            ? 'Restaurando...'
                                            : 'Restaurar copia de seguridad'}
                                    </Button>
                                </div>
                            </form>
                        </div>

                        {/* Reiniciar desde cero */}
                        <div className="rounded-2xl border border-[#D64545]/40 bg-[#FEF2F2]/60 p-5 dark:bg-red-950/20">
                            <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                                <div className="space-y-1">
                                    <div className="flex items-center gap-2 text-base font-bold text-[#991B1B] dark:text-red-300">
                                        <ShieldAlert className="size-5" />
                                        Borrar todos los datos y volver a
                                        iniciar
                                    </div>
                                    <p className="text-xs text-[#7F1D1D] dark:text-red-200/80">
                                        Elimina permanentemente todos los
                                        pacientes, historias clínicas,
                                        odontogramas, evoluciones, citas, pagos,
                                        cierres de caja, documentos y bitácora.
                                        Tu cuenta de administrador y la
                                        configuración del consultorio se
                                        conservarán.
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    variant="destructive"
                                    onClick={() => setOpenResetModal(true)}
                                    className="shrink-0 gap-1.5"
                                >
                                    <RotateCcw className="size-4" />
                                    Reiniciar sistema
                                </Button>
                            </div>
                        </div>
                    </div>
                )}
            </div>

            {/* MODAL: NUEVO USUARIO */}
            <Dialog open={openInvite} onOpenChange={setOpenInvite}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            Agregar miembro al consultorio
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={createUser} className="space-y-3">
                        <div>
                            <Label>Nombre completo *</Label>
                            <Input
                                required
                                value={userForm.data.name}
                                onChange={(e) =>
                                    userForm.setData('name', e.target.value)
                                }
                            />
                            <InputError message={userForm.errors.name} />
                        </div>
                        <div>
                            <Label>Correo electrónico *</Label>
                            <Input
                                type="email"
                                required
                                value={userForm.data.email}
                                onChange={(e) =>
                                    userForm.setData('email', e.target.value)
                                }
                            />
                            <InputError message={userForm.errors.email} />
                        </div>
                        <div>
                            <Label>Rol en el consultorio *</Label>
                            <select
                                value={userForm.data.role}
                                onChange={(e) =>
                                    userForm.setData('role', e.target.value)
                                }
                                className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                            >
                                {roles.map((r) => (
                                    <option key={r.value} value={r.value}>
                                        {r.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <Label>Especialidad</Label>
                                <Input
                                    value={userForm.data.specialty}
                                    onChange={(e) =>
                                        userForm.setData(
                                            'specialty',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>N.º Colegiatura (COP)</Label>
                                <Input
                                    value={userForm.data.license_number}
                                    onChange={(e) =>
                                        userForm.setData(
                                            'license_number',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <Label>Teléfono</Label>
                                <Input
                                    value={userForm.data.phone}
                                    onChange={(e) =>
                                        userForm.setData(
                                            'phone',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Comisión clínica (%)</Label>
                                <Input
                                    type="number"
                                    step="0.5"
                                    min="0"
                                    max="100"
                                    value={userForm.data.commission_rate}
                                    onChange={(e) =>
                                        userForm.setData(
                                            'commission_rate',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>
                        <div>
                            <Label>Contraseña inicial *</Label>
                            <Input
                                type="password"
                                required
                                minLength={8}
                                value={userForm.data.password}
                                onChange={(e) =>
                                    userForm.setData('password', e.target.value)
                                }
                            />
                            <InputError message={userForm.errors.password} />
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setOpenInvite(false)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                disabled={userForm.processing}
                                className="bg-[#0E8FA3] text-white"
                            >
                                Crear usuario
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* MODAL: EDITAR USUARIO */}
            <Dialog
                open={editingMember !== null}
                onOpenChange={(open) => !open && setEditingMember(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            Editar miembro: {editingMember?.name}
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitEditMember} className="space-y-3">
                        <div>
                            <Label>Nombre completo *</Label>
                            <Input
                                required
                                value={editUserForm.data.name}
                                onChange={(e) =>
                                    editUserForm.setData('name', e.target.value)
                                }
                            />
                            <InputError message={editUserForm.errors.name} />
                        </div>
                        <div>
                            <Label>Correo electrónico *</Label>
                            <Input
                                type="email"
                                required
                                value={editUserForm.data.email}
                                onChange={(e) =>
                                    editUserForm.setData(
                                        'email',
                                        e.target.value,
                                    )
                                }
                            />
                            <InputError message={editUserForm.errors.email} />
                        </div>
                        <div>
                            <Label>Rol *</Label>
                            <select
                                value={editUserForm.data.role}
                                disabled={
                                    editingMember?.role === 'admin_consultorio'
                                }
                                onChange={(e) =>
                                    editUserForm.setData('role', e.target.value)
                                }
                                className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm disabled:cursor-not-allowed disabled:bg-muted/60 disabled:opacity-75"
                            >
                                {roles.map((r) => (
                                    <option key={r.value} value={r.value}>
                                        {r.label}
                                    </option>
                                ))}
                            </select>
                            {editingMember?.role === 'admin_consultorio' && (
                                <p className="mt-1 text-xs text-amber-700 dark:text-amber-300">
                                    El rol de Administrador del consultorio está
                                    protegido y no puede modificarse para evitar
                                    pérdida de acceso administrativo.
                                </p>
                            )}
                            <InputError message={editUserForm.errors.role} />
                        </div>
                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <Label>Especialidad</Label>
                                <Input
                                    value={editUserForm.data.specialty}
                                    onChange={(e) =>
                                        editUserForm.setData(
                                            'specialty',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Colegiatura (COP)</Label>
                                <Input
                                    value={editUserForm.data.license_number}
                                    onChange={(e) =>
                                        editUserForm.setData(
                                            'license_number',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-3 gap-2">
                            <div>
                                <Label>Teléfono</Label>
                                <Input
                                    value={editUserForm.data.phone}
                                    onChange={(e) =>
                                        editUserForm.setData(
                                            'phone',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Comisión (%)</Label>
                                <Input
                                    type="number"
                                    step="0.5"
                                    min="0"
                                    max="100"
                                    value={editUserForm.data.commission_rate}
                                    onChange={(e) =>
                                        editUserForm.setData(
                                            'commission_rate',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Nueva clave</Label>
                                <Input
                                    type="password"
                                    minLength={8}
                                    placeholder="Sin cambios"
                                    value={editUserForm.data.password}
                                    onChange={(e) =>
                                        editUserForm.setData(
                                            'password',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={editUserForm.errors.password}
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setEditingMember(null)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                disabled={editUserForm.processing}
                                className="bg-[#0E8FA3] text-white"
                            >
                                Guardar cambios
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* MODAL: CONFIRMAR ELIMINACIÓN DE USUARIO */}
            <Dialog
                open={deletingMember !== null}
                onOpenChange={(open) => !open && setDeletingMember(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Eliminar miembro del equipo</DialogTitle>
                        <DialogDescription>
                            ¿Seguro que deseas eliminar la cuenta de{' '}
                            <strong>{deletingMember?.name}</strong> (
                            {deletingMember?.email})? Esta acción no se puede
                            deshacer.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setDeletingMember(null)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={confirmDeleteMember}
                        >
                            Eliminar usuario
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* MODAL: REINICIAR SISTEMA */}
            <Dialog open={openResetModal} onOpenChange={setOpenResetModal}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-[#D64545]">
                            <AlertTriangle className="size-5" />
                            Confirmar reinicio completo de datos
                        </DialogTitle>
                        <DialogDescription>
                            Recomendamos descargar primero un respaldo JSON.
                            Esta acción vaciará pacientes, historias, citas,
                            caja y archivos cargados.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitReset} className="space-y-3">
                        <label className="flex items-center gap-2 rounded-xl border bg-muted/30 p-3 text-xs font-medium">
                            <input
                                type="checkbox"
                                checked={keepCatalog}
                                onChange={(e) =>
                                    setKeepCatalog(e.target.checked)
                                }
                            />
                            Conservar el catálogo de tratamientos y tarifario
                            actual
                        </label>

                        <div>
                            <Label>
                                Escribe exactamente{' '}
                                <span className="font-mono font-bold text-[#D64545]">
                                    REINICIAR
                                </span>{' '}
                                para continuar *
                            </Label>
                            <Input
                                required
                                placeholder="REINICIAR"
                                value={resetConfirmation}
                                onChange={(e) =>
                                    setResetConfirmation(e.target.value)
                                }
                            />
                        </div>

                        <div>
                            <Label>Contraseña de tu cuenta *</Label>
                            <Input
                                type="password"
                                required
                                value={resetPassword}
                                onChange={(e) =>
                                    setResetPassword(e.target.value)
                                }
                            />
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setOpenResetModal(false)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={
                                    resetting ||
                                    resetConfirmation.trim().toUpperCase() !==
                                        'REINICIAR'
                                }
                            >
                                {resetting
                                    ? 'Reiniciando...'
                                    : 'Borrar todo y reiniciar'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
