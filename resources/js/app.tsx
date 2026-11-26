import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';

const appName = import.meta.env.VITE_APP_NAME || 'DentaFlow';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'welcome' || name.startsWith('public/'):
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster richColors position="top-right" />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#0E8FA3',
    },
});

// Register PWA Service Worker with update detection
if (typeof window !== 'undefined' && 'serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/sw.js')
            .then((registration) => {
                if (registration.waiting) {
                    window.dispatchEvent(
                        new CustomEvent('dentaflow:sw-update-available', {
                            detail: { worker: registration.waiting },
                        }),
                    );
                }
                registration.addEventListener('updatefound', () => {
                    const installing = registration.installing;
                    if (!installing) return;
                    installing.addEventListener('statechange', () => {
                        if (
                            installing.state === 'installed' &&
                            navigator.serviceWorker.controller
                        ) {
                            window.dispatchEvent(
                                new CustomEvent(
                                    'dentaflow:sw-update-available',
                                    {
                                        detail: { worker: installing },
                                    },
                                ),
                            );
                        }
                    });
                });
            })
            .catch(() => undefined);
    });
}

// This will set light / dark mode on load...
initializeTheme();
