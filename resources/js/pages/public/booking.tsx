import { Head, Link, useForm } from '@inertiajs/react';
import {
    CalendarCheck2,
    Clock,
    MapPin,
    Phone,
    ShieldCheck,
    Stethoscope,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { JmsFooter } from '@/components/jms-footer';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Dentist = {
    id: number;
    name: string;
    specialty: string | null;
    license_number: string | null;
};

type ServiceItem = {
    id: number;
    name: string;
    category: string;
    duration_minutes: number;
    default_price: string | number;
};

type Props = {
    clinic: {
        name: string;
        slogan: string | null;
        phone: string | null;
        address: string | null;
        logo_url: string | null;
        primary_color: string;
        currency_symbol: string;
    };
    dentists: Dentist[];
    services: ServiceItem[];
    defaultDate: string;
};

export default function PublicBooking({
    clinic,
    dentists,
    services,
    defaultDate,
}: Props) {
    const [slots, setSlots] = useState<{ start: string; end: string }[]>([]);
    const [loadingSlots, setLoadingSlots] = useState(false);

    const form = useForm({
        dentist_id: String(dentists[0]?.id ?? ''),
        service_title: services[0]?.name ?? 'Evaluación y Diagnóstico Integral',
        duration: String(services[0]?.duration_minutes ?? 30),
        date: defaultDate,
        start_time: '',
        first_name: '',
        last_name: '',
        document_number: '',
        phone: '',
        email: '',
        reason: '',
    });

    useEffect(() => {
        if (!form.data.dentist_id || !form.data.date) return;
        let cancelled = false;
        setLoadingSlots(true);

        const params = new URLSearchParams({
            dentist_id: form.data.dentist_id,
            date: form.data.date,
            duration: form.data.duration,
        });

        fetch(`/reservar-cita/disponibilidad?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        })
            .then((r) => r.json())
            .then((data) => {
                if (cancelled) return;
                const list = Array.isArray(data.slots) ? data.slots : [];
                setSlots(list);
                form.setData('start_time', list[0]?.start ?? '');
            })
            .catch(() => {
                if (!cancelled) setSlots([]);
            })
            .finally(() => {
                if (!cancelled) setLoadingSlots(false);
            });

        return () => {
            cancelled = true;
        };
    }, [form.data.dentist_id, form.data.date, form.data.duration]);

    const submitBooking = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/reservar-cita');
    };

    return (
        <>
            <Head title={`Reserva de Citas Online · ${clinic.name}`} />
            <div className="flex min-h-screen flex-col bg-[#F6FAFB] text-[#1F2D3A]">
                <header className="border-b border-[#DCE6EA] bg-white px-5 py-4 sm:px-8">
                    <div className="mx-auto flex max-w-5xl items-center justify-between gap-4">
                        <Link href="/" className="flex items-center gap-3">
                            {clinic.logo_url ? (
                                <img
                                    src={clinic.logo_url}
                                    alt={clinic.name}
                                    className="size-10 rounded-xl border bg-white object-contain p-0.5"
                                />
                            ) : (
                                <div className="flex size-10 items-center justify-center rounded-xl bg-[#0E8FA3] text-white">
                                    <AppLogoIcon className="size-6" />
                                </div>
                            )}
                            <div>
                                <div className="text-base font-bold">
                                    {clinic.name}
                                </div>
                                <div className="text-xs text-[#5F6F7C]">
                                    Agenda Online 24/7 · Sin necesidad de
                                    registro
                                </div>
                            </div>
                        </Link>
                        <div className="hidden items-center gap-4 text-xs text-[#5F6F7C] sm:flex">
                            {clinic.address && (
                                <span className="inline-flex items-center gap-1">
                                    <MapPin className="size-3.5 text-[#0E8FA3]" />
                                    {clinic.address}
                                </span>
                            )}
                            {clinic.phone && (
                                <span className="inline-flex items-center gap-1">
                                    <Phone className="size-3.5 text-[#2E9E6B]" />
                                    {clinic.phone}
                                </span>
                            )}
                        </div>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-8 sm:px-6">
                    <div className="grid gap-6 lg:grid-cols-12">
                        <div className="space-y-4 lg:col-span-4">
                            <div className="rounded-2xl bg-[#062E39] p-6 text-white shadow-md">
                                <div className="mb-3 inline-flex items-center gap-1.5 rounded-full bg-[#0E8FA3] px-3 py-1 text-xs font-semibold">
                                    <CalendarCheck2 className="size-3.5" />
                                    Reserva Inmediata
                                </div>
                                <h1 className="text-2xl font-bold">
                                    Agenda tu cita odontológica en 1 minuto
                                </h1>
                                <p className="mt-2 text-xs leading-relaxed text-cyan-100/85">
                                    Elige al especialista, fecha y horario libre
                                    en tiempo real. Al confirmar recibirás tu
                                    pase de cita digital con opción de cambio.
                                </p>
                                <div className="mt-5 space-y-2 border-t border-white/15 pt-4 text-xs text-cyan-100/90">
                                    <div className="flex items-center gap-2">
                                        <ShieldCheck className="size-4 text-[#5EEAD4]" />
                                        Protocolos de bioseguridad certificados
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Stethoscope className="size-4 text-[#5EEAD4]" />
                                        Historia Clínica Electrónica HCE
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form
                            onSubmit={submitBooking}
                            className="space-y-5 rounded-2xl border border-[#DCE6EA] bg-white p-6 shadow-sm lg:col-span-8"
                        >
                            <h2 className="border-b pb-3 text-lg font-bold">
                                1. Selecciona especialidad, doctor y horario
                            </h2>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <Label>Servicio / Motivo *</Label>
                                    <select
                                        value={form.data.service_title}
                                        onChange={(e) => {
                                            const selected = services.find(
                                                (s) =>
                                                    s.name === e.target.value,
                                            );
                                            form.setData({
                                                ...form.data,
                                                service_title: e.target.value,
                                                duration: String(
                                                    selected?.duration_minutes ??
                                                        30,
                                                ),
                                            });
                                        }}
                                        className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                                    >
                                        <option value="Evaluación y Diagnóstico Integral">
                                            Evaluación y Diagnóstico Integral
                                        </option>
                                        {services.map((s) => (
                                            <option key={s.id} value={s.name}>
                                                {s.name} ({s.duration_minutes}{' '}
                                                min)
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <Label>Odontólogo *</Label>
                                    <select
                                        value={form.data.dentist_id}
                                        onChange={(e) =>
                                            form.setData(
                                                'dentist_id',
                                                e.target.value,
                                            )
                                        }
                                        className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                                    >
                                        {dentists.map((d) => (
                                            <option key={d.id} value={d.id}>
                                                {d.name}
                                                {d.specialty
                                                    ? ` · ${d.specialty}`
                                                    : ''}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <Label>Fecha deseada *</Label>
                                    <Input
                                        type="date"
                                        required
                                        min={defaultDate}
                                        value={form.data.date}
                                        onChange={(e) =>
                                            form.setData('date', e.target.value)
                                        }
                                    />
                                </div>

                                <div>
                                    <Label className="flex items-center gap-1.5">
                                        <Clock className="size-3.5 text-[#0E8FA3]" />
                                        Horarios disponibles *
                                    </Label>
                                    {loadingSlots ? (
                                        <div className="mt-2 text-xs text-muted-foreground">
                                            Consultando disponibilidad...
                                        </div>
                                    ) : slots.length === 0 ? (
                                        <div className="mt-2 text-xs text-[#D64545]">
                                            Sin turnos libres este día. Prueba
                                            otra fecha.
                                        </div>
                                    ) : (
                                        <div className="mt-1.5 flex max-h-28 flex-wrap gap-1.5 overflow-y-auto">
                                            {slots.map((sl) => (
                                                <button
                                                    key={sl.start}
                                                    type="button"
                                                    onClick={() =>
                                                        form.setData(
                                                            'start_time',
                                                            sl.start,
                                                        )
                                                    }
                                                    className={`rounded-lg border px-2.5 py-1 text-xs font-semibold transition ${
                                                        form.data.start_time ===
                                                        sl.start
                                                            ? 'border-[#0E8FA3] bg-[#0E8FA3] text-white'
                                                            : 'bg-[#F6FAFB] hover:border-[#0E8FA3]'
                                                    }`}
                                                >
                                                    {sl.start}
                                                </button>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            </div>

                            <h2 className="border-b pt-2 pb-3 text-lg font-bold">
                                2. Tus datos para el registro de cita
                            </h2>

                            <div className="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <Label>Nombres *</Label>
                                    <Input
                                        required
                                        value={form.data.first_name}
                                        onChange={(e) =>
                                            form.setData(
                                                'first_name',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <Label>Apellidos *</Label>
                                    <Input
                                        required
                                        value={form.data.last_name}
                                        onChange={(e) =>
                                            form.setData(
                                                'last_name',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <Label>DNI / Documento *</Label>
                                    <Input
                                        required
                                        value={form.data.document_number}
                                        onChange={(e) =>
                                            form.setData(
                                                'document_number',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <Label>WhatsApp / Celular *</Label>
                                    <Input
                                        required
                                        value={form.data.phone}
                                        onChange={(e) =>
                                            form.setData(
                                                'phone',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <Label>Correo electrónico (opcional)</Label>
                                    <Input
                                        type="email"
                                        value={form.data.email}
                                        onChange={(e) =>
                                            form.setData(
                                                'email',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <Label>Comentario / Molestia</Label>
                                    <Input
                                        placeholder="Ej. Sensibilidad al frío o revisión"
                                        value={form.data.reason}
                                        onChange={(e) =>
                                            form.setData(
                                                'reason',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                            </div>

                            <div className="flex justify-end pt-2">
                                <Button
                                    type="submit"
                                    disabled={
                                        form.processing || !form.data.start_time
                                    }
                                    className="h-11 gap-2 rounded-xl bg-[#0E8FA3] px-6 text-white hover:bg-[#0A6577]"
                                >
                                    <CalendarCheck2 className="size-4" />
                                    {form.processing
                                        ? 'Reservando...'
                                        : 'Confirmar reserva de cita'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </main>

                <JmsFooter />
            </div>
        </>
    );
}
