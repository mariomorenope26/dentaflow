import { router } from '@inertiajs/react';
import {
    ArrowLeftRight,
    Check,
    Eraser,
    FileDown,
    Layers,
    MessageSquareText,
    Plus,
    Redo2,
    Save,
    Sparkles,
    Trash2,
    Undo2,
    ZoomIn,
    ZoomOut,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
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
import { queueOfflineMutation } from '@/lib/offline-store';
import type {
    Odontogram,
    OdontogramCondition,
    OdontogramFinding,
} from '@/types';

const UPPER_ADULT_RIGHT = ['18', '17', '16', '15', '14', '13', '12', '11'];
const UPPER_ADULT_LEFT = ['21', '22', '23', '24', '25', '26', '27', '28'];
const LOWER_ADULT_RIGHT = ['48', '47', '46', '45', '44', '43', '42', '41'];
const LOWER_ADULT_LEFT = ['31', '32', '33', '34', '35', '36', '37', '38'];

const UPPER_CHILD_RIGHT = ['55', '54', '53', '52', '51'];
const UPPER_CHILD_LEFT = ['61', '62', '63', '64', '65'];
const LOWER_CHILD_RIGHT = ['85', '84', '83', '82', '81'];
const LOWER_CHILD_LEFT = ['71', '72', '73', '74', '75'];

const MOLAR_CODES = new Set([
    '18',
    '17',
    '16',
    '26',
    '27',
    '28',
    '36',
    '37',
    '38',
    '46',
    '47',
    '48',
    '55',
    '54',
    '64',
    '65',
    '74',
    '75',
    '84',
    '85',
]);

const UNIVERSAL_MAP: Record<string, string> = {
    '18': '1',
    '17': '2',
    '16': '3',
    '15': '4',
    '14': '5',
    '13': '6',
    '12': '7',
    '11': '8',
    '21': '9',
    '22': '10',
    '23': '11',
    '24': '12',
    '25': '13',
    '26': '14',
    '27': '15',
    '28': '16',
    '38': '17',
    '37': '18',
    '36': '19',
    '35': '20',
    '34': '21',
    '33': '22',
    '32': '23',
    '31': '24',
    '41': '25',
    '42': '26',
    '43': '27',
    '44': '28',
    '45': '29',
    '46': '30',
    '47': '31',
    '48': '32',
    '55': 'A',
    '54': 'B',
    '53': 'C',
    '52': 'D',
    '51': 'E',
    '61': 'F',
    '62': 'G',
    '63': 'H',
    '64': 'I',
    '65': 'J',
    '75': 'K',
    '74': 'L',
    '73': 'M',
    '72': 'N',
    '71': 'O',
    '81': 'P',
    '82': 'Q',
    '83': 'R',
    '84': 'S',
    '85': 'T',
};

function findingColor(finding: OdontogramFinding): string {
    if (finding.status === 'ausente') return '#5F6F7C';
    if (finding.status === 'sano') return '#2E9E6B';
    if (finding.status === 'realizado') return '#2F7FD1';
    return '#D64545';
}

function isRightQuadrant(code: string): boolean {
    const q = code[0];
    return q === '1' || q === '4' || q === '5' || q === '8';
}

function isUpperQuadrant(code: string): boolean {
    const q = code[0];
    return q === '1' || q === '2' || q === '5' || q === '6';
}

type ToothSvgProps = {
    code: string;
    notation: 'FDI' | 'UNIVERSAL';
    findings: OdontogramFinding[];
    conditionMap: Record<string, OdontogramCondition>;
    selectedMulti: boolean;
    onSurfaceClick: (
        toothCode: string,
        surface: 'O' | 'V' | 'L' | 'M' | 'D',
    ) => void;
    onInspectTooth: (toothCode: string) => void;
    readOnly: boolean;
    compact?: boolean;
};

function ToothSvg({
    code,
    notation,
    findings,
    conditionMap,
    selectedMulti,
    onSurfaceClick,
    onInspectTooth,
    readOnly,
    compact = false,
}: ToothSvgProps) {
    const displayNum =
        notation === 'UNIVERSAL' ? (UNIVERSAL_MAP[code] ?? code) : code;
    const rightSide = isRightQuadrant(code);
    const upper = isUpperQuadrant(code);
    const isMolar = MOLAR_CODES.has(code);

    const surfaceColor = (surface: 'O' | 'V' | 'L' | 'M' | 'D'): string => {
        const hit = findings.find((f) => f.surface === surface);
        if (!hit) return '#FFFFFF';
        return findingColor(hit);
    };

    const fullFindings = findings.filter((f) => f.surface === 'FULL');
    const primaryFull = fullFindings[0];
    const isAbsent = fullFindings.some((f) => f.condition_code === 'ausente');
    const isExtract = fullFindings.some(
        (f) => f.condition_code === 'extraccion_indicada',
    );
    const hasCrown = fullFindings.some((f) => f.condition_code === 'corona');
    const hasEndo = fullFindings.some((f) => f.condition_code === 'endodoncia');
    const hasImplant = fullFindings.some(
        (f) => f.condition_code === 'implante',
    );
    const hasBridge = fullFindings.some(
        (f) => f.condition_code === 'protesis_fija',
    );
    const hasRemovable = fullFindings.some(
        (f) => f.condition_code === 'protesis_removible',
    );
    const hasOrtho = fullFindings.some(
        (f) => f.condition_code === 'ortodoncia',
    );
    const hasApical = fullFindings.some(
        (f) => f.condition_code === 'lesion_periapical',
    );
    const hasRecession = fullFindings.some(
        (f) => f.condition_code === 'recesion',
    );
    const hasFracture = findings.some((f) => f.condition_code === 'fractura');

    const leftSurface: 'M' | 'D' = rightSide ? 'D' : 'M';
    const rightSurface: 'M' | 'D' = rightSide ? 'M' : 'D';
    const topSurface: 'V' | 'L' = upper ? 'V' : 'L';
    const bottomSurface: 'V' | 'L' = upper ? 'L' : 'V';

    const topFill = surfaceColor(topSurface);
    const bottomFill = surfaceColor(bottomSurface);
    const leftFill = surfaceColor(leftSurface);
    const rightFill = surfaceColor(rightSurface);
    const centerFill = surfaceColor('O');

    // Crown bounding box coordinates inside 64x96 viewBox (root + crown anatomy)
    const cy = upper ? 32 : 4;
    const ry = upper ? 0 : 66;
    const apexY = upper ? 6 : 90;
    const neckY = upper ? 32 : 64;

    const badgeText = fullFindings
        .map(
            (f) =>
                conditionMap[f.condition_code]?.short ??
                f.condition_code.slice(0, 3).toUpperCase(),
        )
        .slice(0, 2)
        .join('·');

    const badgeColor = primaryFull ? findingColor(primaryFull) : '#0E8FA3';

    return (
        <div
            className={`flex flex-col items-center rounded-xl border p-1 transition-all ${
                selectedMulti
                    ? 'border-[#0E8FA3] bg-[#E6F5F7] ring-2 ring-[#0E8FA3]/40'
                    : findings.length > 0
                      ? 'border-[#DCE6EA] bg-white shadow-2xs dark:bg-card'
                      : 'border-transparent hover:border-border hover:bg-muted/30'
            }`}
        >
            {upper && (
                <button
                    type="button"
                    disabled={readOnly}
                    onClick={() => onInspectTooth(code)}
                    title={`Ver detalles pieza ${displayNum}`}
                    className="mb-0.5 rounded px-1 text-[11px] font-bold tracking-tight text-foreground hover:bg-[#E6F5F7] hover:text-[#0A6577]"
                >
                    {displayNum}
                </button>
            )}

            <svg
                viewBox="0 0 64 96"
                className={
                    compact
                        ? 'h-14 w-10 select-none'
                        : 'h-18 w-12 touch-manipulation select-none sm:h-20 sm:w-13'
                }
            >
                {/* Raíces anatómicas */}
                {!hasImplant && (
                    <g opacity={isAbsent ? 0.3 : 1} pointerEvents="none">
                        {isMolar ? (
                            upper ? (
                                <>
                                    <path
                                        d="M14,32 L20,7 L28,32"
                                        fill="#F8FAFC"
                                        stroke="#94A3B8"
                                        strokeWidth="1.5"
                                    />
                                    <path
                                        d="M26,32 L32,4 L38,32"
                                        fill="#F8FAFC"
                                        stroke="#94A3B8"
                                        strokeWidth="1.5"
                                    />
                                    <path
                                        d="M36,32 L44,7 L50,32"
                                        fill="#F8FAFC"
                                        stroke="#94A3B8"
                                        strokeWidth="1.5"
                                    />
                                </>
                            ) : (
                                <>
                                    <path
                                        d="M14,64 L22,90 L31,64"
                                        fill="#F8FAFC"
                                        stroke="#94A3B8"
                                        strokeWidth="1.5"
                                    />
                                    <path
                                        d="M33,64 L42,90 L50,64"
                                        fill="#F8FAFC"
                                        stroke="#94A3B8"
                                        strokeWidth="1.5"
                                    />
                                </>
                            )
                        ) : upper ? (
                            <path
                                d="M21,32 L32,4 L43,32"
                                fill="#F8FAFC"
                                stroke="#94A3B8"
                                strokeWidth="1.5"
                            />
                        ) : (
                            <path
                                d="M21,64 L32,91 L43,64"
                                fill="#F8FAFC"
                                stroke="#94A3B8"
                                strokeWidth="1.5"
                            />
                        )}
                    </g>
                )}

                {/* Implante en zona radicular */}
                {hasImplant && (
                    <g pointerEvents="none">
                        <rect
                            x="25"
                            y={upper ? 6 : 66}
                            width="14"
                            height="24"
                            rx="3"
                            fill="#E0F2FE"
                            stroke={findingColor(
                                fullFindings.find(
                                    (f) => f.condition_code === 'implante',
                                )!,
                            )}
                            strokeWidth="2.2"
                        />
                        <line
                            x1="25"
                            y1={ry + 10}
                            x2="39"
                            y2={ry + 10}
                            stroke="#2F7FD1"
                            strokeWidth="1.8"
                        />
                        <line
                            x1="25"
                            y1={ry + 16}
                            x2="39"
                            y2={ry + 16}
                            stroke="#2F7FD1"
                            strokeWidth="1.8"
                        />
                        <line
                            x1="25"
                            y1={ry + 22}
                            x2="39"
                            y2={ry + 22}
                            stroke="#2F7FD1"
                            strokeWidth="1.8"
                        />
                    </g>
                )}

                {/* Endodoncia a lo largo de la raíz */}
                {hasEndo && (
                    <g pointerEvents="none">
                        <line
                            x1="32"
                            y1={apexY}
                            x2="32"
                            y2={cy + 30}
                            stroke={findingColor(
                                fullFindings.find(
                                    (f) => f.condition_code === 'endodoncia',
                                )!,
                            )}
                            strokeWidth="3.4"
                            strokeLinecap="round"
                        />
                        <polygon
                            points={
                                upper ? '27,10 37,10 32,2' : '27,86 37,86 32,94'
                            }
                            fill={findingColor(
                                fullFindings.find(
                                    (f) => f.condition_code === 'endodoncia',
                                )!,
                            )}
                        />
                    </g>
                )}

                {/* Lesión periapical en ápice radicular */}
                {hasApical && (
                    <circle
                        cx="32"
                        cy={apexY}
                        r="6"
                        fill="#FEE2E2"
                        stroke="#D64545"
                        strokeWidth="2.4"
                        pointerEvents="none"
                    />
                )}

                {/* Recesión gingival en cuello */}
                {hasRecession && (
                    <path
                        d={`M10,${neckY} Q32,${upper ? neckY - 8 : neckY + 8} 54,${neckY}`}
                        fill="none"
                        stroke="#D64545"
                        strokeWidth="2.6"
                        pointerEvents="none"
                    />
                )}

                {/* Corona: 5 caras interactivas */}
                <polygon
                    points={`6,${cy + 2} 58,${cy + 2} 44,${cy + 16} 20,${cy + 16}`}
                    fill={topFill}
                    stroke="#5F6F7C"
                    strokeWidth="1.8"
                    className={
                        readOnly ? '' : 'cursor-pointer hover:opacity-80'
                    }
                    onClick={() =>
                        !readOnly && onSurfaceClick(code, topSurface)
                    }
                />
                <polygon
                    points={`20,${cy + 42} 44,${cy + 42} 58,${cy + 56} 6,${cy + 56}`}
                    fill={bottomFill}
                    stroke="#5F6F7C"
                    strokeWidth="1.8"
                    className={
                        readOnly ? '' : 'cursor-pointer hover:opacity-80'
                    }
                    onClick={() =>
                        !readOnly && onSurfaceClick(code, bottomSurface)
                    }
                />
                <polygon
                    points={`6,${cy + 2} 20,${cy + 16} 20,${cy + 42} 6,${cy + 56}`}
                    fill={leftFill}
                    stroke="#5F6F7C"
                    strokeWidth="1.8"
                    className={
                        readOnly ? '' : 'cursor-pointer hover:opacity-80'
                    }
                    onClick={() =>
                        !readOnly && onSurfaceClick(code, leftSurface)
                    }
                />
                <polygon
                    points={`58,${cy + 2} 58,${cy + 56} 44,${cy + 42} 44,${cy + 16}`}
                    fill={rightFill}
                    stroke="#5F6F7C"
                    strokeWidth="1.8"
                    className={
                        readOnly ? '' : 'cursor-pointer hover:opacity-80'
                    }
                    onClick={() =>
                        !readOnly && onSurfaceClick(code, rightSurface)
                    }
                />
                <rect
                    x="20"
                    y={cy + 16}
                    width="24"
                    height="26"
                    fill={centerFill}
                    stroke="#5F6F7C"
                    strokeWidth="1.8"
                    className={
                        readOnly ? '' : 'cursor-pointer hover:opacity-80'
                    }
                    onClick={() => !readOnly && onSurfaceClick(code, 'O')}
                />

                {hasCrown && (
                    <circle
                        cx="32"
                        cy={cy + 29}
                        r="26"
                        fill="none"
                        stroke={findingColor(
                            fullFindings.find(
                                (f) => f.condition_code === 'corona',
                            )!,
                        )}
                        strokeWidth="3.2"
                        pointerEvents="none"
                    />
                )}

                {hasBridge && (
                    <rect
                        x="2"
                        y={cy + 26}
                        width="60"
                        height="6"
                        rx="2"
                        fill={findingColor(
                            fullFindings.find(
                                (f) => f.condition_code === 'protesis_fija',
                            )!,
                        )}
                        opacity="0.88"
                        pointerEvents="none"
                    />
                )}

                {hasRemovable && (
                    <g pointerEvents="none">
                        <line
                            x1="2"
                            y1={cy + 23}
                            x2="62"
                            y2={cy + 23}
                            stroke={findingColor(
                                fullFindings.find(
                                    (f) =>
                                        f.condition_code ===
                                        'protesis_removible',
                                )!,
                            )}
                            strokeWidth="2.5"
                        />
                        <line
                            x1="2"
                            y1={cy + 35}
                            x2="62"
                            y2={cy + 35}
                            stroke={findingColor(
                                fullFindings.find(
                                    (f) =>
                                        f.condition_code ===
                                        'protesis_removible',
                                )!,
                            )}
                            strokeWidth="2.5"
                        />
                    </g>
                )}

                {hasOrtho && (
                    <g pointerEvents="none">
                        <line
                            x1="2"
                            y1={cy + 29}
                            x2="62"
                            y2={cy + 29}
                            stroke="#2F7FD1"
                            strokeWidth="2.2"
                        />
                        <rect
                            x="26"
                            y={cy + 23}
                            width="12"
                            height="12"
                            fill="#FFFFFF"
                            stroke="#2F7FD1"
                            strokeWidth="2.4"
                        />
                    </g>
                )}

                {hasFracture && (
                    <polyline
                        points={`14,${cy + 12} 28,${cy + 24} 22,${cy + 34} 48,${cy + 46}`}
                        fill="none"
                        stroke="#D64545"
                        strokeWidth="2.8"
                        pointerEvents="none"
                    />
                )}

                {(isAbsent || isExtract) && (
                    <g pointerEvents="none">
                        <line
                            x1="8"
                            y1={cy + 4}
                            x2="56"
                            y2={cy + 54}
                            stroke={isExtract ? '#D64545' : '#5F6F7C'}
                            strokeWidth="4.2"
                            strokeLinecap="round"
                        />
                        <line
                            x1="56"
                            y1={cy + 4}
                            x2="8"
                            y2={cy + 54}
                            stroke={isExtract ? '#D64545' : '#5F6F7C'}
                            strokeWidth="4.2"
                            strokeLinecap="round"
                        />
                    </g>
                )}
            </svg>

            {!upper && (
                <button
                    type="button"
                    disabled={readOnly}
                    onClick={() => onInspectTooth(code)}
                    title={`Ver detalles pieza ${displayNum}`}
                    className="mt-0.5 rounded px-1 text-[11px] font-bold tracking-tight text-foreground hover:bg-[#E6F5F7] hover:text-[#0A6577]"
                >
                    {displayNum}
                </button>
            )}

            <div className="h-4">
                {badgeText && (
                    <span
                        className="py-0.2 inline-block rounded px-1 text-[9px] font-bold text-white"
                        style={{ backgroundColor: badgeColor }}
                    >
                        {badgeText}
                    </span>
                )}
            </div>
        </div>
    );
}

type Props = {
    patientId: number;
    odontograms: Odontogram[];
    conditions: OdontogramCondition[];
    canEdit: boolean;
    defaultNotation?: 'FDI' | 'UNIVERSAL';
};

export function OdontogramChart({
    patientId,
    odontograms,
    conditions,
    canEdit,
    defaultNotation = 'FDI',
}: Props) {
    const [activeId, setActiveId] = useState<number>(odontograms[0]?.id ?? 0);
    const activeOdontogram =
        odontograms.find((o) => o.id === activeId) ?? odontograms[0] ?? null;

    const [dentition, setDentition] = useState<'adulto' | 'nino' | 'mixto'>(
        activeOdontogram?.dentition ?? 'adulto',
    );
    const [notation, setNotation] = useState<'FDI' | 'UNIVERSAL'>(
        activeOdontogram?.notation ?? defaultNotation,
    );
    const [findings, setFindings] = useState<OdontogramFinding[]>(
        activeOdontogram?.findings ?? [],
    );
    const [notes, setNotes] = useState<string>(activeOdontogram?.notes ?? '');

    const [undoStack, setUndoStack] = useState<OdontogramFinding[][]>([]);
    const [redoStack, setRedoStack] = useState<OdontogramFinding[][]>([]);
    const [selectedTool, setSelectedTool] = useState<string>('caries');
    const [selectedStatus, setSelectedStatus] = useState<
        'por_realizar' | 'realizado'
    >('por_realizar');
    const [eraserMode, setEraserMode] = useState(false);
    const [multiMode, setMultiMode] = useState(false);
    const [multiTeeth, setMultiTeeth] = useState<string[]>([]);
    const [zoom, setZoom] = useState(1);
    const [saving, setSaving] = useState(false);
    const [compareMode, setCompareMode] = useState(false);
    const [compareId, setCompareId] = useState<number>(
        odontograms[1]?.id ?? odontograms[0]?.id ?? 0,
    );
    const [inspectToothCode, setInspectToothCode] = useState<string | null>(
        null,
    );

    const conditionMap = useMemo(() => {
        const map: Record<string, OdontogramCondition> = {};
        for (const c of conditions) {
            map[c.code] = c;
        }
        return map;
    }, [conditions]);

    const switchSnapshot = (odo: Odontogram) => {
        setActiveId(odo.id);
        setDentition(odo.dentition);
        setNotation(odo.notation);
        setFindings(odo.findings ?? []);
        setNotes(odo.notes ?? '');
        setUndoStack([]);
        setRedoStack([]);
    };

    const commitFindings = (next: OdontogramFinding[]) => {
        setUndoStack((prev) => [...prev.slice(-24), findings]);
        setRedoStack([]);
        setFindings(next);
    };

    const handleUndo = () => {
        if (undoStack.length === 0) return;
        const previous = undoStack[undoStack.length - 1];
        setRedoStack((prev) => [...prev, findings]);
        setUndoStack((prev) => prev.slice(0, -1));
        setFindings(previous);
    };

    const handleRedo = () => {
        if (redoStack.length === 0) return;
        const next = redoStack[redoStack.length - 1];
        setUndoStack((prev) => [...prev, findings]);
        setRedoStack((prev) => prev.slice(0, -1));
        setFindings(next);
    };

    const handleSelectCondition = (cond: OdontogramCondition) => {
        setEraserMode(false);
        setSelectedTool(cond.code);
        if (
            cond.default_status === 'realizado' ||
            cond.default_status === 'por_realizar'
        ) {
            setSelectedStatus(cond.default_status);
        }
        if (cond.surface_mode === 'multi') {
            setMultiMode(true);
        } else {
            setMultiMode(false);
            setMultiTeeth([]);
        }
    };

    const handleToothSurfaceClick = (
        toothCode: string,
        surface: 'O' | 'V' | 'L' | 'M' | 'D',
    ) => {
        if (!canEdit) return;

        if (eraserMode) {
            commitFindings(findings.filter((f) => f.tooth_code !== toothCode));
            return;
        }

        if (multiMode) {
            setMultiTeeth((prev) =>
                prev.includes(toothCode)
                    ? prev.filter((t) => t !== toothCode)
                    : [...prev, toothCode],
            );
            return;
        }

        const cond = conditionMap[selectedTool];
        if (!cond) return;

        if (cond.code === 'sano') {
            const next = findings.filter((f) => f.tooth_code !== toothCode);
            commitFindings([
                ...next,
                {
                    tooth_code: toothCode,
                    surface: 'FULL',
                    condition_code: 'sano',
                    status: 'sano',
                },
            ]);
            return;
        }

        const targetSurface: OdontogramFinding['surface'] =
            cond.surface_mode === 'surface' ? surface : 'FULL';
        const targetStatus: OdontogramFinding['status'] =
            cond.code === 'ausente' ? 'ausente' : selectedStatus;

        const existingIndex = findings.findIndex(
            (f) =>
                f.tooth_code === toothCode &&
                f.surface === targetSurface &&
                f.condition_code === cond.code,
        );

        if (existingIndex >= 0) {
            const copy = [...findings];
            copy.splice(existingIndex, 1);
            commitFindings(copy);
            return;
        }

        const filtered = findings.filter(
            (f) =>
                !(
                    f.tooth_code === toothCode &&
                    f.surface === targetSurface &&
                    cond.surface_mode === 'surface'
                ),
        );

        commitFindings([
            ...filtered,
            {
                tooth_code: toothCode,
                surface: targetSurface,
                condition_code: cond.code,
                status: targetStatus,
            },
        ]);
    };

    const applyMultiTeeth = () => {
        if (multiTeeth.length === 0) return;
        const cond = conditionMap[selectedTool];
        if (!cond) return;

        const status: OdontogramFinding['status'] =
            cond.code === 'ausente' ? 'ausente' : selectedStatus;
        const next = [...findings];
        for (const code of multiTeeth) {
            const exists = next.some(
                (f) =>
                    f.tooth_code === code &&
                    f.surface === 'FULL' &&
                    f.condition_code === cond.code,
            );
            if (!exists) {
                next.push({
                    tooth_code: code,
                    surface: 'FULL',
                    condition_code: cond.code,
                    status,
                    notes: `Grupo: ${multiTeeth.join(', ')}`,
                });
            }
        }
        commitFindings(next);
        setMultiTeeth([]);
        setMultiMode(false);
    };

    const handleSave = async () => {
        if (!activeOdontogram || !canEdit) return;

        if (!navigator.onLine) {
            await queueOfflineMutation(
                'odontogram.sync',
                `Odontograma #${activeOdontogram.id} (${findings.length} hallazgos)`,
                {
                    patient_id: patientId,
                    odontogram_id: activeOdontogram.id,
                    expected_version: activeOdontogram.version,
                    dentition,
                    notation,
                    notes,
                    findings,
                },
            );
            toast.success(
                'Sin conexión: odontograma guardado localmente para sincronizar al volver el internet.',
            );
            return;
        }

        setSaving(true);
        router.put(
            `/patients/${patientId}/odontograms/${activeOdontogram.id}`,
            {
                dentition,
                notation,
                notes,
                expected_version: activeOdontogram.version,
                findings,
            },
            {
                preserveScroll: true,
                onFinish: () => setSaving(false),
            },
        );
    };

    const handleCreateEvolutiveSnapshot = () => {
        if (!activeOdontogram || !canEdit) return;
        router.post(
            `/patients/${patientId}/odontograms`,
            {
                type: 'evolutivo',
                dentition,
                notation,
                notes: 'Control evolutivo',
                clone_from_id: activeOdontogram.id,
            },
            { preserveScroll: true },
        );
    };

    const handleConvertFinding = (finding: OdontogramFinding) => {
        if (!activeOdontogram || !finding.id) {
            toast.warning(
                'Guarde el odontograma antes de convertir este hallazgo en tratamiento.',
            );
            return;
        }
        router.post(
            `/patients/${patientId}/odontograms/${activeOdontogram.id}/findings/${finding.id}/convert`,
            {},
            { preserveScroll: true },
        );
    };

    const findingsByTooth = useMemo(() => {
        const grouped: Record<string, OdontogramFinding[]> = {};
        for (const f of findings) {
            grouped[f.tooth_code] = grouped[f.tooth_code] ?? [];
            grouped[f.tooth_code].push(f);
        }
        return grouped;
    }, [findings]);

    const clinicalCounters = useMemo(() => {
        const totalArch =
            dentition === 'nino' ? 20 : dentition === 'mixto' ? 52 : 32;
        const absentTeeth = new Set(
            findings
                .filter((f) => f.status === 'ausente')
                .map((f) => f.tooth_code),
        ).size;
        const todoCount = findings.filter(
            (f) => f.status === 'por_realizar',
        ).length;
        const doneCount = findings.filter(
            (f) => f.status === 'realizado',
        ).length;
        return {
            present: Math.max(0, totalArch - absentTeeth),
            absentTeeth,
            todoCount,
            doneCount,
        };
    }, [dentition, findings]);

    const compareSnapshot = odontograms.find((o) => o.id === compareId) ?? null;
    const compareFindingsByTooth = useMemo(() => {
        const grouped: Record<string, OdontogramFinding[]> = {};
        for (const f of compareSnapshot?.findings ?? []) {
            grouped[f.tooth_code] = grouped[f.tooth_code] ?? [];
            grouped[f.tooth_code].push(f);
        }
        return grouped;
    }, [compareSnapshot]);

    const renderArchRow = (
        left: string[],
        right: string[],
        mapFindings: Record<string, OdontogramFinding[]>,
        isCompact = false,
        readOnly = false,
    ) => (
        <div className="flex items-center justify-center gap-1 sm:gap-2">
            <div className="flex gap-0.5 sm:gap-1">
                {left.map((code) => (
                    <ToothSvg
                        key={code}
                        code={code}
                        notation={notation}
                        findings={mapFindings[code] ?? []}
                        conditionMap={conditionMap}
                        selectedMulti={!readOnly && multiTeeth.includes(code)}
                        onSurfaceClick={handleToothSurfaceClick}
                        onInspectTooth={(c) => setInspectToothCode(c)}
                        readOnly={readOnly || !canEdit}
                        compact={isCompact}
                    />
                ))}
            </div>
            <div className="h-20 w-0.5 bg-[#0E8FA3]/40" />
            <div className="flex gap-0.5 sm:gap-1">
                {right.map((code) => (
                    <ToothSvg
                        key={code}
                        code={code}
                        notation={notation}
                        findings={mapFindings[code] ?? []}
                        conditionMap={conditionMap}
                        selectedMulti={!readOnly && multiTeeth.includes(code)}
                        onSurfaceClick={handleToothSurfaceClick}
                        onInspectTooth={(c) => setInspectToothCode(c)}
                        readOnly={readOnly || !canEdit}
                        compact={isCompact}
                    />
                ))}
            </div>
        </div>
    );

    const inspectedFindings = inspectToothCode
        ? findings.filter((f) => f.tooth_code === inspectToothCode)
        : [];

    const updateFindingNote = (indexInTooth: number, nextNote: string) => {
        if (!inspectToothCode) return;
        let seen = -1;
        commitFindings(
            findings.map((f) => {
                if (f.tooth_code !== inspectToothCode) return f;
                seen += 1;
                return seen === indexInTooth ? { ...f, notes: nextNote } : f;
            }),
        );
    };

    const removeToothFinding = (indexInTooth: number) => {
        if (!inspectToothCode) return;
        let seen = -1;
        commitFindings(
            findings.filter((f) => {
                if (f.tooth_code !== inspectToothCode) return true;
                seen += 1;
                return seen !== indexInTooth;
            }),
        );
    };

    return (
        <div className="space-y-4">
            {/* Indicadores clínicos rápidos */}
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div className="rounded-xl border bg-card px-4 py-2.5">
                    <div className="text-[11px] font-medium text-muted-foreground">
                        Piezas presentes
                    </div>
                    <div className="text-lg font-bold text-[#0A6577]">
                        {clinicalCounters.present}
                    </div>
                </div>
                <div className="rounded-xl border bg-card px-4 py-2.5">
                    <div className="text-[11px] font-medium text-muted-foreground">
                        Patologías / Por realizar
                    </div>
                    <div className="text-lg font-bold text-[#D64545]">
                        {clinicalCounters.todoCount}
                    </div>
                </div>
                <div className="rounded-xl border bg-card px-4 py-2.5">
                    <div className="text-[11px] font-medium text-muted-foreground">
                        Tratamientos realizados
                    </div>
                    <div className="text-lg font-bold text-[#2F7FD1]">
                        {clinicalCounters.doneCount}
                    </div>
                </div>
                <div className="rounded-xl border bg-card px-4 py-2.5">
                    <div className="text-[11px] font-medium text-muted-foreground">
                        Piezas ausentes
                    </div>
                    <div className="text-lg font-bold text-muted-foreground">
                        {clinicalCounters.absentTeeth}
                    </div>
                </div>
            </div>

            {/* Barra superior de controles */}
            <div className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border bg-card p-3.5 shadow-xs">
                <div className="flex flex-wrap items-center gap-2">
                    <select
                        value={activeOdontogram?.id ?? ''}
                        onChange={(e) => {
                            const found = odontograms.find(
                                (o) => o.id === Number(e.target.value),
                            );
                            if (found) switchSnapshot(found);
                        }}
                        className="h-10 rounded-xl border bg-background px-3 text-sm font-medium"
                    >
                        {odontograms.map((o) => (
                            <option key={o.id} value={o.id}>
                                {o.type === 'inicial' ? 'Inicial' : 'Evolutivo'}{' '}
                                ·{' '}
                                {new Date(o.recorded_at).toLocaleDateString(
                                    'es-PE',
                                )}{' '}
                                ({o.findings.length})
                            </option>
                        ))}
                    </select>

                    <div className="inline-flex rounded-xl border bg-muted/40 p-0.5">
                        {(['adulto', 'nino', 'mixto'] as const).map((mode) => (
                            <button
                                key={mode}
                                type="button"
                                onClick={() => setDentition(mode)}
                                className={`rounded-lg px-3 py-1.5 text-xs font-semibold capitalize transition-colors ${
                                    dentition === mode
                                        ? 'bg-[#0E8FA3] text-white'
                                        : 'text-muted-foreground hover:text-foreground'
                                }`}
                            >
                                {mode === 'nino'
                                    ? 'Niño'
                                    : mode === 'adulto'
                                      ? 'Adulto'
                                      : 'Mixto'}
                            </button>
                        ))}
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="h-9 rounded-xl text-xs"
                        onClick={() =>
                            setNotation((n) =>
                                n === 'FDI' ? 'UNIVERSAL' : 'FDI',
                            )
                        }
                    >
                        Numeración: {notation}
                    </Button>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    {odontograms.length > 1 && (
                        <Button
                            type="button"
                            variant={compareMode ? 'default' : 'outline'}
                            size="sm"
                            className={`h-9 gap-1.5 rounded-xl text-xs ${compareMode ? 'bg-[#0A6577] text-white' : ''}`}
                            onClick={() => setCompareMode((v) => !v)}
                        >
                            <ArrowLeftRight className="size-3.5" />
                            Comparar fechas
                        </Button>
                    )}

                    {canEdit && (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="h-9 gap-1.5 rounded-xl text-xs"
                            onClick={handleCreateEvolutiveSnapshot}
                        >
                            <Plus className="size-3.5" />
                            Nuevo evolutivo
                        </Button>
                    )}

                    {activeOdontogram && (
                        <a
                            href={`/patients/${patientId}/pdf/odontogram/${activeOdontogram.id}`}
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex h-9 items-center gap-1.5 rounded-xl border bg-background px-3 text-xs font-medium hover:bg-accent"
                        >
                            <FileDown className="size-3.5" />
                            PDF Odontograma
                        </a>
                    )}

                    {canEdit && (
                        <Button
                            type="button"
                            size="sm"
                            disabled={saving}
                            onClick={() => void handleSave()}
                            className="h-9 gap-1.5 rounded-xl bg-[#0E8FA3] px-4 text-xs font-semibold text-white hover:bg-[#0A6577]"
                        >
                            <Save className="size-3.5" />
                            {saving ? 'Guardando...' : 'Guardar odontograma'}
                        </Button>
                    )}
                </div>
            </div>

            {compareMode && compareSnapshot ? (
                <div className="space-y-3 rounded-2xl border bg-card p-4 shadow-xs">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <h3 className="text-sm font-bold text-[#0A6577]">
                            Comparación lado a lado entre fechas
                        </h3>
                        <select
                            value={compareSnapshot.id}
                            onChange={(e) =>
                                setCompareId(Number(e.target.value))
                            }
                            className="h-9 rounded-xl border bg-background px-3 text-xs"
                        >
                            {odontograms.map((o) => (
                                <option key={o.id} value={o.id}>
                                    Comparar con: {o.type} (
                                    {new Date(o.recorded_at).toLocaleDateString(
                                        'es-PE',
                                    )}
                                    )
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="grid gap-4 lg:grid-cols-2">
                        <div className="overflow-x-auto rounded-xl border p-3">
                            <div className="mb-2 text-xs font-semibold text-muted-foreground">
                                Referencia: {compareSnapshot.type} ·{' '}
                                {new Date(
                                    compareSnapshot.recorded_at,
                                ).toLocaleDateString('es-PE')}
                            </div>
                            <div className="min-w-[540px] space-y-2">
                                {renderArchRow(
                                    UPPER_ADULT_RIGHT,
                                    UPPER_ADULT_LEFT,
                                    compareFindingsByTooth,
                                    true,
                                    true,
                                )}
                                {renderArchRow(
                                    LOWER_ADULT_RIGHT,
                                    LOWER_ADULT_LEFT,
                                    compareFindingsByTooth,
                                    true,
                                    true,
                                )}
                            </div>
                        </div>
                        <div className="overflow-x-auto rounded-xl border border-[#0E8FA3]/40 p-3">
                            <div className="mb-2 text-xs font-semibold text-[#0E8FA3]">
                                Actual: {activeOdontogram?.type} ·{' '}
                                {activeOdontogram
                                    ? new Date(
                                          activeOdontogram.recorded_at,
                                      ).toLocaleDateString('es-PE')
                                    : ''}
                            </div>
                            <div className="min-w-[540px] space-y-2">
                                {renderArchRow(
                                    UPPER_ADULT_RIGHT,
                                    UPPER_ADULT_LEFT,
                                    findingsByTooth,
                                    true,
                                    true,
                                )}
                                {renderArchRow(
                                    LOWER_ADULT_RIGHT,
                                    LOWER_ADULT_LEFT,
                                    findingsByTooth,
                                    true,
                                    true,
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            ) : (
                <div className="grid gap-4 lg:grid-cols-12">
                    {canEdit && (
                        <div className="space-y-3 rounded-2xl border bg-card p-3.5 shadow-xs lg:col-span-4 xl:col-span-3">
                            <div className="flex items-center justify-between">
                                <span className="text-xs font-bold tracking-wide text-muted-foreground uppercase">
                                    Hallazgos y estado
                                </span>
                                <div className="flex gap-1">
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="outline"
                                        className="size-8 rounded-lg"
                                        disabled={undoStack.length === 0}
                                        onClick={handleUndo}
                                        title="Deshacer"
                                    >
                                        <Undo2 className="size-3.5" />
                                    </Button>
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="outline"
                                        className="size-8 rounded-lg"
                                        disabled={redoStack.length === 0}
                                        onClick={handleRedo}
                                        title="Rehacer"
                                    >
                                        <Redo2 className="size-3.5" />
                                    </Button>
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant={
                                            eraserMode
                                                ? 'destructive'
                                                : 'outline'
                                        }
                                        className="size-8 rounded-lg"
                                        onClick={() => setEraserMode((v) => !v)}
                                        title="Borrar hallazgos de una pieza"
                                    >
                                        <Eraser className="size-3.5" />
                                    </Button>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-1.5 rounded-xl bg-muted/50 p-1">
                                <button
                                    type="button"
                                    onClick={() =>
                                        setSelectedStatus('por_realizar')
                                    }
                                    className={`rounded-lg py-2 text-xs font-semibold transition-colors ${
                                        selectedStatus === 'por_realizar'
                                            ? 'bg-[#D64545] text-white shadow-xs'
                                            : 'text-muted-foreground'
                                    }`}
                                >
                                    Patología / Por hacer
                                </button>
                                <button
                                    type="button"
                                    onClick={() =>
                                        setSelectedStatus('realizado')
                                    }
                                    className={`rounded-lg py-2 text-xs font-semibold transition-colors ${
                                        selectedStatus === 'realizado'
                                            ? 'bg-[#2F7FD1] text-white shadow-xs'
                                            : 'text-muted-foreground'
                                    }`}
                                >
                                    Realizado / Buen estado
                                </button>
                            </div>

                            <div className="grid max-h-80 grid-cols-2 gap-1.5 overflow-y-auto pr-1">
                                {conditions.map((cond) => {
                                    const active =
                                        !eraserMode &&
                                        selectedTool === cond.code;
                                    return (
                                        <button
                                            key={cond.code}
                                            type="button"
                                            onClick={() =>
                                                handleSelectCondition(cond)
                                            }
                                            className={`flex min-h-10 items-center justify-between gap-1.5 rounded-xl border px-2.5 py-1.5 text-left text-xs font-medium transition-all ${
                                                active
                                                    ? 'border-[#0E8FA3] bg-[#E6F5F7] text-[#0A6577] ring-1 ring-[#0E8FA3]'
                                                    : 'bg-background hover:bg-muted/40'
                                            }`}
                                        >
                                            <span className="truncate">
                                                {cond.label}
                                            </span>
                                            <span
                                                className="shrink-0 rounded px-1 text-[9px] font-bold text-white"
                                                style={{
                                                    backgroundColor:
                                                        cond.code === 'sano'
                                                            ? '#2E9E6B'
                                                            : cond.code ===
                                                                'ausente'
                                                              ? '#5F6F7C'
                                                              : selectedStatus ===
                                                                  'realizado'
                                                                ? cond.color_done
                                                                : cond.color_todo,
                                                }}
                                            >
                                                {cond.short}
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>

                            <div className="rounded-xl border border-dashed p-2.5">
                                <div className="flex items-center justify-between gap-2">
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setMultiMode((v) => !v);
                                            setMultiTeeth([]);
                                        }}
                                        className={`inline-flex items-center gap-1.5 text-xs font-semibold ${
                                            multiMode
                                                ? 'text-[#0E8FA3]'
                                                : 'text-muted-foreground'
                                        }`}
                                    >
                                        <Layers className="size-3.5" />
                                        {multiMode
                                            ? 'Modo multi-pieza activo'
                                            : 'Activar multi-pieza (Puente)'}
                                    </button>
                                    {multiMode && multiTeeth.length > 0 && (
                                        <Button
                                            type="button"
                                            size="sm"
                                            className="h-7 bg-[#0E8FA3] px-2.5 text-xs text-white"
                                            onClick={applyMultiTeeth}
                                        >
                                            <Check className="mr-1 size-3" />
                                            Aplicar ({multiTeeth.length})
                                        </Button>
                                    )}
                                </div>
                                <p className="mt-1 text-[11px] text-muted-foreground">
                                    Consejo clínico: toca el número de cualquier
                                    pieza para agregar notas específicas por
                                    hallazgo.
                                </p>
                            </div>
                        </div>
                    )}

                    <div
                        className={`space-y-4 rounded-2xl border bg-card p-4 shadow-xs ${
                            canEdit
                                ? 'lg:col-span-8 xl:col-span-9'
                                : 'lg:col-span-12'
                        }`}
                    >
                        <div className="flex flex-wrap items-center justify-between gap-2 border-b pb-2.5">
                            <div className="flex flex-wrap items-center gap-3 text-xs">
                                <span className="inline-flex items-center gap-1.5">
                                    <span className="size-3 rounded-full bg-[#2F7FD1]" />
                                    Azul = Realizado
                                </span>
                                <span className="inline-flex items-center gap-1.5">
                                    <span className="size-3 rounded-full bg-[#D64545]" />
                                    Rojo = Patología / Por hacer
                                </span>
                                <span className="inline-flex items-center gap-1.5">
                                    <span className="size-3 rounded-full bg-[#5F6F7C]" />
                                    Gris = Ausente
                                </span>
                                <span className="inline-flex items-center gap-1.5">
                                    <span className="size-3 rounded-full bg-[#2E9E6B]" />
                                    Verde = Sana
                                </span>
                            </div>

                            <div className="flex items-center gap-1">
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="outline"
                                    className="size-8 rounded-lg"
                                    onClick={() =>
                                        setZoom((z) =>
                                            Math.max(
                                                0.8,
                                                Number((z - 0.1).toFixed(2)),
                                            ),
                                        )
                                    }
                                >
                                    <ZoomOut className="size-3.5" />
                                </Button>
                                <span className="w-11 text-center text-xs font-medium">
                                    {Math.round(zoom * 100)}%
                                </span>
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="outline"
                                    className="size-8 rounded-lg"
                                    onClick={() =>
                                        setZoom((z) =>
                                            Math.min(
                                                1.4,
                                                Number((z + 0.1).toFixed(2)),
                                            ),
                                        )
                                    }
                                >
                                    <ZoomIn className="size-3.5" />
                                </Button>
                            </div>
                        </div>

                        <div className="overflow-x-auto pb-2">
                            <div
                                className="mx-auto min-w-[820px] origin-top-left space-y-4 transition-transform"
                                style={{
                                    transform: `scale(${zoom})`,
                                    width: `${100 / zoom}%`,
                                }}
                            >
                                {(dentition === 'adulto' ||
                                    dentition === 'mixto') && (
                                    <div className="space-y-3 rounded-xl bg-[#F6FAFB] p-3 dark:bg-muted/20">
                                        <div className="flex justify-between text-[11px] font-semibold text-muted-foreground">
                                            <span>
                                                Cuadrante Superior Derecho
                                            </span>
                                            <span>
                                                Arcada Permanente Superior
                                            </span>
                                            <span>
                                                Cuadrante Superior Izquierdo
                                            </span>
                                        </div>
                                        {renderArchRow(
                                            UPPER_ADULT_RIGHT,
                                            UPPER_ADULT_LEFT,
                                            findingsByTooth,
                                        )}
                                        <div className="border-t border-dashed border-[#DCE6EA]" />
                                        {renderArchRow(
                                            LOWER_ADULT_RIGHT,
                                            LOWER_ADULT_LEFT,
                                            findingsByTooth,
                                        )}
                                        <div className="flex justify-between text-[11px] font-semibold text-muted-foreground">
                                            <span>
                                                Cuadrante Inferior Derecho
                                            </span>
                                            <span>
                                                Arcada Permanente Inferior
                                            </span>
                                            <span>
                                                Cuadrante Inferior Izquierdo
                                            </span>
                                        </div>
                                    </div>
                                )}

                                {(dentition === 'nino' ||
                                    dentition === 'mixto') && (
                                    <div className="space-y-3 rounded-xl bg-[#E3F6F1]/50 p-3 dark:bg-muted/20">
                                        <div className="text-center text-[11px] font-semibold text-[#0A6577] dark:text-[#7BE0C8]">
                                            Dentición Temporal / Primaria (20
                                            piezas)
                                        </div>
                                        {renderArchRow(
                                            UPPER_CHILD_RIGHT,
                                            UPPER_CHILD_LEFT,
                                            findingsByTooth,
                                        )}
                                        <div className="border-t border-dashed border-[#DCE6EA]" />
                                        {renderArchRow(
                                            LOWER_CHILD_RIGHT,
                                            LOWER_CHILD_LEFT,
                                            findingsByTooth,
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>

                        <div>
                            <label className="mb-1 block text-xs font-semibold text-muted-foreground">
                                Observaciones del odontograma
                            </label>
                            <input
                                type="text"
                                value={notes}
                                disabled={!canEdit}
                                onChange={(e) => setNotes(e.target.value)}
                                placeholder="Notas clínicas generales de la evaluación dental..."
                                className="h-10 w-full rounded-xl border bg-background px-3 text-sm"
                            />
                        </div>
                    </div>
                </div>
            )}

            {/* Tabla de hallazgos y conversión 1-clic a plan de tratamiento */}
            <div className="rounded-2xl border bg-card p-4 shadow-xs">
                <div className="mb-3 flex items-center justify-between">
                    <h4 className="text-sm font-bold text-foreground">
                        Hallazgos registrados ({findings.length})
                    </h4>
                    <span className="text-xs text-muted-foreground">
                        Convierte cualquier hallazgo pendiente en un ítem del
                        plan de tratamiento con un clic
                    </span>
                </div>

                {findings.length === 0 ? (
                    <p className="py-4 text-center text-sm text-muted-foreground">
                        Aún no hay hallazgos marcados. Toca una cara o pieza del
                        odontograma para registrar.
                    </p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b text-xs text-muted-foreground">
                                <tr>
                                    <th className="py-2 pr-3">Pieza</th>
                                    <th className="py-2 pr-3">Cara</th>
                                    <th className="py-2 pr-3">Hallazgo</th>
                                    <th className="py-2 pr-3">Nota clínica</th>
                                    <th className="py-2 pr-3">Estado</th>
                                    <th className="py-2 pr-3 text-right">
                                        Acción
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {findings.map((f, idx) => (
                                    <tr
                                        key={`${f.tooth_code}-${f.surface}-${f.condition_code}-${idx}`}
                                    >
                                        <td className="py-2 pr-3 font-bold">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setInspectToothCode(
                                                        f.tooth_code,
                                                    )
                                                }
                                                className="text-[#0A6577] hover:underline"
                                            >
                                                {notation === 'UNIVERSAL'
                                                    ? `${UNIVERSAL_MAP[f.tooth_code] ?? f.tooth_code} (FDI ${f.tooth_code})`
                                                    : f.tooth_code}
                                            </button>
                                        </td>
                                        <td className="py-2 pr-3">
                                            {f.surface}
                                        </td>
                                        <td className="py-2 pr-3">
                                            {conditionMap[f.condition_code]
                                                ?.label ?? f.condition_code}
                                        </td>
                                        <td className="py-2 pr-3 text-xs text-muted-foreground">
                                            {f.notes || '-'}
                                        </td>
                                        <td className="py-2 pr-3">
                                            <Badge
                                                variant="outline"
                                                className={
                                                    f.status === 'realizado'
                                                        ? 'border-[#2F7FD1] bg-[#DBEAFE] text-[#1E40AF]'
                                                        : f.status === 'ausente'
                                                          ? 'border-[#5F6F7C] bg-muted text-muted-foreground'
                                                          : f.status === 'sano'
                                                            ? 'border-[#2E9E6B] bg-[#DCFCE7] text-[#166534]'
                                                            : 'border-[#D64545] bg-[#FEE2E2] text-[#991B1B]'
                                                }
                                            >
                                                {f.status.replace('_', ' ')}
                                            </Badge>
                                        </td>
                                        <td className="py-2 text-right">
                                            {canEdit &&
                                                f.status === 'por_realizar' &&
                                                !f.treatment_plan_item_id && (
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant="outline"
                                                        className="h-7 gap-1 rounded-lg text-xs text-[#0A6577]"
                                                        onClick={() =>
                                                            handleConvertFinding(
                                                                f,
                                                            )
                                                        }
                                                    >
                                                        <Sparkles className="size-3" />
                                                        A tratamiento
                                                    </Button>
                                                )}
                                            {f.treatment_plan_item_id && (
                                                <span className="text-xs font-medium text-[#2E9E6B]">
                                                    En plan de tratamiento
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {/* Modal detalle e historial específico de la pieza dental */}
            <Dialog
                open={inspectToothCode !== null}
                onOpenChange={() => setInspectToothCode(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <MessageSquareText className="size-5 text-[#0E8FA3]" />
                            Detalle de pieza dental {inspectToothCode}
                        </DialogTitle>
                    </DialogHeader>

                    {inspectedFindings.length === 0 ? (
                        <p className="py-4 text-sm text-muted-foreground">
                            Esta pieza no tiene hallazgos marcados actualmente.
                            Toca sus caras en el odontograma para registrar uno.
                        </p>
                    ) : (
                        <div className="space-y-3">
                            {inspectedFindings.map((f, idx) => (
                                <div
                                    key={idx}
                                    className="space-y-2 rounded-xl border p-3"
                                >
                                    <div className="flex items-center justify-between">
                                        <div className="text-sm font-bold">
                                            {conditionMap[f.condition_code]
                                                ?.label ??
                                                f.condition_code}{' '}
                                            · Cara {f.surface}
                                        </div>
                                        {canEdit && (
                                            <Button
                                                type="button"
                                                size="icon"
                                                variant="ghost"
                                                className="size-7 text-[#D64545]"
                                                onClick={() =>
                                                    removeToothFinding(idx)
                                                }
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        )}
                                    </div>
                                    <div>
                                        <Label className="text-xs">
                                            Nota clínica específica
                                        </Label>
                                        <Input
                                            disabled={!canEdit}
                                            value={f.notes ?? ''}
                                            onChange={(e) =>
                                                updateFindingNote(
                                                    idx,
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Ej: Caries dentinaria profunda sin compromiso pulpar / Resina A2"
                                            className="mt-1 h-9 text-xs"
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    <DialogFooter>
                        <Button
                            type="button"
                            onClick={() => setInspectToothCode(null)}
                        >
                            Cerrar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
