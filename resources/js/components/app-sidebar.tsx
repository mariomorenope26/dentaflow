import { Link, usePage } from '@inertiajs/react';
import {
    BarChart3,
    BookOpen,
    Bot,
    CalendarDays,
    ClipboardList,
    FileSpreadsheet,
    FlaskConical,
    HeartHandshake,
    LayoutGrid,
    Settings,
    ShieldCheck,
    Users,
    Wallet,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { Auth, NavItem } from '@/types';

export function AppSidebar() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const can = auth.user?.can;

    const mainNavItems: NavItem[] = [
        {
            title: 'Inicio',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Pacientes',
            href: '/patients',
            icon: Users,
        },
        {
            title: 'Agenda',
            href: '/agenda',
            icon: CalendarDays,
        },
        {
            title: 'Tratamientos',
            href: '/treatments',
            icon: ClipboardList,
        },
        {
            title: 'Laboratorio',
            href: '/laboratorio',
            icon: FlaskConical,
        },
        {
            title: 'Esterilización / Kardex',
            href: '/esterilizacion-kardex',
            icon: ShieldCheck,
        },
        ...(can?.manage_cash
            ? [
                  {
                      title: 'Caja y cobros',
                      href: '/caja',
                      icon: Wallet,
                  },
              ]
            : []),
        ...(can?.manage_patients || can?.manage_cash
            ? [
                  {
                      title: 'CRM y Retención',
                      href: '/crm',
                      icon: HeartHandshake,
                  },
              ]
            : []),
        {
            title: 'Reportes',
            href: '/reportes',
            icon: BarChart3,
        },
        ...(can?.edit_clinical || can?.manage_clinic || can?.view_audit
            ? [
                  {
                      title: 'Contralor IA',
                      href: '/contralor-ia',
                      icon: Bot,
                  },
              ]
            : []),
        ...(can?.view_audit
            ? [
                  {
                      title: 'Auditoría',
                      href: '/clinic/audit',
                      icon: FileSpreadsheet,
                  },
              ]
            : []),
        ...(can?.manage_clinic
            ? [
                  {
                      title: 'Ajustes',
                      href: '/clinic/settings',
                      icon: Settings,
                  },
              ]
            : []),
        {
            title: 'Manual de uso',
            href: '/manual',
            icon: BookOpen,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                {auth.user && (
                    <div className="mx-2 mb-2 rounded-xl border bg-secondary/50 px-3 py-2 text-xs group-data-[collapsible=icon]:hidden">
                        <div className="font-semibold text-secondary-foreground">
                            {auth.user.role_label}
                        </div>
                        {auth.user.license_number && (
                            <div className="truncate text-[11px] text-muted-foreground">
                                {auth.user.license_number}
                            </div>
                        )}
                    </div>
                )}
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
