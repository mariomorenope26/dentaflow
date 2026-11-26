import { Lock, Moon, Sun } from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { GlobalPatientSearch } from '@/components/global-patient-search';
import { OfflineSyncManager } from '@/components/offline-sync-manager';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useAppearance } from '@/hooks/use-appearance';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { resolvedAppearance, updateAppearance } = useAppearance();

    const toggleDark = () => {
        updateAppearance(resolvedAppearance === 'dark' ? 'light' : 'dark');
    };

    return (
        <header className="sticky top-0 z-30 flex min-h-16 shrink-0 flex-wrap items-center justify-between gap-3 border-b border-border bg-card/95 px-4 py-2 backdrop-blur transition-[width,height] ease-linear md:px-6">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>

            <div className="order-3 w-full md:order-2 md:max-w-md md:flex-1">
                <GlobalPatientSearch />
            </div>

            <div className="order-2 flex items-center gap-2 md:order-3">
                <OfflineSyncManager />

                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="size-9 rounded-full"
                    title="Cambiar tema claro / oscuro"
                    onClick={toggleDark}
                >
                    {resolvedAppearance === 'dark' ? (
                        <Sun className="size-4 text-[#F0A92E]" />
                    ) : (
                        <Moon className="size-4 text-muted-foreground" />
                    )}
                </Button>

                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="size-9 rounded-full"
                    title="Bloquear pantalla por privacidad"
                    onClick={() =>
                        window.dispatchEvent(
                            new CustomEvent('dentaflow:lock-screen'),
                        )
                    }
                >
                    <Lock className="size-4 text-muted-foreground" />
                </Button>
            </div>
        </header>
    );
}
