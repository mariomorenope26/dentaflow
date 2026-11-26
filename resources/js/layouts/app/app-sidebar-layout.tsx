import { Link, usePage } from '@inertiajs/react';
import { CalendarDays, LayoutGrid, Plus, Users, Wallet } from 'lucide-react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { InactivityLock } from '@/components/inactivity-lock';
import { JmsFooter } from '@/components/jms-footer';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { dashboard } from '@/routes';
import type { AppLayoutProps, Auth } from '@/types';

type FlashMessages = {
    success?: string | null;
    error?: string | null;
    warning?: string | null;
};

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    const { flash, auth } = usePage<{ flash?: FlashMessages; auth: Auth }>()
        .props;
    const { isCurrentUrl } = useCurrentUrl();

    useFlashToast();

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
        if (flash?.warning) toast.warning(flash.warning);
    }, [flash?.success, flash?.error, flash?.warning]);

    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent
                variant="sidebar"
                className="min-w-0 overflow-x-clip pb-20 md:pb-6"
            >
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                <div className="flex-1">{children}</div>
                <JmsFooter className="mt-6" />
            </AppContent>

            {/* Barra inferior para tablet y móvil */}
            <nav
                aria-label="Navegación rápida móvil"
                className="fixed right-0 bottom-0 left-0 z-40 flex h-16 items-center justify-around border-t bg-card/95 px-2 backdrop-blur md:hidden"
            >
                <Link
                    href={dashboard()}
                    className={`flex flex-col items-center gap-0.5 px-3 py-1 text-[11px] font-medium ${
                        isCurrentUrl(dashboard())
                            ? 'text-[#0E8FA3]'
                            : 'text-muted-foreground'
                    }`}
                >
                    <LayoutGrid className="size-5" />
                    <span>Inicio</span>
                </Link>

                <Link
                    href="/patients"
                    className={`flex flex-col items-center gap-0.5 px-3 py-1 text-[11px] font-medium ${
                        isCurrentUrl('/patients')
                            ? 'text-[#0E8FA3]'
                            : 'text-muted-foreground'
                    }`}
                >
                    <Users className="size-5" />
                    <span>Pacientes</span>
                </Link>

                <Link
                    href="/patients?new=1"
                    className="-mt-5 flex size-12 items-center justify-center rounded-full bg-[#0E8FA3] text-white shadow-lg ring-4 ring-background transition-transform active:scale-95"
                    title="Acción rápida: Nuevo paciente"
                >
                    <Plus className="size-6" />
                </Link>

                <Link
                    href="/agenda"
                    className={`flex flex-col items-center gap-0.5 px-3 py-1 text-[11px] font-medium ${
                        isCurrentUrl('/agenda')
                            ? 'text-[#0E8FA3]'
                            : 'text-muted-foreground'
                    }`}
                >
                    <CalendarDays className="size-5" />
                    <span>Agenda</span>
                </Link>

                {auth.user?.can.manage_cash ? (
                    <Link
                        href="/caja"
                        className={`flex flex-col items-center gap-0.5 px-3 py-1 text-[11px] font-medium ${
                            isCurrentUrl('/caja')
                                ? 'text-[#0E8FA3]'
                                : 'text-muted-foreground'
                        }`}
                    >
                        <Wallet className="size-5" />
                        <span>Caja</span>
                    </Link>
                ) : (
                    <Link
                        href="/reportes"
                        className={`flex flex-col items-center gap-0.5 px-3 py-1 text-[11px] font-medium ${
                            isCurrentUrl('/reportes')
                                ? 'text-[#0E8FA3]'
                                : 'text-muted-foreground'
                        }`}
                    >
                        <LayoutGrid className="size-5" />
                        <span>Reportes</span>
                    </Link>
                )}
            </nav>

            <InactivityLock />
        </AppShell>
    );
}
