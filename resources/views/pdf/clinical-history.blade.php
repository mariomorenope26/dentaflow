<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Historia Clínica {{ $patient->history_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1F2D3A; font-size: 11px; line-height: 1.45; margin: 20px; }
        .header { border-bottom: 3px solid {{ $clinic?->primary_color ?? '#0E8FA3' }}; padding-bottom: 10px; margin-bottom: 14px; }
        .clinic-name { font-size: 18px; font-weight: bold; color: {{ $clinic?->primary_color ?? '#0E8FA3' }}; }
        .meta { color: #5F6F7C; font-size: 10px; }
        .badge { display: inline-block; padding: 2px 7px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .alert-box { background: #FEF2F2; border: 1px solid #D64545; border-left: 5px solid #D64545; padding: 8px 10px; margin-bottom: 12px; color: #7F1D1D; }
        .section-title { background: #EBF5F7; color: #0A6577; font-weight: bold; padding: 5px 8px; margin-top: 12px; margin-bottom: 6px; border-left: 4px solid {{ $clinic?->primary_color ?? '#0E8FA3' }}; font-size: 11px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #DCE6EA; padding: 5px 7px; text-align: left; vertical-align: top; }
        th { background: #F6FAFB; color: #1F2D3A; font-weight: bold; width: 24%; }
        .evol-card { border: 1px solid #DCE6EA; padding: 8px 10px; margin-bottom: 8px; border-radius: 4px; }
        .locked { color: #2E9E6B; font-weight: bold; font-size: 9px; }
        .correction { background: #FFFBEB; border-left: 3px solid #F0A92E; margin-top: 6px; padding: 5px 8px; font-size: 10px; }
        .footer { margin-top: 18px; border-top: 1px solid #DCE6EA; padding-top: 6px; font-size: 9px; color: #5F6F7C; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%; border-collapse: collapse; margin: 0;">
            <tr>
                @if(!empty($logoDataUri))
                    <td style="border: none; padding: 0 10px 0 0; width: 72px; vertical-align: middle;">
                        <img src="{{ $logoDataUri }}" alt="Logo" style="max-height: 56px; max-width: 68px;">
                    </td>
                @endif
                <td style="border: none; padding: 0; vertical-align: middle;">
                    <div class="clinic-name">{{ $clinic?->name ?? 'Consultorio Odontológico' }}</div>
                    <div class="meta">
                        @if($clinic?->ruc) RUC: {{ $clinic->ruc }} · @endif
                        @if($clinic?->address) {{ $clinic->address }} · @endif
                        @if($clinic?->phone) Tel: {{ $clinic->phone }} @endif
                    </div>
                    @if(!empty($printSettings['header_text']))
                        <div class="meta" style="color: #0A6577; font-style: italic; margin-top: 2px;">{{ $printSettings['header_text'] }}</div>
                    @endif
                    <div style="margin-top: 6px; font-size: 13px; font-weight: bold;">
                        HISTORIA CLÍNICA ESTOMATOLÓGICA ELECTRÓNICA · N.º {{ $patient->history_number }}
                    </div>
                    <div class="meta">
                        Estándar Internacional HCE: HL7 FHIR R4 · ISO 13606 · ISO 3950 (FDI) · CIE-10
                        @if($patient->fhir_uuid) · UUID: {{ $patient->fhir_uuid }} @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @if(!empty($history?->medical_alerts) || !empty($history?->allergies))
        <div class="alert-box">
            <strong>ALERTAS MÉDICAS IMPORTANTES:</strong>
            @if(!empty($history?->allergies))
                <div>• Alergias: {{ $history->allergies }}</div>
            @endif
            @foreach(($history?->medical_alerts ?? []) as $alert)
                <div>• {{ $alert['label'] ?? '' }} ({{ strtoupper($alert['severity'] ?? 'alta') }})</div>
            @endforeach
        </div>
    @endif

    <div class="section-title">1. Filiación del Paciente</div>
    <table>
        <tr>
            <th>Nombre completo</th>
            <td>{{ $patient->full_name }}</td>
            <th>Documento</th>
            <td>{{ $patient->document_type }} {{ $patient->document_number ?? '-' }}</td>
        </tr>
        <tr>
            <th>Fecha de nacimiento / Edad</th>
            <td>{{ $patient->birth_date?->format('d/m/Y') ?? '-' }} ({{ $patient->age !== null ? $patient->age.' años' : '-' }})</td>
            <th>Teléfono / Correo</th>
            <td>{{ $patient->phone ?? '-' }} · {{ $patient->email ?? '-' }}</td>
        </tr>
        <tr>
            <th>Dirección</th>
            <td>{{ $patient->address ?? '-' }}</td>
            <th>Seguro / Ocupación</th>
            <td>{{ $patient->insurance_provider ?? 'Particular' }} · {{ $patient->occupation ?? '-' }}</td>
        </tr>
        <tr>
            <th>Grupo Sanguíneo / Rh</th>
            <td>{{ $patient->blood_type ?? 'No registrado' }}</td>
            <th>Contacto de Emergencia</th>
            <td>{{ $patient->emergency_contact_name ?? '-' }} {{ $patient->emergency_contact_phone ? '('.$patient->emergency_contact_phone.')' : '' }}</td>
        </tr>
        @if($patient->guardian_name)
        <tr>
            <th>Apoderado</th>
            <td colspan="3">{{ $patient->guardian_name }} ({{ $patient->guardian_relationship ?? 'Apoderado' }}) · Tel: {{ $patient->guardian_phone ?? '-' }}</td>
        </tr>
        @endif
    </table>

    <div class="section-title">2. Anamnesis y Antecedentes</div>
    <table>
        <tr>
            <th>Motivo de consulta</th>
            <td>{{ $history?->chief_complaint ?? 'No registrado' }}</td>
        </tr>
        <tr>
            <th>Enfermedad actual</th>
            <td>{{ $history?->current_illness ?? '-' }}</td>
        </tr>
        <tr>
            <th>Antecedentes médicos</th>
            <td>{{ $history?->medical_history_notes ?? '-' }}</td>
        </tr>
        <tr>
            <th>Medicación actual</th>
            <td>{{ $history?->current_medications ?? '-' }}</td>
        </tr>
        <tr>
            <th>Antecedentes odontológicos / Familiares</th>
            <td>{{ $history?->dental_history_notes ?? '-' }} @if($history?->family_history) | Familiares: {{ $history->family_history }} @endif</td>
        </tr>
        <tr>
            <th>Hábitos / Gestación / Clasificación ASA</th>
            <td>{{ !empty($history?->habits) ? implode(', ', $history->habits) : 'Ninguno reportado' }} · Gestación: {{ $history?->is_pregnant ? 'Sí' : 'No' }} · Riesgo: <strong>{{ $history?->asa_classification ?? 'No clasificado' }}</strong></td>
        </tr>
    </table>

    <div class="section-title">3. Examen Clínico y Diagnóstico</div>
    <table>
        <tr>
            <th>Signos vitales y Antropometría</th>
            <td>
                PA: {{ $history?->vital_signs['pa'] ?? '-' }} ·
                FC: {{ $history?->vital_signs['fc'] ?? '-' }} ·
                FR: {{ $history?->vital_signs['fr'] ?? '-' }} ·
                Temp: {{ $history?->vital_signs['temp'] ?? '-' }} ·
                SpO₂: {{ $history?->vital_signs['sato2'] ?? '-' }}
                @if(!empty($history?->vital_signs['weight_kg']) || !empty($history?->vital_signs['height_cm']))
                    · Peso: {{ $history?->vital_signs['weight_kg'] ?? '-' }} kg · Talla: {{ $history?->vital_signs['height_cm'] ?? '-' }} cm (IMC: {{ $history?->vital_signs['bmi'] ?? '-' }})
                @endif
            </td>
        </tr>
        <tr>
            <th>Examen extraoral / ATM</th>
            <td>{{ $history?->extraoral_exam ?? '-' }} | ATM: {{ $history?->tmj_exam ?? '-' }}</td>
        </tr>
        <tr>
            <th>Examen intraoral y tejidos blandos</th>
            <td>{{ $history?->intraoral_exam ?? '-' }} | Tejidos: {{ $history?->soft_tissues_exam ?? '-' }}</td>
        </tr>
        <tr>
            <th>Periodonto y oclusión</th>
            <td>{{ $history?->periodontal_exam ?? '-' }} | Oclusión: {{ $history?->occlusion_exam ?? '-' }}</td>
        </tr>
        <tr>
            <th>Diagnóstico presuntivo</th>
            <td>{{ $history?->presumptive_diagnosis ?? '-' }}</td>
        </tr>
        <tr>
            <th>Diagnóstico definitivo (CIE-10)</th>
            <td><strong>{{ $history?->cie10_code ? '['.$history->cie10_code.'] ' : '' }}</strong>{{ $history?->definitive_diagnosis ?? '-' }}</td>
        </tr>
    </table>

    @if($latestOdontogram && $latestOdontogram->findings->count() > 0)
        <div class="section-title">4. Resumen del Último Odontograma ({{ ucfirst($latestOdontogram->type) }} · {{ $latestOdontogram->recorded_at->format('d/m/Y') }})</div>
        <table>
            <tr>
                <th style="width: 12%;">Pieza</th>
                <th style="width: 12%;">Cara</th>
                <th style="width: 30%;">Hallazgo</th>
                <th style="width: 18%;">Estado</th>
                <th>Observaciones</th>
            </tr>
            @foreach($latestOdontogram->findings as $finding)
                <tr>
                    <td>{{ $finding->tooth_code }}</td>
                    <td>{{ $finding->surface }}</td>
                    <td>{{ $conditionsMap[$finding->condition_code]['label'] ?? $finding->condition_code }}</td>
                    <td>{{ strtoupper(str_replace('_', ' ', $finding->status)) }}</td>
                    <td>{{ $finding->notes ?? '-' }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <div class="section-title">5. Evolución y Atenciones Clínicas</div>
    @forelse($evolutions->whereNull('parent_evolution_id') as $evol)
        <div class="evol-card">
            <div style="display: flex; justify-content: space-between;">
                <strong>{{ $evol->attended_at->format('d/m/Y H:i') }} — {{ $evol->procedure_summary }}</strong>
                <span class="locked">
                    @if($evol->signed_at)
                        FIRMADO Y BLOQUEADO ({{ $evol->signed_at->format('d/m/Y H:i') }} · {{ $evol->signer?->name }})
                    @else
                        BORRADOR
                    @endif
                </span>
            </div>
            <div style="margin-top: 4px;">{{ $evol->clinical_notes }}</div>
            @if($evol->soap_subjective || $evol->soap_objective || $evol->soap_assessment || $evol->soap_plan)
                <div style="margin-top: 4px; font-size: 10px; background: #F8FAFC; padding: 4px 6px; border-left: 3px solid #0E8FA3;">
                    @if($evol->soap_subjective)<div><strong>S (Subjetivo):</strong> {{ $evol->soap_subjective }}</div>@endif
                    @if($evol->soap_objective)<div><strong>O (Objetivo):</strong> {{ $evol->soap_objective }}</div>@endif
                    @if($evol->soap_assessment)<div><strong>A (Análisis {{ $evol->cie10_code ? '['.$evol->cie10_code.']' : '' }}):</strong> {{ $evol->soap_assessment }}</div>@endif
                    @if($evol->soap_plan)<div><strong>P (Plan):</strong> {{ $evol->soap_plan }}</div>@endif
                </div>
            @endif
            @if($evol->prescription_notes)
                <div style="margin-top: 4px; color: #0A6577;"><strong>Indicaciones / Receta:</strong> {{ $evol->prescription_notes }}</div>
            @endif
            <div class="meta" style="margin-top: 3px;">
                Profesional: {{ $evol->author?->name ?? '-' }} {{ ($printSettings['show_cop'] ?? true) && $evol->author?->license_number ? '('.$evol->author->license_number.')' : '' }}
                @if($evol->signature_hash) · Hash HCE: {{ substr($evol->signature_hash, 0, 16) }} @endif
            </div>

            @foreach($evol->corrections as $corr)
                <div class="correction">
                    <strong>Nota de corrección ({{ $corr->attended_at->format('d/m/Y H:i') }} · {{ $corr->author?->name }}):</strong>
                    <div>Motivo: {{ $corr->correction_reason }}</div>
                    <div>{{ $corr->clinical_notes }}</div>
                </div>
            @endforeach
        </div>
    @empty
        <p class="meta">Sin notas de evolución registradas aún.</p>
    @endforelse

    <div class="footer">
        @if(!empty($printSettings['footer_text']))
            <div style="margin-bottom: 3px; text-align: left;">{{ $printSettings['footer_text'] }}</div>
        @endif
        Generado por DentaFlow · {{ $generatedAt->format('d/m/Y H:i') }} · Documento clínico confidencial (Ley N.º 29733)
    </div>
</body>
</html>
