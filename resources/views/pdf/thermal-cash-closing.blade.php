<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cierre de Caja {{ $date->format('d/m/Y') }}</title>
    <style>
        @page { margin: {{ $widthMm === '58mm' ? '8px 6px' : '12px 10px' }}; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: {{ $widthMm === '58mm' ? '7.5px' : '8.8px' }};
            line-height: 1.32;
            margin: 0;
            padding: 0;
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .muted { color: #4B5563; }
        .mono { font-family: DejaVu Sans Mono, monospace; }
        .logo-wrap { text-align: center; margin-bottom: 4px; }
        .logo-wrap img { max-height: {{ $widthMm === '58mm' ? '30px' : '42px' }}; max-width: 110px; }
        .clinic-name {
            font-size: {{ $widthMm === '58mm' ? '10px' : '12px' }};
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #0F172A;
        }
        .clinic-meta { font-size: {{ $widthMm === '58mm' ? '6.8px' : '7.6px' }}; color: #4B5563; margin-top: 1px; }
        .header-note {
            margin-top: 3px;
            font-size: {{ $widthMm === '58mm' ? '6.8px' : '7.4px' }};
            color: #374151;
            font-style: italic;
        }
        .dash { border-top: 1px dashed #374151; margin: 6px 0; }
        .solid { border-top: 1.5px solid #111827; margin: 6px 0; }
        .banner {
            background: #111827;
            color: #FFFFFF;
            text-align: center;
            padding: 4px 6px;
            border-radius: 3px;
            margin: 5px 0;
        }
        .banner-title { font-size: {{ $widthMm === '58mm' ? '8.2px' : '9.5px' }}; font-weight: bold; letter-spacing: 0.4px; }
        .banner-sub { font-size: {{ $widthMm === '58mm' ? '6.8px' : '7.5px' }}; opacity: 0.9; margin-top: 1px; }
        .section-head {
            font-size: {{ $widthMm === '58mm' ? '7.2px' : '8px' }};
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            background: #F3F4F6;
            border-left: 3px solid {{ $clinic?->primary_color ?? '#0E8FA3' }};
            padding: 2px 5px;
            margin: 6px 0 4px;
            color: #111827;
        }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 2px 1px; vertical-align: top; }
        .kv td:first-child { color: #374151; }
        .kv td:last-child { text-align: right; font-weight: bold; }
        .method-row td { padding-top: 3px; padding-bottom: 1px; }
        .bar-track {
            width: 100%;
            height: 3.5px;
            background: #E5E7EB;
            border-radius: 2px;
            margin-bottom: 3px;
        }
        .bar-fill {
            height: 3.5px;
            background: {{ $clinic?->primary_color ?? '#0E8FA3' }};
            border-radius: 2px;
        }
        .total-box {
            border: 1.5px solid #111827;
            background: #F9FAFB;
            border-radius: 4px;
            padding: 5px 6px;
            margin: 6px 0;
        }
        .grand-label { font-size: {{ $widthMm === '58mm' ? '8px' : '9px' }}; font-weight: bold; color: #374151; }
        .grand-val { font-size: {{ $widthMm === '58mm' ? '11.5px' : '13.5px' }}; font-weight: bold; color: #0F172A; }
        .tx-table th {
            font-size: {{ $widthMm === '58mm' ? '6.5px' : '7.2px' }};
            text-align: left;
            border-bottom: 1px solid #9CA3AF;
            color: #374151;
            padding-bottom: 2px;
        }
        .tx-table td {
            font-size: {{ $widthMm === '58mm' ? '6.7px' : '7.4px' }};
            border-bottom: 1px dotted #D1D5DB;
            padding: 2.5px 1px;
        }
        .voided { text-decoration: line-through; color: #6B7280; }
        .sig-grid { width: 100%; margin-top: 14px; }
        .sig-cell { width: 50%; text-align: center; padding: 0 4px; }
        .sig-line {
            border-top: 1px solid #374151;
            margin-top: 16px;
            padding-top: 2px;
            font-size: {{ $widthMm === '58mm' ? '6.5px' : '7.2px' }};
            color: #374151;
        }
        .footer {
            margin-top: 7px;
            text-align: center;
            font-size: {{ $widthMm === '58mm' ? '6.5px' : '7.2px' }};
            color: #4B5563;
        }
    </style>
</head>
<body>
    @php
        $curr = $clinic?->currency_symbol ?? 'S/';
    @endphp

    @if(!empty($logoDataUri))
        <div class="logo-wrap">
            <img src="{{ $logoDataUri }}" alt="Logo">
        </div>
    @endif

    <div class="center">
        <div class="clinic-name">{{ $clinic?->name ?? 'Consultorio Odontológico' }}</div>
        @if($clinic?->ruc)
            <div class="clinic-meta">RUC: {{ $clinic->ruc }}</div>
        @endif
        @if($clinic?->address || $clinic?->phone)
            <div class="clinic-meta">
                {{ $clinic?->address }}@if($clinic?->address && $clinic?->phone) · @endif{{ $clinic?->phone ? 'Tel: '.$clinic->phone : '' }}
            </div>
        @endif
        @if(!empty($printSettings['header_text']))
            <div class="header-note">{{ $printSettings['header_text'] }}</div>
        @endif
    </div>

    <div class="banner">
        <div class="banner-title">
            {{ $closing ? 'REPORTE DE CIERRE DE CAJA' : 'ARQUEO DE CAJA DEL DÍA' }}
        </div>
        <div class="banner-sub">
            {{ $date->format('d/m/Y') }} ·
            {{ $closing ? 'CERRADO (N.º '.str_pad((string) $closing->id, 4, '0', STR_PAD_LEFT).')' : 'PARCIAL EN CURSO' }}
        </div>
    </div>

    <table class="kv">
        <tr>
            <td>Fecha operativa</td>
            <td>{{ $date->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td>Responsable</td>
            <td>{{ $closing?->closer?->name ?? $printedBy }}</td>
        </tr>
        <tr>
            <td>{{ $closing ? 'Hora de cierre' : 'Hora de corte' }}</td>
            <td>{{ ($closing?->closed_at ?? $printedAt)->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td>Operaciones válidas</td>
            <td>{{ $activeCount }} @if($voidedCount > 0)({{ $voidedCount }} anul.)@endif</td>
        </tr>
    </table>

    <div class="section-head">Resumen por Medios de Pago</div>
    <table>
        @foreach($methodBreakdown as $row)
            <tr class="method-row">
                <td class="bold">{{ $row['label'] }}</td>
                <td class="center muted">{{ $row['count'] }} op. ({{ number_format($row['percentage'], 0) }}%)</td>
                <td class="right bold mono">{{ $curr }} {{ number_format($row['amount'], 2) }}</td>
            </tr>
            <tr>
                <td colspan="3" style="padding: 0 1px 2px;">
                    <div class="bar-track">
                        <div class="bar-fill" style="width: {{ max(2, min(100, $row['percentage'])) }}%;"></div>
                    </div>
                </td>
            </tr>
        @endforeach
    </table>

    <div class="total-box">
        <table class="kv">
            <tr>
                <td>Fondo inicial / Apertura</td>
                <td class="mono">{{ $curr }} {{ number_format($openingAmount, 2) }}</td>
            </tr>
            <tr>
                <td>Efectivo neto en gaveta</td>
                <td class="mono">{{ $curr }} {{ number_format($cashInDrawer, 2) }}</td>
            </tr>
            <tr>
                <td>Otros medios (Yape/Tarj/Transf)</td>
                <td class="mono">{{ $curr }} {{ number_format($grandTotal - ($cashInDrawer - $openingAmount), 2) }}</td>
            </tr>
            @if($voidedCount > 0)
                <tr>
                    <td class="muted">Anulados ({{ $voidedCount }})</td>
                    <td class="muted mono">- {{ $curr }} {{ number_format($voidedTotal, 2) }}</td>
                </tr>
            @endif
        </table>
        <div class="solid"></div>
        <table>
            <tr>
                <td class="grand-label">RECAUDADO DÍA</td>
                <td class="right grand-val mono">{{ $curr }} {{ number_format($grandTotal, 2) }}</td>
            </tr>
            <tr>
                <td class="muted" style="font-size: 7.5px;">TOTAL CON APERTURA</td>
                <td class="right bold mono" style="font-size: 8.8px;">{{ $curr }} {{ number_format($expectedTotal, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="section-head">Comprobantes Emitidos</div>
    <table class="kv">
        @foreach($docBreakdown as $doc)
            <tr>
                <td>{{ $doc['label'] }} ({{ $doc['count'] }})</td>
                <td class="mono">{{ $curr }} {{ number_format($doc['amount'], 2) }}</td>
            </tr>
        @endforeach
    </table>

    @if($payments->count() > 0)
        <div class="section-head">Detalle de Movimientos ({{ $payments->count() }})</div>
        <table class="tx-table">
            <thead>
                <tr>
                    <th>Hora / Comp.</th>
                    <th>Paciente / Medio</th>
                    <th class="right">Importe</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $p)
                    @php
                        $isVoid = $p->status === 'anulado';
                        $methodShort = match ($p->payment_method) {
                            'yape_plin' => 'Yape/Plin',
                            'tarjeta' => 'Tarjeta',
                            'transferencia' => 'Transf.',
                            default => 'Efectivo',
                        };
                    @endphp
                    <tr class="{{ $isVoid ? 'voided' : '' }}">
                        <td class="mono">
                            {{ $p->paid_at->format('H:i') }}<br>
                            {{ $p->receipt_number }}
                        </td>
                        <td>
                            {{ \Illuminate\Support\Str::limit($p->patient?->full_name ?? '-', $widthMm === '58mm' ? 16 : 24) }}<br>
                            <span class="muted">{{ $methodShort }}@if($isVoid) · ANULADO @endif</span>
                        </td>
                        <td class="right bold mono">{{ $curr }} {{ number_format((float) $p->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="dash"></div>
        <div class="center muted" style="padding: 4px 0;">Sin movimientos registrados en esta fecha.</div>
    @endif

    @if($closing?->notes)
        <div class="dash"></div>
        <div style="font-size: 7.5px;">
            <span class="bold">Observaciones:</span> {{ $closing->notes }}
        </div>
    @endif

    <table class="sig-grid">
        <tr>
            <td class="sig-cell">
                <div class="sig-line">Cajero / Recepción</div>
            </td>
            <td class="sig-cell">
                <div class="sig-line">V.° B.° Dirección</div>
            </td>
        </tr>
    </table>

    <div class="dash"></div>
    <div class="footer">
        @if(!empty($printSettings['footer_text']))
            <div>{{ $printSettings['footer_text'] }}</div>
        @endif
        <div style="margin-top: 2px;">Impreso: {{ $printedAt->format('d/m/Y H:i') }} · DentaFlow ({{ $widthMm }})</div>
    </div>
</body>
</html>
