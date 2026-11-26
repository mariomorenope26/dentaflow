<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Manual Oficial de Usuario · DentaFlow</title>
    <style>
        @page {
            margin: 18mm 14mm 18mm 14mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1F2D3A;
            font-size: 10.5px;
            line-height: 1.5;
            margin: 0;
        }
        .page-break {
            page-break-after: always;
        }
        .running-footer {
            position: fixed;
            bottom: -11mm;
            left: 0;
            right: 0;
            border-top: 1px solid #DCE6EA;
            padding-top: 4px;
            font-size: 8.5px;
            color: #5F6F7C;
        }
        .running-footer table {
            width: 100%;
            border-collapse: collapse;
        }
        .running-footer td {
            border: none;
            padding: 0;
        }
        .page-num:after {
            content: "Pág. " counter(page);
        }

        /* PORTADA */
        .cover {
            background: #062E39;
            color: #FFFFFF;
            padding: 36px 32px;
            border-radius: 14px;
            border-bottom: 6px solid #0E8FA3;
            margin-bottom: 18px;
        }
        .cover-badge {
            display: inline-block;
            background: #0E8FA3;
            color: #FFFFFF;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 14px;
        }
        .cover h1 {
            font-size: 26px;
            line-height: 1.2;
            margin: 0 0 10px 0;
            color: #FFFFFF;
        }
        .cover p {
            font-size: 12px;
            color: #D7F0F4;
            margin: 0 0 18px 0;
        }
        .std-bar {
            background: #094352;
            border: 1px solid #1C6C7D;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 9.5px;
            color: #99E9DF;
            font-weight: bold;
        }
        .meta-box {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }
        .meta-box th, .meta-box td {
            border: 1px solid #DCE6EA;
            padding: 8px 10px;
            font-size: 10px;
            text-align: left;
        }
        .meta-box th {
            background: #F6FAFB;
            color: #062E39;
            width: 30%;
        }

        /* TABLAS E ÍNDICE */
        .section-heading {
            font-size: 14px;
            font-weight: bold;
            color: #062E39;
            border-left: 4px solid #0E8FA3;
            background: #EBF5F7;
            padding: 7px 10px;
            margin: 14px 0 10px 0;
        }
        table.clean {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        table.clean th, table.clean td {
            border: 1px solid #DCE6EA;
            padding: 5px 7px;
            font-size: 9px;
            text-align: left;
            vertical-align: top;
        }
        table.clean th {
            background: #062E39;
            color: #FFFFFF;
            font-weight: bold;
        }
        table.clean tr:nth-child(even) td {
            background: #F9FCFD;
        }

        /* CAPÍTULOS */
        .chapter-header {
            border-bottom: 2px solid #0E8FA3;
            padding-bottom: 6px;
            margin-top: 14px;
            margin-bottom: 8px;
            page-break-after: avoid;
        }
        .chapter-num {
            display: inline-block;
            background: #0E8FA3;
            color: #FFFFFF;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            margin-right: 6px;
        }
        .chapter-cat {
            font-size: 9px;
            color: #5F6F7C;
            text-transform: uppercase;
            font-weight: bold;
        }
        .chapter-title {
            font-size: 15px;
            font-weight: bold;
            color: #062E39;
            margin: 5px 0 3px 0;
        }
        .roles-line {
            font-size: 9px;
            color: #0A6577;
            font-weight: bold;
        }
        .steps-box {
            margin: 8px 0 10px 0;
        }
        .step-row {
            margin-bottom: 5px;
            padding: 5px 8px;
            background: #F6FAFB;
            border: 1px solid #E3EDF1;
            border-radius: 5px;
            font-size: 9.8px;
        }
        .step-num {
            display: inline-block;
            background: #062E39;
            color: #FFFFFF;
            font-weight: bold;
            font-size: 8.5px;
            padding: 1px 5px;
            border-radius: 4px;
            margin-right: 6px;
        }
        .tip-box {
            background: #E6F5F7;
            border: 1px solid #B7E2E9;
            border-left: 4px solid #0E8FA3;
            padding: 7px 10px;
            border-radius: 4px;
            font-size: 9.5px;
            color: #062E39;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
        .figure-box {
            border: 1px solid #DCE6EA;
            border-radius: 6px;
            padding: 6px;
            background: #FFFFFF;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
        .figure-box img {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 4px;
            border: 1px solid #E5EEF2;
        }
        .figure-cap {
            font-size: 8.8px;
            color: #5F6F7C;
            margin-top: 5px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="running-footer">
        <table>
            <tr>
                <td style="width:38%; text-align:left;">DentaFlow HCE · {{ $clinic?->name ?? 'Consultorio Odontológico' }}</td>
                <td style="width:44%; text-align:center;">Desarrollado por <strong>Grupo JMSPERU 2026</strong> :: Contacto 959199368</td>
                <td style="width:18%; text-align:right;"><span class="page-num"></span></td>
            </tr>
        </table>
    </div>

    {{-- PORTADA --}}
    <div class="cover">
        <table style="width:100%; border-collapse:collapse; margin-bottom:16px;">
            <tr>
                @if(!empty($logoDataUri))
                    <td style="border:none; width:74px; padding:0 12px 0 0; vertical-align:middle;">
                        <img src="{{ $logoDataUri }}" alt="Logo" style="max-height:60px; max-width:68px; background:#fff; padding:4px; border-radius:8px;">
                    </td>
                @endif
                <td style="border:none; padding:0; vertical-align:middle;">
                    <div style="font-size:15px; font-weight:bold; color:#5EEAD4;">{{ $clinic?->name ?? 'DentaFlow Consultorio Odontológico' }}</div>
                    <div style="font-size:10px; color:#CFECEE;">{{ $clinic?->slogan ?? 'Sistema Clínico Odontológico Integral' }}</div>
                </td>
            </tr>
        </table>

        <div class="cover-badge">{{ $manualVersion }}</div>
        <h1>Manual Oficial de Usuario y Operación Clínica</h1>
        <p>
            Guía completa ilustrada con capturas reales del sistema para el manejo clínico homologado, Copiloto Clínico IA (Pre-Consulta, SOAP por Voz, Rayos X IA y Reportes IA), Contralor IA, CRM WhatsApp, Reserva Online 24/7, facturación SUNAT UBL 2.1 y bioseguridad en consultorio odontológico único.
        </p>

        <div class="std-bar">
            HOMOLOGACIÓN: HL7 FHIR R4 · ISO/EN 13606 HCE · ISO 3950 (FDI) · CIE-10 OMS · SUNAT UBL 2.1 · LEY N.º 29733 · SUITE IA &amp; RESERVA 24/7
        </div>
    </div>

    <table class="meta-box">
        <tr>
            <th>Consultorio</th>
            <td>{{ $clinic?->name ?? 'Consultorio Odontológico' }} @if($clinic?->ruc) · RUC: {{ $clinic->ruc }} @endif</td>
        </tr>
        <tr>
            <th>Dirección / Contacto</th>
            <td>{{ $clinic?->address ?? 'Consultorio único' }} @if($clinic?->phone) · Tel: {{ $clinic->phone }} @endif</td>
        </tr>
        <tr>
            <th>Alcance del Manual</th>
            <td>{{ count($chapters) }} capítulos operativos · Incluye Copiloto IA, Contralor IA, CRM WhatsApp y Reserva Online 24/7 · Capturas reales</td>
        </tr>
        <tr>
            <th>Generado por</th>
            <td>{{ $generatedBy }} · {{ $generatedAt->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <th>Soporte Técnico Oficial</th>
            <td>Grupo JMSPERU 2026 :: WhatsApp +51 959199368 (https://wa.me/51959199368)</td>
        </tr>
    </table>

    <div class="page-break"></div>

    {{-- ÍNDICE Y MATRIZ DE ROLES --}}
    <div class="section-heading">Índice General de Contenidos</div>
    <table class="clean">
        <thead>
            <tr>
                <th style="width:8%;">Cap.</th>
                <th style="width:44%;">Módulo / Tema</th>
                <th style="width:24%;">Categoría</th>
                <th style="width:24%;">Roles Autorizados</th>
            </tr>
        </thead>
        <tbody>
            @foreach($chapters as $ch)
                <tr>
                    <td><strong>{{ $ch['number'] }}</strong></td>
                    <td><strong>{{ $ch['title'] }}</strong></td>
                    <td>{{ $ch['category'] }}</td>
                    <td>{{ implode(', ', $ch['roles']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-heading">Matriz Oficial de Permisos por Rol</div>
    <table class="clean">
        <thead>
            <tr>
                <th style="width:28%;">Módulo / Funcionalidad</th>
                <th style="width:18%;">Administrador</th>
                <th style="width:18%;">Odontólogo</th>
                <th style="width:18%;">Asistente / Recepción</th>
                <th style="width:18%;">Contador / Caja</th>
            </tr>
        </thead>
        <tbody>
            @foreach($roleMatrix as $row)
                <tr>
                    <td><strong>{{ $row['module'] }}</strong></td>
                    <td>{{ $row['admin'] }}</td>
                    <td>{{ $row['dentist'] }}</td>
                    <td>{{ $row['assistant'] }}</td>
                    <td>{{ $row['accountant'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-break"></div>

    {{-- CAPÍTULOS ILUSTRADOS --}}
    @foreach($chapters as $index => $chapter)
        <div class="chapter-header">
            <div>
                <span class="chapter-num">CAPÍTULO {{ $chapter['number'] }}</span>
                <span class="chapter-cat">{{ $chapter['category'] }}</span>
            </div>
            <div class="chapter-title">{{ $chapter['title'] }}</div>
            <div class="roles-line">Roles autorizados: {{ implode(' · ', $chapter['roles']) }}</div>
        </div>

        <p style="margin: 0 0 8px 0;">{{ $chapter['summary'] }}</p>

        <div class="steps-box">
            @foreach($chapter['steps'] as $stepIdx => $stepText)
                <div class="step-row">
                    <span class="step-num">{{ $stepIdx + 1 }}</span>{{ $stepText }}
                </div>
            @endforeach
        </div>

        <div class="tip-box">
            <strong>Recomendación clave:</strong> {{ $chapter['tip'] }}
        </div>

        @foreach($chapter['screenshots'] as $figIdx => $shot)
            @if(!empty($shot['data_uri']))
                <div class="figure-box">
                    <img src="{{ $shot['data_uri'] }}" alt="{{ $shot['caption'] }}">
                    <div class="figure-cap">
                        <strong>Figura {{ $chapter['number'] }}.{{ $figIdx + 1 }}:</strong> {{ $shot['caption'] }}
                    </div>
                </div>
            @endif
        @endforeach

        @if($index < count($chapters) - 1)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>
