import { Head } from '@inertiajs/react';
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import { edit as editAppearance } from '@/routes/appearance';

export default function Appearance() {
    return (
        <>
            <Head title="Apariencia" />

            <h1 className="sr-only">Ajustes de apariencia</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Apariencia de la interfaz"
                    description="Elige entre modo claro clínico, modo oscuro para poca luz o la configuración del sistema"
                />
                <AppearanceTabs />
            </div>
        </>
    );
}

Appearance.layout = {
    breadcrumbs: [
        {
            title: 'Apariencia',
            href: editAppearance(),
        },
    ],
};
