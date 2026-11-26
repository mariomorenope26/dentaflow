import { Head } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

type AuditEntry = {
    id: number;
    action: string;
    summary: string;
    ip_address: string | null;
    created_at: string;
    user?: { id: number; name: string; role: string } | null;
};

type Props = {
    logs: {
        data: AuditEntry[];
        total: number;
    };
};

export default function ClinicAudit({ logs }: Props) {
    return (
        <>
            <Head title="Registro de auditoría" />
            <div className="mx-auto max-w-6xl space-y-5 p-4 md:p-6">
                <div className="flex items-center gap-2.5">
                    <ShieldCheck className="size-6 text-[#0E8FA3]" />
                    <div>
                        <h1 className="text-2xl font-bold">
                            Registro de auditoría clínica
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Trazabilidad de accesos, ediciones, firmas y
                            exportaciones PDF ({logs.total} eventos)
                        </p>
                    </div>
                </div>

                <div className="overflow-hidden rounded-2xl border bg-card shadow-xs">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b bg-muted/40 text-xs text-muted-foreground">
                            <tr>
                                <th className="px-4 py-3">Fecha / Hora</th>
                                <th className="px-4 py-3">Usuario</th>
                                <th className="px-4 py-3">Acción</th>
                                <th className="px-4 py-3">Detalle</th>
                                <th className="px-4 py-3">IP</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {logs.data.map((log) => (
                                <tr key={log.id} className="hover:bg-muted/20">
                                    <td className="px-4 py-2.5 text-xs whitespace-nowrap text-muted-foreground">
                                        {new Date(
                                            log.created_at,
                                        ).toLocaleString('es-PE')}
                                    </td>
                                    <td className="px-4 py-2.5 text-xs font-semibold">
                                        {log.user?.name ?? 'Sistema'}
                                    </td>
                                    <td className="px-4 py-2.5">
                                        <Badge
                                            variant="outline"
                                            className="text-[11px] uppercase"
                                        >
                                            {log.action}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-2.5 text-xs">
                                        {log.summary}
                                    </td>
                                    <td className="px-4 py-2.5 font-mono text-[11px] text-muted-foreground">
                                        {log.ip_address ?? '-'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}
