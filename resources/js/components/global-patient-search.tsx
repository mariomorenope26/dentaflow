import { router } from '@inertiajs/react';
import { AlertTriangle, Search, UserRound } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Input } from '@/components/ui/input';
import { searchOfflinePatients } from '@/lib/offline-store';

type SearchResult = {
    id: number;
    history_number: string;
    full_name: string;
    document_type: string;
    document_number: string | null;
    phone: string | null;
    has_alerts: boolean;
};

export function GlobalPatientSearch() {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<SearchResult[]>([]);
    const [open, setOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        const onKeyDown = (e: KeyboardEvent) => {
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                inputRef.current?.focus();
            }
        };
        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, []);

    useEffect(() => {
        const onClickOutside = (e: MouseEvent) => {
            if (
                containerRef.current &&
                !containerRef.current.contains(e.target as Node)
            ) {
                setOpen(false);
            }
        };
        document.addEventListener('mousedown', onClickOutside);
        return () => document.removeEventListener('mousedown', onClickOutside);
    }, []);

    useEffect(() => {
        const trimmed = query.trim();
        if (trimmed.length < 2) {
            setResults([]);
            return;
        }

        const timer = setTimeout(async () => {
            setLoading(true);
            try {
                if (navigator.onLine) {
                    const response = await fetch(
                        `/search?q=${encodeURIComponent(trimmed)}`,
                        {
                            headers: { Accept: 'application/json' },
                        },
                    );
                    if (response.ok) {
                        const data = (await response.json()) as {
                            patients: SearchResult[];
                        };
                        setResults(data.patients ?? []);
                        setOpen(true);
                        setLoading(false);
                        return;
                    }
                }

                const offlineHits = await searchOfflinePatients(trimmed);
                setResults(
                    offlineHits.map((p) => ({
                        id: p.id,
                        history_number: p.history_number,
                        full_name: p.full_name,
                        document_type: p.document_type,
                        document_number: p.document_number,
                        phone: p.phone,
                        has_alerts: Boolean(
                            p.medical_history?.allergies ||
                            (p.medical_history?.medical_alerts?.length ?? 0) >
                                0,
                        ),
                    })),
                );
                setOpen(true);
            } catch {
                const offlineHits = await searchOfflinePatients(trimmed);
                setResults(
                    offlineHits.map((p) => ({
                        id: p.id,
                        history_number: p.history_number,
                        full_name: p.full_name,
                        document_type: p.document_type,
                        document_number: p.document_number,
                        phone: p.phone,
                        has_alerts: false,
                    })),
                );
                setOpen(true);
            } finally {
                setLoading(false);
            }
        }, 200);

        return () => clearTimeout(timer);
    }, [query]);

    const goToPatient = (id: number) => {
        setOpen(false);
        setQuery('');
        router.visit(`/patients/${id}`);
    };

    return (
        <div ref={containerRef} className="relative w-full max-w-md">
            <div className="relative flex items-center">
                <Search className="pointer-events-none absolute left-3 size-4 text-muted-foreground" />
                <Input
                    ref={inputRef}
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    onFocus={() => {
                        if (results.length > 0) setOpen(true);
                    }}
                    placeholder="Buscar paciente por nombre, DNI o HC... (⌘K)"
                    className="h-10 rounded-xl bg-muted/40 pr-10 pl-9 text-sm"
                />
                {loading && (
                    <span className="absolute right-3 text-xs text-muted-foreground">
                        ...
                    </span>
                )}
            </div>

            {open && (
                <div className="absolute top-12 right-0 left-0 z-50 overflow-hidden rounded-xl border bg-popover shadow-lg">
                    {results.length === 0 ? (
                        <div className="px-4 py-3 text-sm text-muted-foreground">
                            No se encontraron pacientes para &ldquo;{query}
                            &rdquo;.
                        </div>
                    ) : (
                        <ul className="max-h-72 divide-y overflow-y-auto">
                            {results.map((patient) => (
                                <li key={patient.id}>
                                    <button
                                        type="button"
                                        onClick={() => goToPatient(patient.id)}
                                        className="flex w-full items-center justify-between gap-3 px-4 py-2.5 text-left transition-colors hover:bg-accent"
                                    >
                                        <div className="flex items-center gap-2.5">
                                            <div className="flex size-8 items-center justify-center rounded-full bg-secondary text-secondary-foreground">
                                                <UserRound className="size-4" />
                                            </div>
                                            <div>
                                                <div className="text-sm font-semibold text-foreground">
                                                    {patient.full_name}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {patient.history_number} ·{' '}
                                                    {patient.document_type}{' '}
                                                    {patient.document_number ??
                                                        'S/N'}
                                                    {patient.phone
                                                        ? ` · ${patient.phone}`
                                                        : ''}
                                                </div>
                                            </div>
                                        </div>
                                        {patient.has_alerts && (
                                            <span className="inline-flex items-center gap-1 rounded-full bg-[#FEE2E2] px-2 py-0.5 text-[11px] font-semibold text-[#D64545]">
                                                <AlertTriangle className="size-3" />
                                                Alerta
                                            </span>
                                        )}
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            )}
        </div>
    );
}
