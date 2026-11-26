<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $document->title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1F2D3A; font-size: 11px; line-height: 1.55; margin: 24px; }
        .header { border-bottom: 3px solid {{ $clinic?->primary_color ?? '#0E8FA3' }}; padding-bottom: 10px; margin-bottom: 16px; }
        .clinic { font-size: 17px; font-weight: bold; color: {{ $clinic?->primary_color ?? '#0E8FA3' }}; }
        .doc-title { font-size: 14px; font-weight: bold; color: #0A6577; text-transform: uppercase; margin: 12px 0; text-align: center; }
        .meta-box { background: #F6FAFB; border: 1px solid #DCE6EA; padding: 8px 10px; margin-bottom: 14px; font-size: 10px; }
        .content { white-space: pre-line; text-align: justify; margin: 14px 0 24px; }
        .signatures { width: 100%; margin-top: 30px; border-collapse: collapse; }
        .signatures td { width: 50%; text-align: center; vertical-align: bottom; padding: 10px; }
        .sig-line { border-top: 1px solid #5F6F7C; margin: 8px 20px 4px; padding-top: 4px; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                @if(!empty($logoDataUri))
                    <td style="width: 72px; vertical-align: middle;">
                        <img src="{{ $logoDataUri }}" alt="Logo" style="max-height: 54px; max-width: 68px;">
                    </td>
                @endif
                <td style="vertical-align: middle;">
                    <div class="clinic">{{ $clinic?->name ?? 'Consultorio Odontológico' }}</div>
                    <div style="font-size: 10px; color: #5F6F7C;">
                        {{ $clinic?->slogan ?? '' }} ·
                        @if($clinic?->ruc) RUC: {{ $clinic->ruc }} · @endif
                        {{ $clinic?->address ?? '' }} · Tel: {{ $clinic?->phone ?? '' }}
                    </div>
                    @if(!empty($printSettings['header_text']))
                        <div style="font-size: 9.5px; color: #0A6577; font-style: italic; margin-top: 2px;">{{ $printSettings['header_text'] }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="doc-title">{{ $document->title }}</div>

    <div class="meta-box">
        <strong>Paciente:</strong> {{ $patient->full_name }} ·
        <strong>Historia N.º:</strong> {{ $patient->history_number }} ·
        <strong>{{ $patient->document_type }}:</strong> {{ $patient->document_number ?? '-' }} ·
        <strong>Fecha de emisión:</strong> {{ $document->issued_at->format('d/m/Y H:i') }}
    </div>

    <div class="content">{{ $document->content }}</div>

    <table class="signatures">
        <tr>
            <td>
                @if($document->signature_data)
                    <img src="{{ $document->signature_data }}" alt="Firma paciente" style="max-height: 75px; max-width: 220px;">
                @else
                    <div style="height: 60px;"></div>
                @endif
                <div class="sig-line">
                    <strong>{{ $document->signed_by_name ?? $patient->full_name }}</strong><br>
                    Paciente / Apoderado
                    @if($document->signed_at)
                        <br>Firmado: {{ $document->signed_at->format('d/m/Y H:i') }}
                    @endif
                </div>
            </td>
            <td>
                <div style="height: 60px;"></div>
                <div class="sig-line">
                    <strong>{{ $document->creator?->name ?? 'Cirujano Dentista' }}</strong><br>
                    {{ $document->creator?->specialty ?? 'Odontología' }}
                    @if(($printSettings['show_cop'] ?? true) && $document->creator?->license_number)
                        · {{ $document->creator->license_number }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    @if(!empty($printSettings['footer_text']))
        <div style="margin-top: 16px; border-top: 1px solid #DCE6EA; padding-top: 6px; font-size: 9px; color: #5F6F7C; text-align: center;">
            {{ $printSettings['footer_text'] }}
        </div>
    @endif
</body>
</html>
