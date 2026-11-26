<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Odontograma {{ $patient->history_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1F2D3A; font-size: 10px; margin: 16px; }
        .header { border-bottom: 3px solid {{ $clinic?->primary_color ?? '#0E8FA3' }}; padding-bottom: 8px; margin-bottom: 12px; }
        .title { font-size: 16px; font-weight: bold; color: {{ $clinic?->primary_color ?? '#0E8FA3' }}; }
        .arch-title { font-weight: bold; color: #0A6577; margin: 8px 0 4px; font-size: 11px; }
        .tooth-grid { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .tooth-cell { border: 1px solid #DCE6EA; text-align: center; padding: 4px 2px; width: 6.25%; vertical-align: top; background: #FFFFFF; }
        .tooth-num { font-weight: bold; font-size: 11px; color: #1F2D3A; margin-bottom: 3px; }
        .tooth-box { display: inline-block; width: 22px; height: 22px; border: 1.5px solid #5F6F7C; border-radius: 3px; line-height: 22px; font-size: 8px; font-weight: bold; }
        .badge-todo { background: #FEE2E2; color: #D64545; border-color: #D64545; }
        .badge-done { background: #DBEAFE; color: #2F7FD1; border-color: #2F7FD1; }
        .badge-ausente { background: #E5E7EB; color: #5F6F7C; border-color: #5F6F7C; }
        .badge-sano { background: #DCFCE7; color: #2E9E6B; border-color: #2E9E6B; }
        .legend { margin: 8px 0; font-size: 9px; color: #5F6F7C; }
        .findings-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .findings-table th, .findings-table td { border: 1px solid #DCE6EA; padding: 4px 6px; text-align: left; }
        .findings-table th { background: #F6FAFB; color: #0A6577; }
    </style>
</head>
<body>
    @php
        $byTooth = $odontogram->findings->groupBy('tooth_code');
        $upperAdult = ['18','17','16','15','14','13','12','11','21','22','23','24','25','26','27','28'];
        $lowerAdult = ['48','47','46','45','44','43','42','41','31','32','33','34','35','36','37','38'];
        $upperChild = ['55','54','53','52','51','61','62','63','64','65'];
        $lowerChild = ['85','84','83','82','81','71','72','73','74','75'];
    @endphp

    <div class="header">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                @if(!empty($logoDataUri))
                    <td style="width: 64px; vertical-align: middle;">
                        <img src="{{ $logoDataUri }}" alt="Logo" style="max-height: 48px; max-width: 60px;">
                    </td>
                @endif
                <td style="vertical-align: middle;">
                    <div class="title">{{ $clinic?->name ?? 'Consultorio Odontológico' }} — ODONTOGRAMA {{ strtoupper($odontogram->type) }}</div>
                    @if(!empty($printSettings['header_text']))
                        <div style="font-size: 9px; color: #0A6577; font-style: italic;">{{ $printSettings['header_text'] }}</div>
                    @endif
                    <div>
                        <strong>Paciente:</strong> {{ $patient->full_name }} ({{ $patient->history_number }}) ·
                        <strong>Dentición:</strong> {{ ucfirst($odontogram->dentition) }} ·
                        <strong>Numeración:</strong> {{ $odontogram->notation }} ·
                        <strong>Fecha:</strong> {{ $odontogram->recorded_at->format('d/m/Y H:i') }} ·
                        <strong>Profesional:</strong> {{ $odontogram->creator?->name ?? '-' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="legend">
        <strong>Convención visual:</strong>
        [AZUL] Tratamiento realizado / buen estado ·
        [ROJO] Patología o tratamiento por realizar ·
        [GRIS] Pieza ausente ·
        [VERDE] Pieza sana
    </div>

    @if(in_array($odontogram->dentition, ['adulto', 'mixto'], true))
        <div class="arch-title">Arcada Permanente (Superior e Inferior)</div>
        <table class="tooth-grid">
            <tr>
                @foreach($upperAdult as $code)
                    @php
                        $f = $byTooth->get($code)?->first();
                        $cls = $f ? ($f->status === 'ausente' ? 'badge-ausente' : ($f->status === 'realizado' ? 'badge-done' : ($f->status === 'sano' ? 'badge-sano' : 'badge-todo'))) : '';
                        $short = $f ? ($conditionsMap[$f->condition_code]['short'] ?? substr($f->condition_code, 0, 3)) : '';
                    @endphp
                    <td class="tooth-cell">
                        <div class="tooth-num">{{ $odontogram->notation === 'UNIVERSAL' ? \App\Services\OdontogramCatalog::toUniversal($code) : $code }}</div>
                        <div class="tooth-box {{ $cls }}">{{ $short }}</div>
                    </td>
                @endforeach
            </tr>
            <tr>
                @foreach($lowerAdult as $code)
                    @php
                        $f = $byTooth->get($code)?->first();
                        $cls = $f ? ($f->status === 'ausente' ? 'badge-ausente' : ($f->status === 'realizado' ? 'badge-done' : ($f->status === 'sano' ? 'badge-sano' : 'badge-todo'))) : '';
                        $short = $f ? ($conditionsMap[$f->condition_code]['short'] ?? substr($f->condition_code, 0, 3)) : '';
                    @endphp
                    <td class="tooth-cell">
                        <div class="tooth-box {{ $cls }}">{{ $short }}</div>
                        <div class="tooth-num">{{ $odontogram->notation === 'UNIVERSAL' ? \App\Services\OdontogramCatalog::toUniversal($code) : $code }}</div>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    @if(in_array($odontogram->dentition, ['nino', 'mixto'], true))
        <div class="arch-title">Arcada Temporal / Primaria</div>
        <table class="tooth-grid">
            <tr>
                @foreach($upperChild as $code)
                    @php
                        $f = $byTooth->get($code)?->first();
                        $cls = $f ? ($f->status === 'ausente' ? 'badge-ausente' : ($f->status === 'realizado' ? 'badge-done' : ($f->status === 'sano' ? 'badge-sano' : 'badge-todo'))) : '';
                        $short = $f ? ($conditionsMap[$f->condition_code]['short'] ?? substr($f->condition_code, 0, 3)) : '';
                    @endphp
                    <td class="tooth-cell">
                        <div class="tooth-num">{{ $odontogram->notation === 'UNIVERSAL' ? \App\Services\OdontogramCatalog::toUniversal($code) : $code }}</div>
                        <div class="tooth-box {{ $cls }}">{{ $short }}</div>
                    </td>
                @endforeach
            </tr>
            <tr>
                @foreach($lowerChild as $code)
                    @php
                        $f = $byTooth->get($code)?->first();
                        $cls = $f ? ($f->status === 'ausente' ? 'badge-ausente' : ($f->status === 'realizado' ? 'badge-done' : ($f->status === 'sano' ? 'badge-sano' : 'badge-todo'))) : '';
                        $short = $f ? ($conditionsMap[$f->condition_code]['short'] ?? substr($f->condition_code, 0, 3)) : '';
                    @endphp
                    <td class="tooth-cell">
                        <div class="tooth-box {{ $cls }}">{{ $short }}</div>
                        <div class="tooth-num">{{ $odontogram->notation === 'UNIVERSAL' ? \App\Services\OdontogramCatalog::toUniversal($code) : $code }}</div>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    <div class="arch-title">Detalle de Hallazgos Registrados ({{ $odontogram->findings->count() }})</div>
    <table class="findings-table">
        <tr>
            <th>Pieza (FDI)</th>
            <th>Cara</th>
            <th>Condición / Hallazgo</th>
            <th>Estado</th>
            <th>Notas</th>
        </tr>
        @forelse($odontogram->findings as $finding)
            <tr>
                <td>{{ $finding->tooth_code }}</td>
                <td>{{ $finding->surface }}</td>
                <td>{{ $conditionsMap[$finding->condition_code]['label'] ?? $finding->condition_code }}</td>
                <td>{{ strtoupper(str_replace('_', ' ', $finding->status)) }}</td>
                <td>{{ $finding->notes ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5">Sin hallazgos patológicos ni restauraciones registradas.</td>
            </tr>
        @endforelse
    </table>

    <div style="margin-top: 10px; font-size: 9px; color: #5F6F7C;">
        @if(!empty($printSettings['footer_text']))
            <div>{{ $printSettings['footer_text'] }}</div>
        @endif
        Generado por DentaFlow · {{ $generatedAt->format('d/m/Y H:i') }}
    </div>
</body>
</html>
