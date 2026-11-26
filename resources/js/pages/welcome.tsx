import { Head, Link, usePage } from '@inertiajs/react';
import {
    Activity,
    ArrowRight,
    Award,
    CalendarCheck2,
    CheckCircle2,
    FileCode2,
    FileSignature,
    FlaskConical,
    MessageCircle,
    Ruler,
    ShieldCheck,
    Sparkles,
    Stethoscope,
    Wallet,
} from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { JmsFooter } from '@/components/jms-footer';
import { dashboard, login } from '@/routes';
import type { Auth } from '@/types';

const STANDARDS = [
    'HL7 FHIR R4',
    'ISO/EN 13606 HCE',
    'ISO 3950 (FDI)',
    'CIE-10 OMS',
    'SUNAT UBL 2.1',
    'Ley N.º 29733',
];

const FEATURE_MODULES = [
    {
        icon: Stethoscope,
        badge: 'Estándar HCE',
        title: 'Historia Clínica & Notas SOAP',
        description:
            'Anamnesis por sistemas, riesgo anestésico ASA, antropometría con IMC automático, examen estomatognático, firma SHA-256 inmutable y exportación HL7 FHIR R4.',
        accent: 'bg-[#E6F5F7] text-[#0E8FA3]',
    },
    {
        icon: Activity,
        badge: 'Diagnóstico visual',
        title: 'Odontograma, Periodonto y Ortodoncia',
        description:
            'Odontograma anatómico interactivo (FDI / Universal, adulto/mixto/niño), sondaje periodontal de 6 sitios por pieza y seguimiento de arcos ortodónticos.',
        accent: 'bg-[#E3F6F1] text-[#2E9E6B]',
    },
    {
        icon: Ruler,
        badge: 'Imagenología Rx',
        title: 'Visor Radiográfico Medible en mm',
        description:
            'Calibración milimétrica sobre radiografías para conductometría y altura ósea, brillo/contraste/negativo, pines de hallazgo y cortina Antes/Después.',
        accent: 'bg-[#EDE9FE] text-[#6D28D9]',
    },
    {
        icon: CalendarCheck2,
        badge: 'Asistencia & Lab',
        title: 'Agenda WhatsApp 1-Clic y Laboratorio',
        description:
            'Recordatorios con enlace público para confirmar o reprogramar citas sin login y trazabilidad de prótesis, coronas y alineadores con alerta por cita.',
        accent: 'bg-[#DBEAFE] text-[#2563EB]',
    },
    {
        icon: ShieldCheck,
        badge: 'Bioseguridad',
        title: 'Autoclave y Kardex de Insumos',
        description:
            'Bitácora de ciclos de esterilización con controles químicos y biológicos por esporas, lotes, alertas de vencimiento y movimientos trazables por paciente.',
        accent: 'bg-[#FEF3C7] text-[#D97706]',
    },
    {
        icon: Wallet,
        badge: 'Tributario & Finanzas',
        title: 'Caja Térmica, SUNAT UBL 2.1 y Cuotas',
        description:
            'Tickets térmicos 80/58mm por medios de pago, Boletas/Facturas XML UBL 2.1 con QR, consulta DNI/RUC vía apiperu.dev, cuotas con morosidad y comisiones.',
        accent: 'bg-[#FCE7F3] text-[#BE185D]',
    },
];

