import { router } from '@inertiajs/react';
import {
    Cloud,
    CloudOff,
    Download,
    RefreshCw,
    AlertTriangle,
} from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    getOfflineSnapshot,
    listOfflineMutations,
    type OfflineConflict,
    type OfflineMutation,
    type OfflineSnapshot,
    removeOfflineMutation,
    saveOfflineSnapshot,
} from '@/lib/offline-store';

type BeforeInstallPromptEvent = Event & {
    prompt: () => Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
};

function csrfToken(): string {
    const el = document.querySelector('meta[name="csrf-token"]');
    return el?.getAttribute('content') ?? '';
}

export function OfflineSyncManager() {
    const [online, setOnline] = useState<boolean>(() =>
        typeof navigator !== 'undefined' ? navigator.onLine : true,
    );
    const [pending, setPending] = useState<OfflineMutation[]>([]);
    const [syncing, setSyncing] = useState(false);
    const [lastSyncedAt, setLastSyncedAt] = useState<string | null>(null);
    const [conflicts, setConflicts] = useState<OfflineConflict[]>([]);
    const [installPrompt, setInstallPrompt] =
        useState<BeforeInstallPromptEvent | null>(null);
    const [swWaiting, setSwWaiting] = useState<ServiceWorker | null>(null);

    const refreshPending = useCallback(async () => {
        const list = await listOfflineMutations();
        setPending(list);
    }, []);

    const bootstrapSnapshot = useCallback(async () => {
        if (!navigator.onLine) {
            const cached = await getOfflineSnapshot();
            if (cached?.synced_at) setLastSyncedAt(cached.synced_at);
            return;
        }

        try {
            const res = await fetch('/offline-sync/bootstrap', {
                headers: { Accept: 'application/json' },
            });
            if (res.ok) {
                const data = (await res.json()) as OfflineSnapshot;
                await saveOfflineSnapshot(data);
                setLastSyncedAt(data.synced_at);
            }
        } catch {
            // Silent fallback when offline
        }
    }, []);

    const syncPendingMutations = useCallback(
        async (overrideMutations?: OfflineMutation[]) => {
            if (!navigator.onLine) return;
            const queue = overrideMutations ?? (await listOfflineMutations());
            if (queue.length === 0) {
                await bootstrapSnapshot();
                return;
            }

            setSyncing(true);
            try {
                const res = await fetch('/offline-sync/push', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: JSON.stringify({
                        mutations: queue.map((m) => ({
                            id: m.id,
                            type: m.type,
                            force: m.force ?? false,
                            payload: m.payload,
                        })),
                    }),
                });

                if (!res.ok) {
                    toast.error(
                        'No se pudieron sincronizar los cambios pendientes.',
                    );
                    return;
                }

                const data = (await res.json()) as {
                    applied: string[];
                    conflicts: OfflineConflict[];
                    synced_at: string;
                };

                for (const id of data.applied) {
                    await removeOfflineMutation(id);
                }

                await refreshPending();
                await bootstrapSnapshot();

                if (data.conflicts.length > 0) {
                    setConflicts(data.conflicts);
                } else if (data.applied.length > 0) {
                    toast.success(
                        `${data.applied.length} cambio(s) sin conexión sincronizados.`,
                    );
                    router.reload({
                        only: [
                            'patients',
                            'appointments',
                            'patient',
                            'todayAppointments',
                        ],
                    });
                }
            } finally {
                setSyncing(false);
            }
        },
        [bootstrapSnapshot, refreshPending],
    );

    useEffect(() => {
        void refreshPending();
        void bootstrapSnapshot();

        const onOnline = () => {
            setOnline(true);
            toast.info('Conexión restablecida. Sincronizando cambios...');
            void syncPendingMutations();
        };
        const onOffline = () => {
            setOnline(false);
            toast.warning(
                'Sin conexión: los cambios se guardarán localmente en el dispositivo.',
            );
        };
        const onQueueUpdate = () => {
            void refreshPending();
        };
        const onBeforeInstall = (e: Event) => {
            e.preventDefault();
            setInstallPrompt(e as BeforeInstallPromptEvent);
        };
        const onSwUpdate = (e: Event) => {
            const detail = (e as CustomEvent<{ worker: ServiceWorker }>).detail;
            if (detail?.worker) {
                setSwWaiting(detail.worker);
            }
        };

        window.addEventListener('online', onOnline);
        window.addEventListener('offline', onOffline);
        window.addEventListener(
            'dentaflow:offline-queue-updated',
            onQueueUpdate,
        );
        window.addEventListener('beforeinstallprompt', onBeforeInstall);
        window.addEventListener('dentaflow:sw-update-available', onSwUpdate);

        return () => {
            window.removeEventListener('online', onOnline);
            window.removeEventListener('offline', onOffline);
            window.removeEventListener(
                'dentaflow:offline-queue-updated',
                onQueueUpdate,
            );
            window.removeEventListener('beforeinstallprompt', onBeforeInstall);
            window.removeEventListener(
                'dentaflow:sw-update-available',
                onSwUpdate,
            );
        };
    }, [bootstrapSnapshot, refreshPending, syncPendingMutations]);

    const handleResolveConflict = async (
        conflict: OfflineConflict,
        strategy: 'server' | 'local',
    ) => {
        if (strategy === 'server') {
            await removeOfflineMutation(conflict.id);
            setConflicts((prev) => prev.filter((c) => c.id !== conflict.id));
            await refreshPending();
            toast.info('Se conservó la versión del servidor.');
            router.reload();
            return;
        }

        const item = pending.find((m) => m.id === conflict.id);
        if (!item) return;

        await syncPendingMutations([{ ...item, force: true }]);
        setConflicts((prev) => prev.filter((c) => c.id !== conflict.id));
    };

    const handleInstallPwa = async () => {
        if (!installPrompt) return;
        await installPrompt.prompt();
        const choice = await installPrompt.userChoice;
        if (choice.outcome === 'accepted') {
            setInstallPrompt(null);
        }
    };

    const handleUpdateApp = () => {
        if (!swWaiting) return;
        swWaiting.postMessage({ type: 'SKIP_WAITING' });
        window.location.reload();
    };

    return (
        <>
            <div className="flex items-center gap-2">
                {swWaiting && (
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={handleUpdateApp}
                        className="h-8 border-[#0E8FA3] text-xs text-[#0E8FA3]"
                    >
                        Nueva versión disponible · Actualizar
                    </Button>
                )}

                {installPrompt && (
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={() => void handleInstallPwa()}
                        className="hidden h-8 gap-1.5 border-[#0E8FA3]/40 text-xs text-[#0A6577] sm:inline-flex dark:text-[#7BE0C8]"
                    >
                        <Download className="size-3.5" />
                        Instalar App
                    </Button>
                )}

                <button
                    type="button"
                    onClick={() => void syncPendingMutations()}
                    title={
                        lastSyncedAt
                            ? `Última copia offline: ${new Date(lastSyncedAt).toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' })}`
                            : 'Sincronizar caché offline'
                    }
                    className={`inline-flex h-8 items-center gap-1.5 rounded-full border px-3 text-xs font-medium transition-colors ${
                        !online
                            ? 'border-[#F0A92E]/60 bg-[#FEF3C7] text-[#92400E]'
                            : pending.length > 0
                              ? 'border-[#2F7FD1]/50 bg-[#DBEAFE] text-[#1E40AF]'
                              : 'border-[#DCE6EA] bg-card text-muted-foreground hover:text-foreground'
                    }`}
                >
                    {syncing ? (
                        <RefreshCw className="size-3.5 animate-spin" />
                    ) : online ? (
                        <Cloud className="size-3.5 text-[#2E9E6B]" />
                    ) : (
                        <CloudOff className="size-3.5 text-[#D97706]" />
                    )}
                    <span className="hidden sm:inline">
                        {!online
                            ? `Sin conexión${pending.length > 0 ? ` (${pending.length})` : ''}`
                            : pending.length > 0
                              ? `${pending.length} pendiente(s) · Sincronizar`
                              : 'En línea'}
                    </span>
                    {pending.length > 0 && (
                        <span className="sm:hidden">{pending.length}</span>
                    )}
                </button>
            </div>

            <Dialog open={conflicts.length > 0}>
                <DialogContent className="max-w-xl">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-[#D64545]">
                            <AlertTriangle className="size-5" />
                            Conflicto de sincronización detectado
                        </DialogTitle>
                        <DialogDescription>
                            Otro dispositivo modificó el mismo registro mientras
                            estabas sin conexión. Elige qué versión conservar.
                        </DialogDescription>
                    </DialogHeader>

                    {conflicts.slice(0, 1).map((conflict) => (
                        <div key={conflict.id} className="space-y-4">
                            <div className="rounded-lg bg-muted/50 p-3 text-sm font-semibold">
                                {conflict.label}
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="rounded-xl border p-3 text-xs">
                                    <div className="mb-1.5 font-bold text-[#2F7FD1]">
                                        Versión en el servidor (v
                                        {conflict.server_version})
                                    </div>
                                    {conflict.server_findings && (
                                        <p className="text-muted-foreground">
                                            {conflict.server_findings.length}{' '}
                                            hallazgo(s) registrados
                                        </p>
                                    )}
                                    {conflict.server_status && (
                                        <p className="text-muted-foreground">
                                            Estado actual:{' '}
                                            <strong>
                                                {conflict.server_status}
                                            </strong>
                                        </p>
                                    )}
                                </div>
                                <div className="rounded-xl border border-[#F0A92E] p-3 text-xs">
                                    <div className="mb-1.5 font-bold text-[#B45309]">
                                        Tu cambio sin conexión
                                    </div>
                                    {conflict.local_findings && (
                                        <p className="text-muted-foreground">
                                            {conflict.local_findings.length}{' '}
                                            hallazgo(s) locales
                                        </p>
                                    )}
                                    {conflict.local_status && (
                                        <p className="text-muted-foreground">
                                            Estado propuesto:{' '}
                                            <strong>
                                                {conflict.local_status}
                                            </strong>
                                        </p>
                                    )}
                                </div>
                            </div>

                            <DialogFooter className="gap-2 sm:justify-between">
                                <Button
                                    variant="outline"
                                    onClick={() =>
                                        void handleResolveConflict(
                                            conflict,
                                            'server',
                                        )
                                    }
                                >
                                    Conservar servidor
                                </Button>
                                <Button
                                    className="bg-[#0E8FA3] hover:bg-[#0A6577]"
                                    onClick={() =>
                                        void handleResolveConflict(
                                            conflict,
                                            'local',
                                        )
                                    }
                                >
                                    Aplicar mis cambios locales
                                </Button>
                            </DialogFooter>
                        </div>
                    ))}
                </DialogContent>
            </Dialog>
        </>
    );
}
