<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ticket {{ $payment->receipt_number }}</title>
    <style>
        @page { margin: {{ $widthMm === '58mm' ? '8px 6px' : '12px 10px' }}; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: {{ $widthMm === '58mm' ? '7.5px' : '8.8px' }};
            line-height: 1.35;
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
        }
        .clinic-meta { font-size: {{ $widthMm === '58mm' ? '6.8px' : '7.6px' }}; color: #4B5563; }
        .dash { border-top: 1px dashed #374151; margin: 6px 0; }
        .doc-box {
            border: 1px solid #111827;
            border-radius: 3px;
            padding: 4px;
            text-align: center;
            margin: 5px 0;
            background: #F9FAFB;
        }
        .void-banner {
            background: #DC2626;
            color: #FFFFFF;
            font-weight: bold;
            text-align: center;
            padding: 3px;
            margin-bottom: 5px;
            border-radius: 3px;
        }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        .total-box {
            border-top: 1.5px solid #111827;
            border-bottom: 1.5px solid #111827;
            padding: 5px 0;
            margin: 6px 0;
        }
        .footer {
            margin-top: 6px;
            text-align: center;
            font-size: {{ $widthMm === '58mm' ? '6.5px' : '7.2px' }};
            color: #4B5563;
        }
    </style>
</head>
<body>
    @php
        $curr = $clinic?->currency_symbol ?? 'S/';
        $docLabel = match ($payment->document_type) {
            'boleta' => 'BOLETA DE VENTA',
            'factura' => 'FACTURA ELECTRÓNICA',
            default => 'RECIBO DE PAGO',
        };
        $methodLabel = match ($payment->payment_method) {
            'yape_plin' => 'Yape / Plin',
            'tarjeta' => 'Tarjeta POS',
            'transferencia' => 'Transferencia Bancaria',
            default => 'Efectivo',
        };
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
            <div class="clinic-meta" style="font-style: italic; margin-top: 2px;">{{ $printSettings['header_text'] }}</div>
        @endif
    </div>

    <div class="doc-box">
        <div class="bold">{{ $docLabel }}</div>
        <div class="bold mono" style="font-size: {{ $widthMm === '58mm' ? '9.5px' : '11px' }};">N.º {{ $payment->receipt_number }}</div>
    </div>

    @if($payment->status === 'anulado')
        <div class="void-banner">*** COMPROBANTE ANULADO ***</div>
    @endif

    <table>
        <tr>
            <td class="muted">Fecha:</td>
            <td class="right">{{ $payment->paid_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td class="muted">Paciente:</td>
            <td class="right bold">{{ $payment->customer_name ?: ($patient?->full_name ?? '-') }}</td>
        </tr>
        <tr>
            <td class="muted">Historia / Doc:</td>
            <td class="right mono">{{ $patient?->history_number ?? '-' }} · {{ $payment->customer_doc_number ?: ($patient?->document_number ?? '-') }}</td>
        </tr>
        <tr>
            <td class="muted">Medio de pago:</td>
            <td class="right bold">{{ $methodLabel }}</td>
        </tr>
        <tr>
            <td class="muted">Atendido por:</td>
            <td class="right">{{ $payment->receiver?->name ?? 'Caja' }}</td>
        </tr>
    </table>

    <div class="dash"></div>
    <div class="muted" style="font-size: 7.2px;">CONCEPTO / TRATAMIENTO</div>
    <div class="bold" style="margin: 2px 0 4px;">{{ $payment->concept }}</div>
    @if($payment->notes)
        <div class="muted" style="font-size: 7.2px;">Obs: {{ $payment->notes }}</div>
    @endif

    <div class="total-box">
        <table>
            @if($payment->document_type !== 'recibo')
                <tr>
                    <td class="muted" style="font-size: 7.2px;">OP. GRAVADA</td>
                    <td class="right mono" style="font-size: 7.5px;">{{ $curr }} {{ number_format((float) $payment->tax_subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td class="muted" style="font-size: 7.2px;">I.G.V. (18%)</td>
                    <td class="right mono" style="font-size: 7.5px;">{{ $curr }} {{ number_format((float) $payment->tax_igv, 2) }}</td>
                </tr>
            @endif
            <tr>
                <td class="bold" style="font-size: {{ $widthMm === '58mm' ? '8.5px' : '10px' }};">TOTAL PAGADO</td>
                <td class="right bold mono" style="font-size: {{ $widthMm === '58mm' ? '11px' : '13px' }};">
                    {{ $curr }} {{ number_format((float) $payment->amount, 2) }}
                </td>
            </tr>
        </table>
    </div>

    @if(!empty($sunatQrDataUri))
        <div class="center" style="margin: 5px 0;">
            <img src="{{ $sunatQrDataUri }}" alt="QR SUNAT" style="width: 78px; height: 78px;">
            <div class="muted" style="font-size: 6.4px;">Representación impresa UBL 2.1 · Hash: {{ \Illuminate\Support\Str::limit($payment->sunat_hash ?? '', 16, '') }}</div>
        </div>
    @endif

    <div class="footer">
        @if(!empty($printSettings['footer_text']))
            <div>{{ $printSettings['footer_text'] }}</div>
        @endif
        <div style="margin-top: 2px;">Código #{{ $payment->id }} · Impreso {{ $printedAt->format('d/m/Y H:i') }}</div>
    </div>
</body>
</html>