export default function Welcome() {
    const { auth, onlineBookingUrl } = usePage<{
        auth: Auth;
        onlineBookingUrl?: string | null;
    }>().props;

    const whatsappHref =
        'https://wa.me/51959199368?text=' +
        encodeURIComponent(
            'Hola Grupo JMSPERU, me interesa conocer más sobre DentaFlow para mi consultorio odontológico.',
        );

    return (
        <>
            <Head title="DentaFlow · Historia Clínica Electrónica Odontológica" />
            <div className="flex min-h-screen flex-col bg-[#F6FAFB] text-[#1F2D3A] dark:bg-[#0B141C] dark:text-[#E7F1F5]">
                {/* Barra superior elegante */}
                <header className="sticky top-0 z-30 border-b border-[#DCE6EA]/80 bg-white/85 backdrop-blur-md dark:border-[#1E2E3B] dark:bg-[#0E1922]/85">
                    <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-3.5 sm:px-8">
                        <Link href="/" className="flex items-center gap-3">
                            {auth.clinic?.logo_url ? (
                                <img
                                    src={auth.clinic.logo_url}
                                    alt={auth.clinic.name}
                                    className="size-10 rounded-xl border bg-white object-contain p-0.5 shadow-xs"
                                />
                            ) : (
                                <div className="flex size-10 items-center justify-center rounded-xl bg-[#0E8FA3] text-white shadow-sm">
                                    <AppLogoIcon className="size-6" />
                                </div>
                            )}
                            <div>
                                <div className="text-lg font-bold tracking-tight">
                                    DentaFlow
                                </div>
                                <div className="text-[11px] font-medium text-[#5F6F7C] dark:text-[#96AAB8]">
                                    {auth.clinic?.name ??
                                        'Sistema Clínico Odontológico Integral'}
                                </div>
                            </div>
                        </Link>

                        <nav className="hidden items-center gap-6 text-sm font-medium text-[#5F6F7C] md:flex dark:text-[#96AAB8]">
                            <a
                                href="#modulos"
                                className="transition hover:text-[#0E8FA3]"
                            >
                                Módulos clínicos
                            </a>
                            <a
                                href="#estandares"
                                className="transition hover:text-[#0E8FA3]"
                            >
                                Estándares HCE
                            </a>
                            <a
                                href={whatsappHref}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex items-center gap-1.5 text-[#2E9E6B] transition hover:text-[#238056]"
                            >
                                <MessageCircle className="size-4" />
                                Soporte WhatsApp
                            </a>
                        </nav>

                        <div className="flex items-center gap-2.5">
                            <Link
                                href={auth.user ? dashboard() : login()}
                                className="inline-flex items-center gap-2 rounded-xl bg-[#0E8FA3] px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-[#0A6577] sm:px-5 sm:text-sm"
                            >
                                {auth.user
                                    ? 'Ir al panel clínico'
                                    : 'Ingresar al consultorio'}
                                <ArrowRight className="size-4" />
                            </Link>
                        </div>
                    </div>
                </header>

                {/* Hero editorial a juego con la pantalla de Login */}
                <section className="relative overflow-hidden border-b border-[#DCE6EA] bg-gradient-to-b from-white via-[#F6FAFB] to-[#EEF6F8] dark:border-[#1E2E3B] dark:from-[#0E1922] dark:to-[#0B141C]">
                    <div className="mx-auto grid max-w-7xl items-center gap-10 px-5 py-12 sm:px-8 lg:grid-cols-12 lg:py-16">
                        <div className="space-y-6 lg:col-span-6">
                            <div className="inline-flex items-center gap-2 rounded-full border border-[#0E8FA3]/25 bg-[#E6F5F7] px-3.5 py-1.5 text-xs font-semibold text-[#0A6577] dark:bg-cyan-950/50 dark:text-cyan-200">
                                <Sparkles className="size-3.5 text-[#0E8FA3]" />
                                Edición Clínica Completa · Homologada HCE
                                Internacional
                            </div>

                            <h1 className="text-4xl leading-[1.12] font-bold tracking-tight sm:text-5xl">
                                Tu consultorio dental{' '}
                                <span className="text-[#0E8FA3]">
                                    100% digital
                                </span>{' '}
                                con estándar clínico global.
                            </h1>

                            <p className="max-w-xl text-base leading-relaxed text-[#5F6F7C] dark:text-[#96AAB8]">
                                Historia Clínica Electrónica interoperable (HL7
                                FHIR R4), odontograma anatómico, mediciones
                                radiográficas en milímetros, confirmación de
                                citas por WhatsApp en 1 clic, laboratorio,
                                bioseguridad y facturación SUNAT UBL 2.1 con
                                ticketera térmica.
                            </p>

                            <div className="flex flex-wrap items-center gap-3 pt-1">
                                <Link
                                    href={auth.user ? dashboard() : login()}
                                    className="inline-flex h-12 items-center gap-2 rounded-xl bg-[#0E8FA3] px-6 text-sm font-semibold text-white shadow-md transition hover:bg-[#0A6577]"
                                >
                                    {auth.user
                                        ? 'Abrir panel clínico'
                                        : 'Iniciar sesión en el consultorio'}
                                    <ArrowRight className="size-4" />
                                </Link>
                                {onlineBookingUrl && (
                                    <Link
                                        href={onlineBookingUrl}
                                        className="inline-flex h-12 items-center gap-2 rounded-xl border border-[#0E8FA3]/35 bg-[#E6F5F7] px-5 text-sm font-semibold text-[#0A6577] shadow-xs transition hover:bg-[#D5EEF2]"
                                    >
                                        <CalendarCheck2 className="size-4 text-[#0E8FA3]" />
                                        Reservar cita online
                                    </Link>
                                )}
                                <a
                                    href={whatsappHref}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex h-12 items-center gap-2 rounded-xl border border-[#DCE6EA] bg-white px-5 text-sm font-semibold text-[#1F2D3A] shadow-xs transition hover:bg-[#F6FAFB] dark:border-[#253746] dark:bg-[#15222D] dark:text-white"
                                >
                                    <MessageCircle className="size-4 text-[#2E9E6B]" />
                                    Contacto directo WhatsApp
                                </a>
                            </div>

                            <div className="grid max-w-lg grid-cols-3 gap-4 border-t border-[#DCE6EA] pt-5 dark:border-[#1E2E3B]">
                                <div>
                                    <div className="text-lg font-bold text-[#0A6577] dark:text-[#5EEAD4]">
                                        &lt; 5 min
                                    </div>
                                    <div className="text-xs text-[#5F6F7C] dark:text-[#96AAB8]">
                                        Registro de atención
                                    </div>
                                </div>
                                <div>
                                    <div className="text-lg font-bold text-[#0A6577] dark:text-[#5EEAD4]">
                                        80 / 58mm
                                    </div>
                                    <div className="text-xs text-[#5F6F7C] dark:text-[#96AAB8]">
                                        Ticketera y cierre caja
                                    </div>
                                </div>
                                <div>
                                    <div className="text-lg font-bold text-[#0A6577] dark:text-[#5EEAD4]">
                                        Offline PWA
                                    </div>
                                    <div className="text-xs text-[#5F6F7C] dark:text-[#96AAB8]">
                                        Sincronización segura
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Tarjeta fotográfica editorial sincronizada con el Login */}
                        <div className="lg:col-span-6">
                            <div className="relative overflow-hidden rounded-3xl border border-[#0E8FA3]/20 bg-[#062E39] shadow-2xl">
                                <img
                                    src="/images/login-dental-hero.jpg"
                                    alt="Suite odontológica moderna DentaFlow"
                                    className="h-[440px] w-full object-cover object-center opacity-85 sm:h-[460px]"
                                />
                                <div className="absolute inset-0 bg-gradient-to-t from-[#041F27] via-[#062E39]/35 to-transparent" />

                                <div className="absolute top-4 right-4 left-4 flex flex-wrap items-center justify-between gap-2">
                                    <span className="inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-black/35 px-3 py-1 text-[11px] font-semibold text-white backdrop-blur-md">
                                        <Award className="size-3.5 text-[#5EEAD4]" />
                                        HL7 FHIR R4 · ISO 13606
                                    </span>
                                    <span className="inline-flex items-center gap-1.5 rounded-full bg-[#2E9E6B]/90 px-3 py-1 text-[11px] font-semibold text-white backdrop-blur-md">
                                        <CheckCircle2 className="size-3.5" />
                                        Listo para consultorio único
                                    </span>
                                </div>

                                <div className="absolute right-4 bottom-4 left-4 grid gap-2.5 sm:grid-cols-2">
                                    <div className="rounded-2xl border border-white/15 bg-white/10 p-3.5 text-white backdrop-blur-md">
                                        <div className="flex items-center gap-2 text-xs font-bold">
                                            <FileSignature className="size-4 text-[#5EEAD4]" />
                                            Consentimientos & Evoluciones
                                        </div>
                                        <p className="mt-1 text-[11px] text-cyan-50/85">
                                            Firma táctil en pantalla y sellado
                                            criptográfico SHA-256.
                                        </p>
                                    </div>
                                    <div className="rounded-2xl border border-white/15 bg-white/10 p-3.5 text-white backdrop-blur-md">
                                        <div className="flex items-center gap-2 text-xs font-bold">
                                            <FileCode2 className="size-4 text-[#5EEAD4]" />
                                            RENIEC / SUNAT en Vivo
                                        </div>
                                        <p className="mt-1 text-[11px] text-cyan-50/85">
                                            Consulta DNI/RUC vía apiperu.dev y
                                            XML UBL 2.1 con QR.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Franja de estándares internacionales */}
                <section
                    id="estandares"
                    className="border-b border-[#DCE6EA] bg-white py-5 dark:border-[#1E2E3B] dark:bg-[#0E1922]"
                >
                    <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 sm:px-8">
                        <span className="text-xs font-bold tracking-wider text-[#5F6F7C] uppercase dark:text-[#96AAB8]">
                            Homologación clínica y tributaria:
                        </span>
                        <div className="flex flex-wrap items-center gap-2">
                            {STANDARDS.map((std) => (
                                <span
                                    key={std}
                                    className="rounded-full border border-[#DCE6EA] bg-[#F6FAFB] px-3 py-1 font-mono text-xs font-semibold text-[#0A6577] dark:border-[#253746] dark:bg-[#15222D] dark:text-[#7BE0C8]"
                                >
                                    {std}
                                </span>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Módulos del sistema */}
                <section
                    id="modulos"
                    className="mx-auto max-w-7xl px-5 py-14 sm:px-8"
                >
                    <div className="mx-auto mb-10 max-w-2xl text-center">
                        <span className="text-xs font-bold tracking-wider text-[#0E8FA3] uppercase">
                            Arquitectura Clínica Integral
                        </span>
                        <h2 className="mt-2 text-3xl font-bold tracking-tight">
                            Diseñado por odontólogos y especialistas en
                            historias clínicas
                        </h2>
                        <p className="mt-2 text-sm text-[#5F6F7C] dark:text-[#96AAB8]">
                            Cada herramienta responde al flujo real entre sillón
                            dental, recepción, laboratorio, esterilización y
                            caja.
                        </p>
                    </div>

                    <div className="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                        {FEATURE_MODULES.map((mod) => {
                            const Icon = mod.icon;
                            return (
                                <div
                                    key={mod.title}
                                    className="group rounded-2xl border border-[#DCE6EA] bg-white p-6 shadow-xs transition hover:-translate-y-0.5 hover:border-[#0E8FA3]/50 hover:shadow-md dark:border-[#253746] dark:bg-[#15222D]"
                                >
                                    <div className="mb-4 flex items-center justify-between">
                                        <div
                                            className={`flex size-11 items-center justify-center rounded-xl ${mod.accent}`}
                                        >
                                            <Icon className="size-5" />
                                        </div>
                                        <span className="rounded-full bg-muted px-2.5 py-0.5 text-[11px] font-semibold text-muted-foreground">
                                            {mod.badge}
                                        </span>
                                    </div>
                                    <h3 className="text-base font-bold text-foreground">
                                        {mod.title}
                                    </h3>
                                    <p className="mt-2 text-sm leading-relaxed text-[#5F6F7C] dark:text-[#96AAB8]">
                                        {mod.description}
                                    </p>
                                </div>
                            );
                        })}
                    </div>
                </section>

                {/* Bloque CTA final oscuro haciendo juego con el Login */}
                <section className="mx-auto mb-12 w-full max-w-7xl px-5 sm:px-8">
                    <div className="relative overflow-hidden rounded-3xl bg-[#062E39] p-8 text-white shadow-xl sm:p-10">
                        <div className="relative z-10 flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
                            <div className="max-w-2xl space-y-2">
                                <div className="inline-flex items-center gap-1.5 text-xs font-semibold text-[#5EEAD4] uppercase">
                                    <FlaskConical className="size-4" />
                                    Listo para operar en red local o web
                                </div>
                                <h2 className="text-2xl font-bold tracking-tight sm:text-3xl">
                                    Respaldo automático, auditoría clínica y
                                    control por roles incluidos.
                                </h2>
                                <p className="text-sm text-cyan-100/85">
                                    Incluye copias de seguridad verificadas
                                    (JSON + SQL), búsqueda automática de
                                    pacientes por DNI, reportes térmicos de
                                    cierre de caja y liquidación de honorarios.
                                </p>
                            </div>
                            <div className="flex shrink-0 flex-wrap gap-3">
                                <Link
                                    href={auth.user ? dashboard() : login()}
                                    className="inline-flex h-12 items-center gap-2 rounded-xl bg-[#0E8FA3] px-6 text-sm font-bold text-white shadow-lg transition hover:bg-[#0A6577]"
                                >
                                    {auth.user
                                        ? 'Entrar al sistema'
                                        : 'Ingresar al consultorio'}
                                    <ArrowRight className="size-4" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </section>

                <JmsFooter className="mt-auto" />
            </div>
        </>
    );
}
