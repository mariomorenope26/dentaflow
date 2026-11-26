import { usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import type { Auth } from '@/types';

export default function AppLogo() {
    const { name, auth } = usePage<{ name: string; auth: Auth }>().props;
    const clinicName = auth.clinic?.name ?? name;
    const logoUrl = auth.clinic?.logo_url;

    return (
        <>
            {logoUrl ? (
                <img
                    src={logoUrl}
                    alt={clinicName}
                    className="aspect-square size-9 rounded-xl border bg-white object-contain p-0.5 shadow-sm"
                />
            ) : (
                <div className="flex aspect-square size-9 items-center justify-center rounded-xl bg-[#0E8FA3] text-white shadow-sm">
                    <AppLogoIcon className="size-5" />
                </div>
            )}
            <div className="ml-1.5 grid flex-1 text-left text-sm">
                <span className="truncate leading-tight font-bold tracking-tight text-foreground">
                    DentaFlow
                </span>
                <span className="truncate text-[11px] font-medium text-muted-foreground">
                    {clinicName}
                </span>
            </div>
        </>
    );
}
