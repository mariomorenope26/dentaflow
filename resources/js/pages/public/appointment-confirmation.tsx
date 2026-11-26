import { Head, router } from '@inertiajs/react';
import {
    CalendarCheck2,
    CalendarClock,
    CheckCircle2,
    Clock,
    MapPin,
    Phone,
    Stethoscope,
} from 'lucide-react';
import { useState } from 'react';
import { JmsFooter } from '@/components/jms-footer';
import { Button } from '@/components/ui/button';

type Props = {
    appointment: {
        id: number;
        title: string;
        chair: string;
        starts_at: string;
        ends_at: string;
        status: string;
        status_label: string;
        patient_confirmed_at: string | null;
        reschedule_requested_at: string | null;
        reschedule_note: string | null;
        token: string;
        patient_name: string;
        dentist_name: string;
        dentist_specialty: string | null;
    };
    clinic: {
        name: string;
        phone: string | null;
        address: string | null;
        logo_url: string | null;
        primary_color: string;
    };
    flash?: {
        success?: string | null;
    };
};

export default function PublicAppointmentConfirmation({
    appointment,
    clinic,
    flash,
}: Props) {
    const [showReschedule, setShowReschedule] = useState(false);
    const [rescheduleNote, setRescheduleNote] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const startDate = new Date(appointment.starts_at);
    const confirmed = Boolean(appointment.patient_confirmed_at);
    const rescheduleSent = Boolean(appointment.reschedule_requested_at);

    const handleConfirm = () => {
        setSubmitting(true);
        router.post(
            `/confirmar-cita/${appointment.token}`,
            { action: 'confirm' },
            { onFinish: () => setSubmitting(false) },
        );
    };

    const handleReschedule = (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        router.post(
            `/confirmar-cita/${appointment.token}`,
            {
                action: 'reschedule',
                reschedule_note: rescheduleNote,
            },
            {
                onFinish: () => setSubmitting(false),
                onSuccess: () => setShowReschedule(false),
            },
        );
    };

    return (
        <>
            <Head title={`Confirmar cita · ${clinic.name}`} />
            <div className="flex min-h-svh flex-col justify-between bg-[#F6FAFB] text-[#1F2D3A]">
                <main className="mx-auto flex w-full max-w-lg flex-1 flex-col justify-center px-4 py-8">
                    <div className="overflow-hidden rounded-3xl border bg-white shadow-sm">
                        <div className="border-b bg-[#0E8FA3]/10 p-6 text-center">
                            {clinic.logo_url && (
                                <img
                                    src={clinic.logo_url}
                                    alt={clinic.name}
                                    className="mx-auto mb-3 max-h-14 object-contain"
                                />
                            )}
                            <div className="text-xs font-bold tracking-wider text-[#0A6577] uppercase">
                                {clinic.name}
                            </div>
                            <h1 className="mt-1 text-xl font-bold">
                                Confirmación de Cita Odontológica
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Estimado(a){' '}
                                <strong className="text-foreground">
                                    {appointment.patient_name}
                                </strong>
                            </p>
                        </div>

                        <div className="space-y-4 p-6">
                            {flash?.success && (
                                <div className="rounded-2xl border border-[#2E9E6B]/40 bg-[#ECFDF5] p-3.5 text-center text-sm font-medium text-[#166534]">
                                    {flash.success}
                                </div>
                            )}

                            <div className="space-y-2.5 rounded-2xl border bg-[#F8FAFC] p-4 text-sm">
                                <div className="flex items-center gap-2.5 font-semibold">
                                    <CalendarCheck2 className="size-4 text-[#0E8FA3]" />
                                    <span className="capitalize">
                                        {startDate.toLocaleDateString('es-PE', {
                                            weekday: 'long',
                                            day: '2-digit',
                                            month: 'long',
                                            year: 'numeric',
                                        })}
                                    </span>
                                </div>
                                <div className="flex items-center gap-2.5">
                                    <Clock className="size-4 text-[#0E8FA3]" />
                                    <span>
                                        Hora:{' '}
                                        <strong>
                                            {startDate.toLocaleTimeString(
                                                'es-PE',
                                                {
                                                    hour: '2-digit',
                                                    minute: '2-digit',
                                                },
                                            )}
                                        </strong>{' '}
                                        · {appointment.chair}
                                    </span>
                                </div>
                                <div className="flex items-center gap-2.5">
                                    <Stethoscope className="size-4 text-[#0E8FA3]" />
                                    <span>
                                        {appointment.title} ·{' '}
                                        <strong>
                                            {appointment.dentist_name}
                                        </strong>
                                    </span>
                                </div>
                                {clinic.address && (
                                    <div className="flex items-center gap-2.5 text-xs text-muted-foreground">
                                        <MapPin className="size-4 shrink-0 text-[#0E8FA3]" />
                                        <span>{clinic.address}</span>
                                    </div>
                                )}
                                {clinic.phone && (
                                    <div className="flex items-center gap-2.5 text-xs text-muted-foreground">
                                        <Phone className="size-4 shrink-0 text-[#0E8FA3]" />
                                        <span>Tel: {clinic.phone}</span>
                                    </div>
                                )}
                            </div>

                            {confirmed ? (
                                <div className="flex items-center justify-center gap-2 rounded-2xl border border-[#2E9E6B]/40 bg-[#ECFDF5] py-4 text-sm font-bold text-[#166534]">
                                    <CheckCircle2 className="size-5" />
                                    ¡Asistencia confirmada! Te esperamos.
                                </div>
                            ) : (
                                <div className="space-y-2.5">
                                    <Button
                                        type="button"
                                        disabled={submitting}
                                        onClick={handleConfirm}
                                        className="h-12 w-full gap-2 rounded-2xl bg-[#2E9E6B] text-base font-bold text-white hover:bg-[#238056]"
                                    >
                                        <CheckCircle2 className="size-5" />
                                        Confirmar mi asistencia
                                    </Button>

                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() =>
                                            setShowReschedule((v) => !v)
                                        }
                                        className="h-11 w-full gap-2 rounded-2xl"
                                    >
                                        <CalendarClock className="size-4 text-[#F0A92E]" />
                                        Solicitar otro horario
                                    </Button>
                                </div>
                            )}

                            {rescheduleSent && !confirmed && (
                                <div className="rounded-2xl border border-amber-300 bg-amber-50 p-3 text-xs text-amber-900">
                                    Solicitud de reprogramación enviada:{' '}
                                    <strong>
                                        {appointment.reschedule_note}
                                    </strong>
                                </div>
                            )}

                            {showReschedule && !confirmed && (
                                <form
                                    onSubmit={handleReschedule}
                                    className="space-y-3 rounded-2xl border bg-muted/20 p-4"
                                >
                                    <label className="block text-xs font-semibold">
                                        Indícanos tu horario disponible
                                        preferido:
                                    </label>
                                    <textarea
                                        required
                                        rows={3}
                                        placeholder="Ej. Por favor cambiar para el viernes después de las 4:00 p.m."
                                        value={rescheduleNote}
                                        onChange={(e) =>
                                            setRescheduleNote(e.target.value)
                                        }
                                        className="w-full rounded-xl border bg-white p-2.5 text-sm"
                                    />
                                    <Button
                                        type="submit"
                                        disabled={submitting}
                                        className="w-full bg-[#0E8FA3] text-white"
                                    >
                                        Enviar solicitud a recepción
                                    </Button>
                                </form>
                            )}
                        </div>
                    </div>
                </main>
                <JmsFooter />
            </div>
        </>
    );
}
