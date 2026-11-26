import { Head } from '@inertiajs/react';
import {
    Award,
    BookOpen,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    FileDown,
    Keyboard,
    Lightbulb,
    Maximize2,
    Search,
    ShieldCheck,
    Sparkles,
    Users,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

type Screenshot = {
    file: string;
    caption: string;
    url: string;
};

type Chapter = {
    id: string;
    number: string;
    title: string;
    category: string;
    roles: string[];
    summary: string;
    tip: string;
    steps: string[];
    screenshots: Screenshot[];
};

type RoleRow = {
    module: string;
    admin: string;
    dentist: string;
    assistant: string;
    accountant: string;
};

type Props = {
    chapters: Chapter[];
    roleMatrix: RoleRow[];
    clinicName: string;
    manualVersion: string;
    pdfUrl: string;
};

export default function UserManual({
    chapters,
    roleMatrix,
    clinicName,
    manualVersion,
    pdfUrl,
}: Props) {
    const [search, setSearch] = useState('');
    const [selectedCategory, setSelectedCategory] = useState<string>('Todas');
    const [activeChapterId, setActiveChapterId] = useState<string>(
        chapters[0]?.id ?? 'acceso-seguro',
    );
    const [lightboxIndex, setLightboxIndex] = useState<number | null>(null);

    const categories = useMemo(() => {
        const unique = Array.from(new Set(chapters.map((c) => c.category)));
        return ['Todas', ...unique];
    }, [chapters]);

    const filteredChapters = useMemo(() => {
        const q = search.trim().toLowerCase();
        return chapters.filter((chapter) => {
            const matchesCategory =
                selectedCategory === 'Todas' ||
                chapter.category === selectedCategory;
            if (!matchesCategory) return false;
            if (!q) return true;
            const textBlob = [
                chapter.number,
                chapter.title,
                chapter.category,
                chapter.summary,
                chapter.tip,
                ...chapter.roles,
                ...chapter.steps,
                ...chapter.screenshots.map((s) => s.caption),
            ]
                .join(' ')
                .toLowerCase();
            return textBlob.includes(q);
        });
    }, [chapters, search, selectedCategory]);

    const allScreenshots = useMemo(
        () =>
            chapters.flatMap((chapter) =>
                chapter.screenshots.map((shot) => ({
                    ...shot,
                    chapterTitle: `${chapter.number}. ${chapter.title}`,
                })),
            ),
        [chapters],
    );

    const activeLightbox =
        lightboxIndex !== null ? allScreenshots[lightboxIndex] : null;

    const openLightboxByFile = (file: string) => {
        const idx = allScreenshots.findIndex((s) => s.file === file);
        if (idx >= 0) setLightboxIndex(idx);
    };

    const totalScreenshots = allScreenshots.length;

    return (
        <>
            <Head title="Manual Oficial del Sistema" />

            <div className="mx-auto max-w-7xl space-y-6 p-4 md:p-6">
                {/* Portada editorial del manual */}
                <section className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#062E39] via-[#094352] to-[#0E8FA3] p-6 text-white shadow-lg sm:p-8">
                    <div className="flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
                        <div className="max-w-3xl space-y-3">
                            <div className="flex flex-wrap items-center gap-2">
                                <span className="inline-flex items-center gap-1.5 rounded-full border border-white/25 bg-white/10 px-3 py-1 text-xs font-semibold backdrop-blur-md">
                                    <BookOpen className="size-3.5 text-[#5EEAD4]" />
                                    Manual Oficial de Operación
                                </span>
                                <span className="inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-black/20 px-3 py-1 text-xs font-medium text-cyan-100">
                                    <Award className="size-3.5 text-[#5EEAD4]" />
                                    {manualVersion}
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">
                                Guía Clínica y Administrativa de DentaFlow ·{' '}
                                {clinicName}
                            </h1>

                            <p className="text-sm leading-relaxed text-cyan-50/90">
                                Documentación paso a paso con{' '}
                                <strong>
                                    {totalScreenshots} capturas reales
                                </strong>{' '}
                                de la plataforma: historia clínica homologada
                                HL7 FHIR R4, odontograma, visor radiográfico
                                medible con Rayos X IA, Copiloto SOAP por voz,
                                Resumen Pre-Consulta IA, Contralor IA, CRM
                                WhatsApp, Reserva Online 24/7, Reportes IA, caja
                                térmica y SUNAT UBL 2.1.
                            </p>

                            <div className="flex flex-wrap gap-3 pt-1 text-xs text-cyan-100/90">
                                <span className="inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-3 py-1.5">
                                    <CheckCircle2 className="size-3.5 text-[#5EEAD4]" />
                                    {chapters.length} capítulos ilustrados
                                </span>
                                <span className="inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-3 py-1.5">
                                    <ShieldCheck className="size-3.5 text-[#5EEAD4]" />
                                    4 roles con matriz de permisos
                                </span>
                                <span className="inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-3 py-1.5">
                                    <Sparkles className="size-3.5 text-[#5EEAD4]" />
                                    Capturas ampliables en pantalla
                                </span>
                            </div>
                        </div>

                        <div className="flex shrink-0 flex-col gap-2.5 sm:flex-row lg:flex-col">
                            <a
                                href={pdfUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-white px-5 text-sm font-bold text-[#062E39] shadow-md transition hover:bg-cyan-50"
                            >
                                <FileDown className="size-4 text-[#0E8FA3]" />
                                Descargar Manual en PDF
                            </a>
                            <a
                                href="#matriz-roles"
                                className="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-white/25 bg-white/10 px-4 text-xs font-semibold text-white transition hover:bg-white/20"
                            >
                                <Users className="size-4" />
                                Ver permisos por rol
                            </a>
                        </div>
                    </div>
                </section>

                {/* Buscador y filtros por categoría */}
                <div className="space-y-3 rounded-2xl border bg-card p-4 shadow-xs">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div className="relative flex-1">
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                type="search"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Buscar módulo, función IA o palabra clave (ej: Contralor IA, CRM, SOAP voz, Rayos X IA, Reserva 24/7, SUNAT, DNI)..."
                                className="pl-9"
                            />
                        </div>
                        <div className="text-xs font-medium text-muted-foreground">
                            Mostrando {filteredChapters.length} de{' '}
                            {chapters.length} capítulos
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-1.5">
                        {categories.map((cat) => (
                            <button
                                key={cat}
                                type="button"
                                onClick={() => setSelectedCategory(cat)}
                                className={`rounded-full px-3 py-1 text-xs font-semibold transition ${
                                    selectedCategory === cat
                                        ? 'bg-[#0E8FA3] text-white'
                                        : 'border bg-muted/40 text-muted-foreground hover:bg-muted hover:text-foreground'
                                }`}
                            >
                                {cat}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Cuerpo principal: Índice lateral sticky + Capítulos detallados */}
                <div className="grid gap-6 lg:grid-cols-12">
                    {/* Índice lateral fijo en desktop */}
                    <aside className="lg:col-span-3">
                        <div className="sticky top-20 space-y-4 rounded-2xl border bg-card p-4 shadow-xs">
                            <div className="border-b pb-2 text-xs font-bold tracking-wider text-[#0A6577] uppercase">
                                Índice del manual
                            </div>
                            <nav className="max-h-[60vh] space-y-1 overflow-y-auto pr-1">
                                {chapters.map((ch) => (
                                    <a
                                        key={ch.id}
                                        href={`#${ch.id}`}
                                        onClick={() =>
                                            setActiveChapterId(ch.id)
                                        }
                                        className={`flex items-start gap-2.5 rounded-xl px-2.5 py-2 text-xs transition ${
                                            activeChapterId === ch.id
                                                ? 'bg-[#E6F5F7] font-semibold text-[#062E39] dark:bg-cyan-950/50 dark:text-cyan-100'
                                                : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground'
                                        }`}
                                    >
                                        <span className="mt-0.5 inline-flex size-5 shrink-0 items-center justify-center rounded-md bg-[#0E8FA3]/15 font-mono text-[10px] font-bold text-[#0A6577] dark:text-cyan-300">
                                            {ch.number}
                                        </span>
                                        <span className="leading-snug">
                                            {ch.title}
                                        </span>
                                    </a>
                                ))}
                            </nav>

                            <div className="rounded-xl border border-[#DCE6EA] bg-[#F6FAFB] p-3 text-xs dark:border-border dark:bg-muted/40">
                                <div className="mb-1.5 flex items-center gap-1.5 font-bold text-[#062E39] dark:text-foreground">
                                    <Keyboard className="size-3.5 text-[#0E8FA3]" />
                                    Atajos rápidos
                                </div>
                                <ul className="space-y-1 text-[11px] text-muted-foreground">
                                    <li>
                                        <kbd className="rounded border bg-background px-1 font-mono">
                                            ⌘K
                                        </kbd>{' '}
                                        /{' '}
                                        <kbd className="rounded border bg-background px-1 font-mono">
                                            Ctrl+K
                                        </kbd>{' '}
                                        Buscador de pacientes
                                    </li>
                                    <li>
                                        <kbd className="rounded border bg-background px-1 font-mono">
                                            Esc
                                        </kbd>{' '}
                                        Cerrar modales o visor
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </aside>

                    {/* Contenido detallado por capítulos */}
                    <div className="space-y-6 lg:col-span-9">
                        {filteredChapters.length === 0 && (
                            <div className="rounded-2xl border bg-card p-10 text-center text-sm text-muted-foreground">
                                No se encontraron capítulos que coincidan con “
                                {search}”.
                            </div>
                        )}

                        {filteredChapters.map((chapter) => (
                            <article
                                key={chapter.id}
                                id={chapter.id}
                                className="scroll-mt-20 space-y-5 rounded-2xl border bg-card p-6 shadow-xs"
                            >
                                <div className="flex flex-wrap items-start justify-between gap-3 border-b pb-4">
                                    <div className="space-y-1.5">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="inline-flex items-center rounded-lg bg-[#0E8FA3] px-2.5 py-0.5 font-mono text-xs font-bold text-white">
                                                Capítulo {chapter.number}
                                            </span>
                                            <Badge variant="outline">
                                                {chapter.category}
                                            </Badge>
                                        </div>
                                        <h2 className="text-xl font-bold text-foreground">
                                            {chapter.title}
                                        </h2>
                                    </div>
                                    <div className="flex flex-wrap gap-1.5">
                                        {chapter.roles.map((role) => (
                                            <Badge
                                                key={role}
                                                className="bg-[#E6F5F7] text-[#0A6577] hover:bg-[#E6F5F7] dark:bg-cyan-950/60 dark:text-cyan-200"
                                            >
                                                {role}
                                            </Badge>
                                        ))}
                                    </div>
                                </div>

                                <p className="text-sm leading-relaxed text-muted-foreground">
                                    {chapter.summary}
                                </p>

                                {/* Pasos operativos */}
                                <div className="space-y-2.5">
                                    <h3 className="text-xs font-bold tracking-wider text-foreground uppercase">
                                        Procedimiento paso a paso
                                    </h3>
                                    <ol className="space-y-2">
                                        {chapter.steps.map((stepText, idx) => (
                                            <li
                                                key={idx}
                                                className="flex items-start gap-3 rounded-xl border bg-muted/25 px-3.5 py-2.5 text-sm"
                                            >
                                                <span className="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-[#0E8FA3]/15 font-mono text-xs font-bold text-[#0A6577] dark:text-cyan-300">
                                                    {idx + 1}
                                                </span>
                                                <span className="leading-relaxed text-foreground">
                                                    {stepText}
                                                </span>
                                            </li>
                                        ))}
                                    </ol>
                                </div>

                                {/* Consejo clínico / seguridad */}
                                <div className="flex items-start gap-3 rounded-xl border border-[#0E8FA3]/30 bg-[#E6F5F7]/65 p-3.5 text-sm text-[#062E39] dark:bg-cyan-950/35 dark:text-cyan-100">
                                    <Lightbulb className="mt-0.5 size-4 shrink-0 text-[#0E8FA3]" />
                                    <div>
                                        <strong className="font-semibold">
                                            Recomendación clave:{' '}
                                        </strong>
                                        <span>{chapter.tip}</span>
                                    </div>
                                </div>

                                {/* Capturas reales del módulo */}
                                <div
                                    className={`grid gap-4 ${
                                        chapter.screenshots.length > 1
                                            ? 'md:grid-cols-2'
                                            : 'grid-cols-1'
                                    }`}
                                >
                                    {chapter.screenshots.map((shot) => (
                                        <figure
                                            key={shot.file}
                                            className="group overflow-hidden rounded-xl border bg-background shadow-xs"
                                        >
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    openLightboxByFile(
                                                        shot.file,
                                                    )
                                                }
                                                className="relative block w-full overflow-hidden bg-slate-100 text-left focus:outline-none"
                                            >
                                                <img
                                                    src={shot.url}
                                                    alt={shot.caption}
                                                    loading="lazy"
                                                    className="aspect-video w-full object-cover object-top transition duration-300 group-hover:scale-[1.015]"
                                                />
                                                <span className="absolute right-2.5 bottom-2.5 inline-flex items-center gap-1 rounded-lg bg-black/70 px-2.5 py-1 text-[11px] font-semibold text-white opacity-90 backdrop-blur-sm transition group-hover:bg-[#0E8FA3]">
                                                    <Maximize2 className="size-3" />
                                                    Ampliar captura
                                                </span>
                                            </button>
                                            <figcaption className="border-t px-3 py-2 text-xs text-muted-foreground">
                                                {shot.caption}
                                            </figcaption>
                                        </figure>
                                    ))}
                                </div>
                            </article>
                        ))}

                        {/* Matriz oficial de permisos */}
                        <section
                            id="matriz-roles"
                            className="scroll-mt-20 space-y-4 rounded-2xl border bg-card p-6 shadow-xs"
                        >
                            <div className="flex flex-wrap items-center justify-between gap-2 border-b pb-3">
                                <div className="flex items-center gap-2">
                                    <ShieldCheck className="size-5 text-[#0E8FA3]" />
                                    <div>
                                        <h2 className="text-lg font-bold">
                                            Matriz Oficial de Permisos por Rol
                                        </h2>
                                        <p className="text-xs text-muted-foreground">
                                            Alcance operativo y clínico asignado
                                            a cada perfil dentro del consultorio
                                            único.
                                        </p>
                                    </div>
                                </div>
                                <a
                                    href={pdfUrl}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <Button
                                        size="sm"
                                        className="gap-1.5 bg-[#0E8FA3] text-white hover:bg-[#0A6577]"
                                    >
                                        <FileDown className="size-4" />
                                        Exportar Manual PDF
                                    </Button>
                                </a>
                            </div>

                            <div className="overflow-x-auto rounded-xl border">
                                <table className="w-full text-left text-xs">
                                    <thead className="border-b bg-muted/50 text-muted-foreground">
                                        <tr>
                                            <th className="px-3.5 py-2.5 font-semibold">
                                                Módulo / Funcionalidad
                                            </th>
                                            <th className="px-3.5 py-2.5 font-semibold text-[#0A6577]">
                                                Administrador
                                            </th>
                                            <th className="px-3.5 py-2.5 font-semibold">
                                                Odontólogo
                                            </th>
                                            <th className="px-3.5 py-2.5 font-semibold">
                                                Asistente / Recepción
                                            </th>
                                            <th className="px-3.5 py-2.5 font-semibold">
                                                Contador / Caja
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {roleMatrix.map((row) => (
                                            <tr
                                                key={row.module}
                                                className="hover:bg-muted/20"
                                            >
                                                <td className="px-3.5 py-2.5 font-semibold text-foreground">
                                                    {row.module}
                                                </td>
                                                <td className="px-3.5 py-2.5 font-medium text-[#0A6577] dark:text-cyan-300">
                                                    {row.admin}
                                                </td>
                                                <td className="px-3.5 py-2.5 text-muted-foreground">
                                                    {row.dentist}
                                                </td>
                                                <td className="px-3.5 py-2.5 text-muted-foreground">
                                                    {row.assistant}
                                                </td>
                                                <td className="px-3.5 py-2.5 text-muted-foreground">
                                                    {row.accountant}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                </div>
            </div>

            {/* Modal Lightbox para ampliar capturas */}
            <Dialog
                open={activeLightbox !== null}
                onOpenChange={(open) => !open && setLightboxIndex(null)}
            >
                <DialogContent className="max-h-[95vh] max-w-[95vw] overflow-y-auto sm:max-w-5xl">
                    <DialogHeader>
                        <DialogTitle className="text-base">
                            {activeLightbox?.chapterTitle}
                        </DialogTitle>
                    </DialogHeader>
                    {activeLightbox && (
                        <div className="space-y-3">
                            <div className="overflow-hidden rounded-xl border bg-slate-950">
                                <img
                                    src={activeLightbox.url}
                                    alt={activeLightbox.caption}
                                    className="max-h-[72vh] w-full object-contain"
                                />
                            </div>
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <p className="text-xs text-muted-foreground">
                                    {activeLightbox.caption}
                                </p>
                                <div className="flex items-center gap-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        disabled={
                                            lightboxIndex === null ||
                                            lightboxIndex <= 0
                                        }
                                        onClick={() =>
                                            setLightboxIndex((i) =>
                                                i !== null && i > 0 ? i - 1 : i,
                                            )
                                        }
                                    >
                                        <ChevronLeft className="size-4" />
                                        Anterior
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        disabled={
                                            lightboxIndex === null ||
                                            lightboxIndex >=
                                                allScreenshots.length - 1
                                        }
                                        onClick={() =>
                                            setLightboxIndex((i) =>
                                                i !== null &&
                                                i < allScreenshots.length - 1
                                                    ? i + 1
                                                    : i,
                                            )
                                        }
                                    >
                                        Siguiente
                                        <ChevronRight className="size-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}
