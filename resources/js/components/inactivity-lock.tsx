import { router, usePage } from '@inertiajs/react';
import { Lock, LogOut, ShieldCheck } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { logout } from '@/routes';
import type { Auth } from '@/types';

export function InactivityLock() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const lockMinutes = auth.clinic?.auto_lock_minutes ?? 15;
    const [locked, setLocked] = useState(false);

    const triggerLock = useCallback(() => {
        setLocked(true);
    }, []);

    useEffect(() => {
        if (!auth.user) return;

        let timer: ReturnType<typeof setTimeout>;
        const resetTimer = () => {
            if (locked) return;
            clearTimeout(timer);
            timer = setTimeout(triggerLock, lockMinutes * 60 * 1000);
        };

        const events = ['mousedown', 'keydown', 'touchstart', 'scroll'];
        events.forEach((evt) =>
            window.addEventListener(evt, resetTimer, { passive: true }),
        );
        resetTimer();

        const onManualLock = () => setLocked(true);
        window.addEventListener('dentaflow:lock-screen', onManualLock);

        return () => {
            clearTimeout(timer);
            events.forEach((evt) =>
                window.removeEventListener(evt, resetTimer),
            );
            window.removeEventListener('dentaflow:lock-screen', onManualLock);
        };
    }, [auth.user, lockMinutes, locked, triggerLock]);

    if (!locked || !auth.user) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/90 p-4 backdrop-blur-md">
            <div className="w-full max-w-md rounded-2xl border bg-card p-6 text-center shadow-2xl">
                <div className="mx-auto mb-4 flex size-14 items-center justify-center rounded-full bg-[#E6F5F7] text-[#0E8FA3]">
                    <Lock className="size-7" />
                </div>
                <h2 className="text-xl font-bold text-foreground">
                    Sesión protegida por privacidad
                </h2>
                <p className="mt-2 text-sm text-muted-foreground">
                    La pantalla se bloqueó para resguardar los datos clínicos de
                    tus pacientes.
                </p>
                <div className="mt-4 rounded-xl bg-muted/50 px-4 py-3 text-sm">
                    <div className="font-semibold text-foreground">
                        {auth.user.name}
                    </div>
                    <div className="text-xs text-muted-foreground">
                        {auth.user.role_label}
                    </div>
                </div>
                <div className="mt-6 flex flex-col gap-2.5 sm:flex-row">
                    <Button
                        type="button"
                        variant="outline"
                        className="flex-1 gap-2"
                        onClick={() => router.post(logout.url())}
                    >
                        <LogOut className="size-4" />
                        Cerrar sesión
                    </Button>
                    <Button
                        type="button"
                        className="flex-1 gap-2 bg-[#0E8FA3] text-white hover:bg-[#0A6577]"
                        onClick={() => setLocked(false)}
                    >
                        <ShieldCheck className="size-4" />
                        Continuar atención
                    </Button>
                </div>
            </div>
        </div>
    );
}
