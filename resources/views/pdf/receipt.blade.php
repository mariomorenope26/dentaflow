<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Comprobante {{ $payment->receipt_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1F2D3A; font-size: 11px; margin: 16px; }
        .box { border: 2px solid {{ $clinic?->primary_color ?? '#0E8FA3' }}; border-radius: 8px; padding: 14px; }
        .top { display: flex; justify-content: space-between; border-bottom: 1px solid #DCE6EA; padding-bottom: 8px; margin-bottom: 10px; }
        .clinic { font-size: 16px; font-weight: bold; color: {{ $clinic?->primary_color ?? '#0E8FA3' }}; }
        .num { font-size: 14px; font-weight: bold; color: #0A6577; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #DCE6EA; padding: 6px 8px; text-align: left; }
        th { background: #F6FAFB; color: #0A6577; width: 25%; }
        .total { font-size: 15px; font-weight: bold; text-align: right; color: #0A6577; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="top">
            <div style="display: flex; align-items: center; gap: 10px;">
                @if(!empty($logoDataUri))
                    <img src="{{ $logoDataUri }}" alt="Logo" style="max-height: 44px; max-width: 90px; object-fit: contain;">
                @endif
                <div>
                    <div class="clinic">{{ $clinic?->name ?? 'Consultorio Odontológico' }}</div>
                    <div style="font-size: 10px; color: #5F6F7C;">
                        @if($clinic?->ruc) RUC: {{ $clinic->ruc }} · @endif
                        {{ $clinic?->address ?? '' }} · {{ $clinic?->phone ?? '' }}
                    </div>
                    @if(!empty($printSettings['header_text']))
                        <div style="font-size: 9px; color: #0A6577; font-style: italic;">{{ $printSettings['header_text'] }}</div>
                    @endif
                </div>
            </div>
            <div class="num">
                {{ strtoupper($payment->document_type) }} N.º {{ $payment->receipt_number }}
            </div>
        </div>

        <table>
            <tr>
                <th>Paciente</th>
                <td>{{ $patient?->full_name }} ({{ $patient?->history_number }})</td>
                <th>Fecha</th>
                <td>{{ $payment->paid_at->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <th>Concepto</th>
                <td>{{ $payment->concept }}</td>
                <th>Medio de pago</th>
                <td>{{ strtoupper(str_replace('_', ' / ', $payment->payment_method)) }}</td>
            </tr>
            <tr>
                <th>Estado / SUNAT</th>
                <td>{{ strtoupper($payment->status) }} · {{ $payment->sunat_series ? $payment->sunat_series.'-'.$payment->sunat_correlative : $payment->receipt_number }}</td>
                <th>Atendido por</th>
                <td>{{ $payment->receiver?->name ?? '-' }}</td>
            </tr>
            @if($payment->customer_doc_number)
            <tr>
                <th>Cliente / Doc.</th>
                <td colspan="3">{{ $payment->customer_name ?? $patient?->full_name }} ({{ $payment->customer_doc_number }}) @if($payment->customer_address) · {{ $payment->customer_address }} @endif</td>
            </tr>
            @endif
        </table>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
            <div style="font-size: 9px; color: #5F6F7C;">
                @if($payment->document_type !== 'recibo')
                    <div><strong>Op. Gravada:</strong> {{ $clinic?->currency_symbol ?? 'S/' }} {{ number_format((float) $payment->tax_subtotal, 2) }} · <strong>IGV (18%):</strong> {{ $clinic?->currency_symbol ?? 'S/' }} {{ number_format((float) $payment->tax_igv, 2) }}</div>
                    @if($payment->sunat_hash)
                        <div>Resumen UBL 2.1: {{ $payment->sunat_hash }}</div>
                    @endif
                @endif
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="total" style="margin-top: 0;">
                    TOTAL PAGADO: {{ $clinic?->currency_symbol ?? 'S/' }} {{ number_format((float) $payment->amount, 2) }}
                </div>
                @if(!empty($sunatQrDataUri))
                    <img src="{{ $sunatQrDataUri }}" alt="QR SUNAT" style="width: 64px; height: 64px; border: 1px solid #DCE6EA; padding: 2px;">
                @endif
            </div>
        </div>
        @if(!empty($printSettings['footer_text']))
            <div style="margin-top: 8px; padding-top: 6px; border-top: 1px dashed #DCE6EA; text-align: center; font-size: 9px; color: #5F6F7C;">
                {{ $printSettings['footer_text'] }}
            </div>
        @endif
    </div>
</body>
</html>
