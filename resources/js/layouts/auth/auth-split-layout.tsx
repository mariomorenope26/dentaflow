import { Link } from '@inertiajs/react';
import { Award, ShieldCheck, Sparkles, Stethoscope } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { JmsFooter } from '@/components/jms-footer';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="grid min-h-svh w-full bg-background lg:grid-cols-12">
            {/* Panel visual editorial con fotografía clínica de alto impacto */}
            <div className="relative hidden overflow-hidden bg-[#062E39] text-white lg:col-span-6 lg:flex lg:flex-col lg:justify-between xl:col-span-7">
                <img
                    src="/images/login-dental-hero.jpg"
                    alt="Consultorio odontológico moderno DentaFlow"
                    className="absolute inset-0 h-full w-full object-cover object-center opacity-75"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-[#041F27] via-[#062E39]/65 to-[#062E39]/35" />

                <div className="relative z-10 flex items-center justify-between gap-4 p-8 xl:p-10">
                    <Link
                        href={home()}
                        className="inline-flex items-center gap-3 rounded-2xl border border-white/15 bg-white/10 px-4 py-2.5 backdrop-blur-md transition hover:bg-white/15"
                    >
                        <div className="flex size-10 items-center justify-center rounded-xl bg-[#0E8FA3] text-white shadow-lg">
                            <AppLogoIcon className="size-6" />
                        </div>
                        <div>
                            <div className="text-base font-bold tracking-tight">
                                DentaFlow
                            </div>
                            <div className="text-[11px] text-cyan-100/85">
                                Historia Clínica Electrónica Odontológica
                            </div>
                        </div>
                    </Link>

                    <span className="inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-white/10 px-3.5 py-1.5 text-xs font-semibold backdrop-blur-md">
                        <Award className="size-3.5 text-[#5EEAD4]" />
                        HL7 FHIR R4 · ISO 13606 · CIE-10
                    </span>
                </div>

                <div className="relative z-10 space-y-6 p-8 xl:p-10">
                    <div className="max-w-xl space-y-3">
                        <span className="inline-flex items-center gap-1.5 rounded-full bg-[#0E8FA3]/90 px-3 py-1 text-xs font-semibold text-white shadow-sm">
                            <Sparkles className="size-3.5" />
                            Suite Clínica & Administrativa Integral
                        </span>
                        <h2 className="text-3xl leading-tight font-bold tracking-tight xl:text-4xl">
                            Excelencia clínica, precisión diagnóstica y gestión
                            inteligente en un solo lugar.
                        </h2>
                        <p className="text-sm leading-relaxed text-cyan-50/85">
                            Odontograma anatómico FDI/Universal, notas SOAP con
                            firma digital inmutable, laboratorio dental,
                            bioseguridad en autoclave y facturación SUNAT UBL
                            2.1 con tickets térmicos.
                        </p>
                    </div>

                    <div className="grid max-w-2xl grid-cols-3 gap-3">
                        <div className="rounded-2xl border border-white/15 bg-white/10 p-3.5 backdrop-blur-md">
                            <Stethoscope className="mb-1.5 size-4 text-[#5EEAD4]" />
                            <div className="text-xs font-bold">
                                Estándar HCE
                            </div>
                            <div className="text-[11px] text-cyan-100/80">
                                HL7 FHIR R4 & CIE-10
                            </div>
                        </div>
                        <div className="rounded-2xl border border-white/15 bg-white/10 p-3.5 backdrop-blur-md">
                            <ShieldCheck className="mb-1.5 size-4 text-[#5EEAD4]" />
                            <div className="text-xs font-bold">
                                Bioseguridad
                            </div>
                            <div className="text-[11px] text-cyan-100/80">
                                Autoclave & Kardex
                            </div>
                        </div>
                        <div className="rounded-2xl border border-white/15 bg-white/10 p-3.5 backdrop-blur-md">
                            <Sparkles className="mb-1.5 size-4 text-[#5EEAD4]" />
                            <div className="text-xs font-bold">
                                SUNAT & RENIEC
                            </div>
                            <div className="text-[11px] text-cyan-100/80">
                                UBL 2.1 + apiperu.dev
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Panel derecho de autenticación (6 cols en lg, 5 de 12 columnas en xl+) */}
            <div className="flex min-w-0 flex-col justify-between bg-[#F6FAFB] lg:col-span-6 xl:col-span-5 dark:bg-background">
                <div className="flex flex-1 flex-col items-center justify-center p-6 sm:p-10">
                    <div className="w-full max-w-md space-y-6">
                        <div className="flex flex-col items-center gap-3 text-center lg:items-start lg:text-left">
                            <Link
                                href={home()}
                                className="flex items-center gap-2.5 lg:hidden"
                            >
                                <div className="flex size-10 items-center justify-center rounded-xl bg-[#0E8FA3] text-white shadow-sm">
                                    <AppLogoIcon className="size-6" />
                                </div>
                                <span className="text-lg font-bold">
                                    DentaFlow
                                </span>
                            </Link>
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight text-foreground">
                                    {title}
                                </h1>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {description}
                                </p>
                            </div>
                        </div>

                        <div className="rounded-2xl border border-[#DCE6EA] bg-white p-6 shadow-sm dark:border-border dark:bg-card">
                            {children}
                        </div>
                    </div>
                </div>

                <JmsFooter />
            </div>
        </div>
    );
}
