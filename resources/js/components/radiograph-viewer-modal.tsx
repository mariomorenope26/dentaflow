import { router } from '@inertiajs/react';
import {
    Contrast,
    MapPin,
    RotateCcw,
    Ruler,
    Save,
    Scale,
    Sun,
    Trash2,
} from 'lucide-react';
import { useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type {
    PatientImage,
    RadiographMeasurement,
    RadiographPin,
} from '@/types';

type ToolMode = 'none' | 'calibrate' | 'measure' | 'pin';

export function RadiographViewerModal({
    patientId,
    image,
    canEdit,
    onClose,
}: {
    patientId: number;
    image: PatientImage | null;
    canEdit: boolean;
    onClose: () => void;
}) {
    const containerRef = useRef<HTMLDivElement>(null);

    const existing = image?.annotations ?? {};
    const [tool, setTool] = useState<ToolMode>('none');
    const [mmPerPx, setMmPerPx] = useState<number | null>(
        existing.calibration_mm_per_px ?? null,
    );
    const [calibrationRefMm, setCalibrationRefMm] = useState('10');
    const [measurements, setMeasurements] = useState<RadiographMeasurement[]>(
        existing.measurements ?? [],
    );
    const [pins, setPins] = useState<RadiographPin[]>(existing.pins ?? []);
    const [pinLabel, setPinLabel] = useState('Ápice / Hallazgo');
    const [measureLabel, setMeasureLabel] = useState('Longitud');
    const [draftStart, setDraftStart] = useState<{
        x: number;
        y: number;
    } | null>(null);

    const [brightness, setBrightness] = useState(
        existing.filters?.brightness ?? 100,
    );
    const [contrast, setContrast] = useState(existing.filters?.contrast ?? 100);
    const [invert, setInvert] = useState(Boolean(existing.filters?.invert));
    const [saving, setSaving] = useState(false);

    if (!image) return null;

    const handleCanvasClick = (e: React.MouseEvent<HTMLDivElement>) => {
        if (!canEdit || tool === 'none') return;
        const rect = containerRef.current?.getBoundingClientRect();
        if (!rect || rect.width === 0 || rect.height === 0) return;

        // Coordenadas normalizadas en base virtual 1000x700 para persistencia independiente de resolución
        const x = ((e.clientX - rect.left) / rect.width) * 1000;
        const y = ((e.clientY - rect.top) / rect.height) * 700;

        if (tool === 'pin') {
            setPins((prev) => [
                ...prev,
                {
                    id: String(Date.now()),
                    x: Number(x.toFixed(1)),
                    y: Number(y.toFixed(1)),
                    label: pinLabel.trim() || 'Hallazgo',
                    color: '#F59E0B',
                },
            ]);
            return;
        }

        if (!draftStart) {
            setDraftStart({ x, y });
            return;
        }

        const dx = x - draftStart.x;
        const dy = y - draftStart.y;
        const pxDist = Math.hypot(dx, dy);
        setDraftStart(null);

        if (pxDist < 3) return;

        if (tool === 'calibrate') {
            const refMm = Number.parseFloat(calibrationRefMm) || 10;
            setMmPerPx(Number((refMm / pxDist).toFixed(4)));
            setTool('measure');
            return;
        }

        if (tool === 'measure') {
            const mm =
                mmPerPx !== null ? Number((pxDist * mmPerPx).toFixed(1)) : null;
            setMeasurements((prev) => [
                ...prev,
                {
                    id: String(Date.now()),
                    x1: Number(draftStart.x.toFixed(1)),
                    y1: Number(draftStart.y.toFixed(1)),
                    x2: Number(x.toFixed(1)),
                    y2: Number(y.toFixed(1)),
                    mm,
                    label: measureLabel.trim() || 'Medición',
                },
            ]);
        }
    };

    const saveAnnotations = () => {
        setSaving(true);
        router.patch(
            `/patients/${patientId}/images/${image.id}/annotations`,
            {
                calibration_mm_per_px: mmPerPx,
                measurements: measurements as never,
                pins: pins as never,
                filters: { brightness, contrast, invert },
            },
            {
                preserveScroll: true,
                onFinish: () => setSaving(false),
                onSuccess: () => onClose(),
            },
        );
    };

    const resetAll = () => {
        setBrightness(100);
        setContrast(100);
        setInvert(false);
        setMeasurements([]);
        setPins([]);
        setMmPerPx(null);
        setDraftStart(null);
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[95vh] max-w-[95vw] overflow-y-auto bg-slate-950 text-slate-100 sm:max-w-5xl">
                <DialogHeader>
                    <DialogTitle className="flex items-center justify-between text-slate-100">
                        <span>
                            Visor Radiográfico & Mediciones — {image.title}
                            {image.tooth_code
                                ? ` (Pieza ${image.tooth_code})`
                                : ''}
                        </span>
                    </DialogTitle>
                </DialogHeader>

                <div className="grid gap-4 lg:grid-cols-12">
                    {/* Lienzo radiológico con SVG overlay */}
                    <div className="space-y-2 lg:col-span-8">
                        <div
                            ref={containerRef}
                            onClick={handleCanvasClick}
                            className={`relative aspect-[10/7] w-full overflow-hidden rounded-xl border border-slate-700 bg-black select-none ${
                                tool !== 'none'
                                    ? 'cursor-crosshair'
                                    : 'cursor-default'
                            }`}
                        >
                            <img
                                src={image.url}
                                alt={image.title}
                                style={{
                                    filter: `brightness(${brightness}%) contrast(${contrast}%) ${
                                        invert ? 'invert(1)' : ''
                                    }`,
                                }}
                                className="h-full w-full object-contain"
                                draggable={false}
                            />

                            <svg
                                viewBox="0 0 1000 700"
                                className="pointer-events-none absolute inset-0 h-full w-full"
                            >
                                {measurements.map((m) => {
                                    const mx = (m.x1 + m.x2) / 2;
                                    const my = (m.y1 + m.y2) / 2;
                                    return (
                                        <g key={m.id}>
                                            <line
                                                x1={m.x1}
                                                y1={m.y1}
                                                x2={m.x2}
                                                y2={m.y2}
                                                stroke="#22D3EE"
                                                strokeWidth="3"
                                            />
                                            <circle
                                                cx={m.x1}
                                                cy={m.y1}
                                                r="5"
                                                fill="#22D3EE"
                                            />
                                            <circle
                                                cx={m.x2}
                                                cy={m.y2}
                                                r="5"
                                                fill="#22D3EE"
                                            />
                                            <rect
                                                x={mx - 48}
                                                y={my - 22}
                                                width="96"
                                                height="20"
                                                rx="4"
                                                fill="rgba(15,23,42,0.85)"
                                            />
                                            <text
                                                x={mx}
                                                y={my - 8}
                                                textAnchor="middle"
                                                fill="#67E8F9"
                                                fontSize="12"
                                                fontWeight="bold"
                                            >
                                                {m.mm !== null
                                                    ? `${m.mm} mm`
                                                    : 'Sin calibrar'}
                                            </text>
                                        </g>
                                    );
                                })}

                                {draftStart && (
                                    <circle
                                        cx={draftStart.x}
                                        cy={draftStart.y}
                                        r="6"
                                        fill="#FBBF24"
                                    />
                                )}

                                {pins.map((p) => (
                                    <g key={p.id}>
                                        <circle
                                            cx={p.x}
                                            cy={p.y}
                                            r="8"
                                            fill={p.color}
                                            stroke="#FFFFFF"
                                            strokeWidth="2"
                                        />
                                        <rect
                                            x={p.x + 12}
                                            y={p.y - 12}
                                            width={Math.max(
                                                72,
                                                p.label.length * 7.5 + 14,
                                            )}
                                            height="22"
                                            rx="5"
                                            fill="rgba(15,23,42,0.9)"
                                        />
                                        <text
                                            x={p.x + 19}
                                            y={p.y + 3}
                                            fill="#FDE68A"
                                            fontSize="12"
                                            fontWeight="bold"
                                        >
                                            {p.label}
                                        </text>
                                    </g>
                                ))}
                            </svg>
                        </div>

                        {tool === 'calibrate' && (
                            <p className="text-xs text-cyan-300">
                                Haz clic en dos extremos de una estructura con
                                medida conocida (ej. implante o regla de{' '}
                                {calibrationRefMm} mm).
                            </p>
                        )}
                        {tool === 'measure' && (
                            <p className="text-xs text-cyan-300">
                                Haz clic en el punto inicial y punto final
                                (conductometría, altura ósea, etc.).
                            </p>
                        )}
                        {tool === 'pin' && (
                            <p className="text-xs text-amber-300">
                                Haz clic sobre cualquier zona de la placa para
                                fijar el marcador.
                            </p>
                        )}
                    </div>

                    {/* Panel de herramientas */}
                    <div className="space-y-4 rounded-xl border border-slate-800 bg-slate-900 p-4 text-xs lg:col-span-4">
                        <div className="space-y-2">
                            <div className="font-bold text-slate-200">
                                Filtros de Diagnóstico Radiográfico
                            </div>
                            <div>
                                <div className="flex justify-between text-slate-400">
                                    <span className="flex items-center gap-1">
                                        <Sun className="size-3.5" /> Brillo
                                    </span>
                                    <span>{brightness}%</span>
                                </div>
                                <input
                                    type="range"
                                    min={50}
                                    max={200}
                                    value={brightness}
                                    onChange={(e) =>
                                        setBrightness(Number(e.target.value))
                                    }
                                    className="w-full"
                                />
                            </div>
                            <div>
                                <div className="flex justify-between text-slate-400">
                                    <span className="flex items-center gap-1">
                                        <Contrast className="size-3.5" />{' '}
                                        Contraste
                                    </span>
                                    <span>{contrast}%</span>
                                </div>
                                <input
                                    type="range"
                                    min={50}
                                    max={220}
                                    value={contrast}
                                    onChange={(e) =>
                                        setContrast(Number(e.target.value))
                                    }
                                    className="w-full"
                                />
                            </div>
                            <Button
                                type="button"
                                size="sm"
                                variant={invert ? 'default' : 'outline'}
                                onClick={() => setInvert((v) => !v)}
                                className={`w-full text-xs ${invert ? 'bg-cyan-600 text-white' : 'border-slate-700 bg-slate-800 text-slate-200'}`}
                            >
                                {invert
                                    ? 'Modo Negativo Activo'
                                    : 'Invertir Escala (Negativo Rx)'}
                            </Button>
                        </div>

                        {canEdit && (
                            <div className="space-y-2 border-t border-slate-800 pt-3">
                                <div className="font-bold text-slate-200">
                                    Medición Calibrada (mm) y Pines
                                </div>
                                <div className="grid grid-cols-2 gap-1.5">
                                    <div>
                                        <Label className="text-[11px] text-slate-400">
                                            Ref. calibración (mm)
                                        </Label>
                                        <Input
                                            type="number"
                                            step="0.5"
                                            value={calibrationRefMm}
                                            onChange={(e) =>
                                                setCalibrationRefMm(
                                                    e.target.value,
                                                )
                                            }
                                            className="mt-1 h-8 border-slate-700 bg-slate-950 text-xs text-white"
                                        />
                                    </div>
                                    <div className="flex items-end">
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant={
                                                tool === 'calibrate'
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                            onClick={() => {
                                                setDraftStart(null);
                                                setTool(
                                                    tool === 'calibrate'
                                                        ? 'none'
                                                        : 'calibrate',
                                                );
                                            }}
                                            className="h-8 w-full gap-1 border-slate-700 bg-slate-800 text-xs text-white"
                                        >
                                            <Scale className="size-3.5" />
                                            Calibrar
                                        </Button>
                                    </div>
                                </div>

                                <div className="text-[11px] text-slate-400">
                                    Escala:{' '}
                                    {mmPerPx !== null
                                        ? `${mmPerPx} mm/px`
                                        : 'Sin calibrar (usa Calibrar primero)'}
                                </div>

                                <div className="flex gap-1.5">
                                    <Input
                                        placeholder="Etiqueta medición"
                                        value={measureLabel}
                                        onChange={(e) =>
                                            setMeasureLabel(e.target.value)
                                        }
                                        className="h-8 border-slate-700 bg-slate-950 text-xs text-white"
                                    />
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant={
                                            tool === 'measure'
                                                ? 'default'
                                                : 'outline'
                                        }
                                        onClick={() => {
                                            setDraftStart(null);
                                            setTool(
                                                tool === 'measure'
                                                    ? 'none'
                                                    : 'measure',
                                            );
                                        }}
                                        className="h-8 shrink-0 gap-1 border-slate-700 bg-slate-800 text-xs text-white"
                                    >
                                        <Ruler className="size-3.5" />
                                        Medir
                                    </Button>
                                </div>

                                <div className="flex gap-1.5">
                                    <Input
                                        placeholder="Texto marcador"
                                        value={pinLabel}
                                        onChange={(e) =>
                                            setPinLabel(e.target.value)
                                        }
                                        className="h-8 border-slate-700 bg-slate-950 text-xs text-white"
                                    />
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant={
                                            tool === 'pin'
                                                ? 'default'
                                                : 'outline'
                                        }
                                        onClick={() => {
                                            setDraftStart(null);
                                            setTool(
                                                tool === 'pin' ? 'none' : 'pin',
                                            );
                                        }}
                                        className="h-8 shrink-0 gap-1 border-slate-700 bg-slate-800 text-xs text-white"
                                    >
                                        <MapPin className="size-3.5" />
                                        Pin
                                    </Button>
                                </div>
                            </div>
                        )}

                        {(measurements.length > 0 || pins.length > 0) && (
                            <div className="max-h-36 space-y-1 overflow-y-auto border-t border-slate-800 pt-2">
                                {measurements.map((m) => (
                                    <div
                                        key={m.id}
                                        className="flex items-center justify-between rounded bg-slate-800 px-2 py-1"
                                    >
                                        <span>
                                            {m.label}:{' '}
                                            <strong className="text-cyan-300">
                                                {m.mm !== null
                                                    ? `${m.mm} mm`
                                                    : 'sin cal.'}
                                            </strong>
                                        </span>
                                        {canEdit && (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setMeasurements((prev) =>
                                                        prev.filter(
                                                            (x) =>
                                                                x.id !== m.id,
                                                        ),
                                                    )
                                                }
                                                className="text-rose-400"
                                            >
                                                <Trash2 className="size-3" />
                                            </button>
                                        )}
                                    </div>
                                ))}
                                {pins.map((p) => (
                                    <div
                                        key={p.id}
                                        className="flex items-center justify-between rounded bg-slate-800 px-2 py-1"
                                    >
                                        <span className="text-amber-300">
                                            Pin: {p.label}
                                        </span>
                                        {canEdit && (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setPins((prev) =>
                                                        prev.filter(
                                                            (x) =>
                                                                x.id !== p.id,
                                                        ),
                                                    )
                                                }
                                                className="text-rose-400"
                                            >
                                                <Trash2 className="size-3" />
                                            </button>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}

                        {canEdit && (
                            <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                onClick={resetAll}
                                className="w-full gap-1 text-slate-400 hover:text-white"
                            >
                                <RotateCcw className="size-3.5" />
                                Restablecer anotaciones y filtros
                            </Button>
                        )}
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={onClose}
                        className="border-slate-700 bg-slate-900 text-slate-200"
                    >
                        Cerrar
                    </Button>
                    {canEdit && (
                        <Button
                            type="button"
                            disabled={saving}
                            onClick={saveAnnotations}
                            className="gap-1.5 bg-[#0E8FA3] text-white hover:bg-[#0A6577]"
                        >
                            <Save className="size-4" />
                            {saving
                                ? 'Guardando...'
                                : 'Guardar mediciones y filtros'}
                        </Button>
                    )}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
