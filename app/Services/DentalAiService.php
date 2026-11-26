<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Clinic;
use App\Models\OdontogramFinding;
use App\Models\Patient;
use App\Models\PatientImage;
use App\Models\TreatmentPlan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DentalAiService
{
    /**
     * Genera un resumen clínico ejecutivo pre-consulta (estilo Dentalink Clinical Summary).
     *
     * @return array{
     *     provider: string,
     *     risk_level: string,
     *     headline: string,
     *     bullets: list<array{label: string, detail: string, tone: string}>,
     *     recommended_next_step: string
     * }
     */
    public static function summarizePatient(Patient $patient, ?Clinic $clinic): array
    {
        $patient->loadMissing([
            'medicalHistory',
            'odontograms.findings',
            'clinicalEvolutions.author:id,name',
            'treatmentPlans.items',
            'payments',
            'labOrders',
            'paymentInstallments',
            'appointments',
        ]);

        $history = $patient->medicalHistory;
        $allergies = trim((string) ($history !== null ? ($history->allergies ?? '') : ''));
        $asa = $history !== null && $history->asa_classification ? $history->asa_classification : 'ASA I';
        $chronic = $history !== null ? ($history->chronic_conditions ?? []) : [];
        $medAlerts = $history !== null ? ($history->medical_alerts ?? []) : [];

        $hasHighRisk = $allergies !== ''
            || $medAlerts !== []
            || in_array($asa, ['ASA III', 'ASA IV', 'ASA V'], true)
            || ($history !== null && $history->is_pregnant);

        $riskLevel = $hasHighRisk ? 'alto' : ($chronic !== [] || $asa === 'ASA II' ? 'medio' : 'bajo');

        $bullets = [];

        if ($allergies !== '' || $medAlerts !== [] || $history?->is_pregnant) {
            $parts = [];
            if ($allergies !== '') {
                $parts[] = "Alergias: {$allergies}";
            }
            if ($history?->is_pregnant) {
                $parts[] = 'Paciente gestante (ajustar anestésico/Rx)';
            }
            foreach ($medAlerts as $a) {
                if (! empty($a['label'])) {
                    $parts[] = (string) $a['label'];
                }
            }
            $bullets[] = [
                'label' => 'Alerta Médica y Riesgo Anestésico',
                'detail' => "{$asa} · ".implode(' · ', $parts),
                'tone' => 'danger',
            ];
        } else {
            $bullets[] = [
                'label' => 'Estado Sistémico',
                'detail' => "Clasificación {$asa}. Sin alergias ni alertas médicas críticas registradas.",
                'tone' => 'success',
            ];
        }

        $latestOdo = $patient->odontograms->first();
        $pendingFindings = $latestOdo !== null
            ? $latestOdo->findings->where('status', 'por_realizar')->values()
            : collect();

        if ($pendingFindings->isNotEmpty()) {
            $descList = $pendingFindings
                ->take(6)
                ->map(fn (OdontogramFinding $f): string => "Pieza {$f->tooth_code} ({$f->condition_code})")
                ->implode(', ');
            $bullets[] = [
                'label' => "Hallazgos Dentales Pendientes ({$pendingFindings->count()})",
                'detail' => $descList,
                'tone' => 'warning',
            ];
        } else {
            $bullets[] = [
                'label' => 'Odontograma',
                'detail' => $latestOdo !== null
                    ? 'Sin hallazgos patológicos pendientes en el último odontograma.'
                    : 'Aún no cuenta con odontograma registrado.',
                'tone' => 'info',
            ];
        }

        $lastEvol = $patient->clinicalEvolutions->first();
        $bullets[] = [
            'label' => 'Última Atención Clínica',
            'detail' => $lastEvol !== null
                ? $lastEvol->attended_at->format('d/m/Y').": {$lastEvol->procedure_summary}".($lastEvol->cie10_code ? " ({$lastEvol->cie10_code})" : '')
                : 'Sin evoluciones registradas hasta la fecha.',
            'tone' => 'info',
        ];

        $budgeted = $patient->treatmentPlans
            ->where('status', '!=', 'cancelado')
            ->sum(function (TreatmentPlan $plan): float {
                $sub = $plan->items->sum(fn ($it): float => ((float) $it->unit_price * $it->quantity) - (float) $it->discount);

                return max(0.0, $sub - (float) $plan->discount_amount);
            });
        $paid = (float) $patient->payments->where('status', 'registrado')->sum('amount');
        $balance = round(max(0.0, $budgeted - $paid), 2);
        $currency = $clinic !== null ? $clinic->currency_symbol : 'S/';

        $overdueCount = $patient->paymentInstallments
            ->whereIn('status', ['pendiente', 'parcial'])
            ->filter(fn ($i): bool => $i->due_date->isPast())
            ->count();

        $urgentLab = $patient->labOrders
            ->whereIn('status', ['por_enviar', 'en_laboratorio', 'rehacer'])
            ->count();

        $bullets[] = [
            'label' => 'Situación Financiera y Laboratorio',
            'detail' => "Presupuestado: {$currency} ".number_format($budgeted, 2)
                ." · Saldo pendiente: {$currency} ".number_format($balance, 2)
                .($overdueCount > 0 ? " · {$overdueCount} cuota(s) vencida(s)" : '')
                .($urgentLab > 0 ? " · {$urgentLab} orden(es) de laboratorio activa(s)" : ''),
            'tone' => ($overdueCount > 0 || $urgentLab > 0) ? 'warning' : 'info',
        ];

        $nextStep = $pendingFindings->isNotEmpty()
            ? 'Priorizar el tratamiento de '.$pendingFindings->first()->condition_code.' en pieza '.$pendingFindings->first()->tooth_code.' y validar consentimiento firmado.'
            : ($balance > 0 ? "Realizar seguimiento financiero del saldo pendiente ({$currency} ".number_format($balance, 2).') y control clínico.' : 'Programar control preventivo periódico y profilaxis según protocolo.');

        $headline = sprintf(
            '%s (%s) · %s · %d hallazgo(s) pendiente(s)',
            $patient->full_name,
            $patient->age !== null ? "{$patient->age} años" : 'Edad N/R',
            $asa,
            $pendingFindings->count(),
        );

        $provider = 'local_ai';

        // Si el consultorio configuró Gemini u OpenAI, enriquecemos el resumen clínico ejecutivo
        if ($clinic !== null && self::hasExternalProvider($clinic)) {
            $contextJson = json_encode([
                'patient' => $patient->only(['history_number', 'gender', 'blood_type', 'age']),
                'risk_level' => $riskLevel,
                'bullets' => $bullets,
                'recommended_next_step' => $nextStep,
            ], JSON_UNESCAPED_UNICODE);

            $llmText = self::callExternalText(
                $clinic,
                'Eres un odontólogo clínico senior asistente en español. Resume en máximo 2 oraciones claras y accionables el siguiente perfil pre-consulta sin inventar datos clínicos nuevos. Responde solo texto plano profesional.',
                "Contexto clínico del paciente:\n{$contextJson}",
            );

            if ($llmText !== null) {
                $headline = $llmText;
                $provider = $clinic->ai_provider;
            }
        }

        return [
            'provider' => $provider,
            'risk_level' => $riskLevel,
            'headline' => $headline,
            'bullets' => $bullets,
            'recommended_next_step' => $nextStep,
        ];
    }

    /**
     * Convierte dictado por voz o nota libre en estructura SOAP + CIE-10 (Clinical Copilot).
     *
     * @return array{
     *     provider: string,
     *     procedure_summary: string,
     *     clinical_notes: string,
     *     soap_subjective: string,
     *     soap_objective: string,
     *     soap_assessment: string,
     *     soap_plan: string,
     *     prescription_notes: string,
     *     cie10_code: string
     * }
     */
    public static function parseDictationToSoap(string $transcript, ?Clinic $clinic): array
    {
        $clean = trim($transcript);

        if ($clinic !== null && self::hasExternalProvider($clinic)) {
            $llmJson = self::callExternalJson(
                $clinic,
                'Eres un copiloto odontológico que estructura dictados clínicos al estándar HCE SOAP en español. Devuelve EXCLUSIVAMENTE un objeto JSON válido con estas claves exactas de tipo string: procedure_summary, clinical_notes, soap_subjective, soap_objective, soap_assessment, soap_plan, prescription_notes, cie10_code. Sugiere el código CIE-10 odontológico más apropiado (K00-K14) si aplica, o vacío si no se deduce.',
                "Dictado clínico del odontólogo:\n\"{$clean}\"",
            );

            if ($llmJson !== null && isset($llmJson['procedure_summary'])) {
                return [
                    'provider' => $clinic->ai_provider,
                    'procedure_summary' => (string) $llmJson['procedure_summary'],
                    'clinical_notes' => (string) ($llmJson['clinical_notes'] ?? $clean),
                    'soap_subjective' => (string) ($llmJson['soap_subjective'] ?? ''),
                    'soap_objective' => (string) ($llmJson['soap_objective'] ?? ''),
                    'soap_assessment' => (string) ($llmJson['soap_assessment'] ?? ''),
                    'soap_plan' => (string) ($llmJson['soap_plan'] ?? ''),
                    'prescription_notes' => (string) ($llmJson['prescription_notes'] ?? ''),
                    'cie10_code' => (string) ($llmJson['cie10_code'] ?? ''),
                ];
            }
        }

        // Motor clínico determinístico en español para dictados odontológicos
        $lower = mb_strtolower($clean);
        $sentences = preg_split('/(?<=[.;])\s+|\n+/u', $clean) ?: [$clean];
        $sentences = array_values(array_filter(array_map('trim', $sentences), fn (string $s): bool => $s !== ''));

        $subj = [];
        $obj = [];
        $assess = [];
        $plan = [];
        $rx = [];

        foreach ($sentences as $sentence) {
            $sl = mb_strtolower($sentence);
            if (preg_match('/(refiere|dolor|molestia|sensibilidad|acude|motivo|hace \d+|sangrado al cepillar)/u', $sl) === 1) {
                $subj[] = $sentence;
            } elseif (preg_match('/(examen|clínic|observa|palpa|percusión|vitalidad|sondaje|radiograf|sin fístula|mucosa|filtrad|movilidad)/u', $sl) === 1) {
                $obj[] = $sentence;
            } elseif (preg_match('/(diagnóstic|pulpitis|caries|periodontitis|gingivitis|absceso|fractura|necrosis|impactad|k\d{2})/u', $sl) === 1) {
                $assess[] = $sentence;
            } elseif (preg_match('/(receta|amoxicilina|ibuprofeno|paracetamol|azitromicina|clindamicina|enjuague|clorhexidina|cada \d+ horas|indicaciones)/u', $sl) === 1) {
                $rx[] = $sentence;
            } else {
                $plan[] = $sentence;
            }
        }

        $cie10 = match (true) {
            preg_match('/pulpitis/u', $lower) === 1 => 'K04.0',
            preg_match('/necrosis pulpar/u', $lower) === 1 => 'K04.1',
            preg_match('/absceso|fístula|periapical/u', $lower) === 1 => 'K04.7',
            preg_match('/caries/u', $lower) === 1 => 'K02.1',
            preg_match('/gingivitis/u', $lower) === 1 => 'K05.1',
            preg_match('/periodontitis/u', $lower) === 1 => 'K05.3',
            preg_match('/impactad|retenid|tercera molar/u', $lower) === 1 => 'K01.1',
            preg_match('/exodoncia|extracción|edentul/u', $lower) === 1 => 'K08.1',
            default => '',
        };

        $toothMatch = '';
        if (preg_match('/pieza\s*(\d{2})/u', $lower, $m) === 1) {
            $toothMatch = ' pieza '.$m[1];
        }

        $procedure = match (true) {
            preg_match('/endodoncia|conducto|pulpectom/u', $lower) === 1 => "Atención endodóntica{$toothMatch}",
            preg_match('/resina|restauración|obturación|ionómero|protección pulpar/u', $lower) === 1 => "Restauración dental{$toothMatch}",
            preg_match('/exodoncia|extracción/u', $lower) === 1 => "Exodoncia{$toothMatch}",
            preg_match('/profilaxis|destartraje|limpieza|periodon/u', $lower) === 1 => 'Terapia periodontal / Profilaxis',
            preg_match('/corona|prótesis|incrustación/u', $lower) === 1 => "Rehabilitación protésica{$toothMatch}",
            default => mb_substr($sentences[0] ?? $clean, 0, 90),
        };

        return [
            'provider' => 'local_ai',
            'procedure_summary' => $procedure,
            'clinical_notes' => $clean,
            'soap_subjective' => $subj !== [] ? implode(' ', $subj) : ($sentences[0] ?? ''),
            'soap_objective' => $obj !== [] ? implode(' ', $obj) : 'Evaluación clínica intraoral y verificación radiográfica.',
            'soap_assessment' => $assess !== [] ? implode(' ', $assess) : ($cie10 !== '' ? "Impresión diagnóstica compatible con {$cie10}." : 'Evaluación estomatológica en curso.'),
            'soap_plan' => $plan !== [] ? implode(' ', $plan) : 'Control evolutivo programado según evolución clínica.',
            'prescription_notes' => implode(' ', $rx),
            'cie10_code' => $cie10,
        ];
    }

    /**
     * Asistente de pre-evaluación radiográfica (Dentalink Rayos X IA).
     *
     * @return array{
     *     provider: string,
     *     summary: string,
     *     disclaimer: string,
     *     findings: list<array{label: string, confidence: int, detail: string, x: float, y: float}>
     * }
     */
    public static function analyzeRadiograph(PatientImage $image, Patient $patient, ?Clinic $clinic): array
    {
        $patient->loadMissing(['odontograms.findings', 'medicalHistory']);
        $tooth = $image->tooth_code;

        $toothFindings = collect();
        $latestOdo = $patient->odontograms->first();
        if ($latestOdo !== null && $tooth !== null && $tooth !== '') {
            $toothFindings = $latestOdo->findings->where('tooth_code', $tooth)->values();
        }

        if ($clinic !== null && self::hasExternalProvider($clinic)) {
            $bytes = null;
            $mime = 'image/jpeg';
            foreach (['local', 'public'] as $disk) {
                if (Storage::disk($disk)->exists($image->file_path)) {
                    $bytes = Storage::disk($disk)->get($image->file_path);
                    $mime = Storage::disk($disk)->mimeType($image->file_path) ?: 'image/jpeg';
                    break;
                }
            }

            if (is_string($bytes) && $bytes !== '' && $clinic->ai_provider === 'gemini') {
                $llmRx = self::callGeminiVisionJson(
                    (string) $clinic->ai_api_key,
                    $bytes,
                    $mime,
                    'Analiza esta radiografía dental en español como apoyo diagnóstico para un odontólogo. Devuelve SOLO JSON válido con: {"summary":"resumen breve","findings":[{"label":"hallazgo corto","confidence":85,"detail":"observación clínica","x":500,"y":350}]}. Usa coordenadas x entre 100-900 e y entre 100-600.',
                );
                if ($llmRx !== null && isset($llmRx['summary']) && is_array($llmRx['findings'] ?? null)) {
                    $normalized = [];
                    foreach ($llmRx['findings'] as $idx => $item) {
                        if (! is_array($item)) {
                            continue;
                        }
                        $normalized[] = [
                            'label' => (string) ($item['label'] ?? 'Hallazgo Rx '.($idx + 1)),
                            'confidence' => max(50, min(99, (int) ($item['confidence'] ?? 80))),
                            'detail' => (string) ($item['detail'] ?? ''),
                            'x' => max(80.0, min(920.0, (float) ($item['x'] ?? 500))),
                            'y' => max(80.0, min(620.0, (float) ($item['y'] ?? 320))),
                        ];
                    }

                    $result = [
                        'provider' => 'gemini_vision',
                        'summary' => (string) $llmRx['summary'],
                        'disclaimer' => 'Apoyo visual generado por IA. El diagnóstico definitivo corresponde exclusivamente al cirujano dentista.',
                        'findings' => $normalized,
                    ];

                    $image->update(['ai_analysis' => $result]);

                    return $result;
                }
            }
        }

        // Análisis contextual inteligente usando pieza, historial radiográfico y hallazgos clínicos
        $findings = [];
        foreach ($toothFindings as $idx => $finding) {
            $findings[] = [
                'label' => ucfirst($finding->condition_code)." Pieza {$tooth}",
                'confidence' => 92,
                'detail' => $finding->notes ?: "Correlación con hallazgo registrado ({$finding->status}).",
                'x' => 420.0 + ($idx * 95.0),
                'y' => 235.0 + ($idx * 45.0),
            ];
        }

        if ($findings === []) {
            $findings[] = [
                'label' => $tooth ? "Revisión coronal/periapical Pieza {$tooth}" : 'Evaluación de soporte óseo y ápices',
                'confidence' => 84,
                'detail' => 'Sugerido revisar integridad coronal, espacio del ligamento periodontal y cresta ósea alveolar con la regla milimétrica.',
                'x' => 490.0,
                'y' => 290.0,
            ];
            $findings[] = [
                'label' => 'Control de conductometría / proporción corono-radicular',
                'confidence' => 79,
                'detail' => 'Calibra una distancia de referencia conocida en mm antes de medir la longitud de trabajo radicular.',
                'x' => 520.0,
                'y' => 440.0,
            ];
        }

        $result = [
            'provider' => 'local_ai',
            'summary' => $tooth
                ? "Pre-análisis contextual para Pieza {$tooth} ({$image->category}): se detectaron ".count($findings).' puntos de verificación radiográfica.'
                : 'Pre-análisis contextual completado: marca los pines sugeridos o calibra la imagen en milímetros para mediciones exactas.',
            'disclaimer' => 'Asistencia inteligente de apoyo diagnóstico. Validar siempre frente a radiografía clínica y examen intraoral.',
            'findings' => $findings,
        ];

        $image->update(['ai_analysis' => $result]);

        return $result;
    }

    /**
     * Analítica conversacional para Reportes (Dentalink Reportes IA).
     *
     * @param  array<string, mixed>  $reportData
     * @return array{provider: string, answer: string, highlights: list<string>}
     */
    public static function answerReportQuestion(string $question, array $reportData, string $currency, ?Clinic $clinic): array
    {
        $summary = is_array($reportData['summary'] ?? null) ? $reportData['summary'] : [];
        $methods = is_array($reportData['incomeByMethod'] ?? null) ? $reportData['incomeByMethod'] : [];
        $commissions = is_array($reportData['dentistCommissions'] ?? null) ? $reportData['dentistCommissions'] : [];
        $topProcedures = is_array($reportData['topProcedures'] ?? null) ? $reportData['topProcedures'] : [];
        $pendingBalances = is_array($reportData['pendingBalances'] ?? null) ? $reportData['pendingBalances'] : [];

        $totalIncome = number_format((float) ($summary['total_income'] ?? 0), 2);
        $debtTotal = number_format((float) ($summary['pending_debt_total'] ?? 0), 2);
        $noShowRate = (float) ($summary['no_show_rate'] ?? 0);
        $attended = (int) ($summary['appointments_attended'] ?? 0);
        $newPatients = (int) ($summary['new_patients'] ?? 0);

        $topMethod = collect($methods)->sortByDesc('total')->first();
        $topMethodText = is_array($topMethod)
            ? "{$topMethod['method']} ({$currency} ".number_format((float) $topMethod['total'], 2).')'
            : 'Sin pagos registrados';

        $topProcText = isset($topProcedures[0]['description'])
            ? "{$topProcedures[0]['description']} ({$topProcedures[0]['total_count']}x)"
            : 'Sin procedimientos registrados';

        $highlights = [
            "Ingresos del periodo: {$currency} {$totalIncome}",
            "Medio de pago líder: {$topMethodText}",
            "Cuentas por cobrar: {$currency} {$debtTotal} (".count($pendingBalances).' pacientes)',
            "Tasa de inasistencia: {$noShowRate}% ({$attended} citas atendidas)",
        ];

        if ($clinic !== null && self::hasExternalProvider($clinic)) {
            $jsonContext = json_encode($reportData, JSON_UNESCAPED_UNICODE);
            $llmAnswer = self::callExternalText(
                $clinic,
                "Eres un analista financiero y clínico dental en español. Responde con precisión usando SOLO las cifras entregadas (moneda {$currency}). Sé concreto, claro y ejecutivo (máximo 4 líneas).",
                "Pregunta del usuario: {$question}\n\nDatos reales del periodo:\n{$jsonContext}",
            );
            if ($llmAnswer !== null) {
                return [
                    'provider' => $clinic->ai_provider,
                    'answer' => $llmAnswer,
                    'highlights' => $highlights,
                ];
            }
        }

        $q = mb_strtolower($question);
        $answer = match (true) {
            preg_match('/ingreso|recaud|caja|efectivo|yape|plin|tarjeta|medio|pago/u', $q) === 1 => "En el periodo seleccionado se recaudaron {$currency} {$totalIncome} en {$summary['payments_count']} operaciones. El medio de pago con mayor ingreso fue {$topMethodText}.",
            preg_match('/deuda|cobrar|pendiente|moros|saldo/u', $q) === 1 => 'Existen '.count($pendingBalances)." paciente(s) con saldo por cobrar que suman {$currency} {$debtTotal}. Se recomienda activar seguimiento desde CRM / Caja.",
            preg_match('/comisi|honorario|doctor|odont/u', $q) === 1 => $commissions !== []
                ? 'Honorarios estimados calculados: '.collect($commissions)->map(fn (array $c): string => "{$c['name']} ({$currency} ".number_format((float) $c['commission_amount'], 2).')')->implode(', ').'.'
                : 'Aún no hay procedimientos marcados como realizados para calcular honorarios en este periodo.',
            preg_match('/cita|inasist|no asist|cancel|agenda/u', $q) === 1 => "Se registraron {$summary['appointments_total']} citas ({$attended} atendidas, {$summary['appointments_no_show']} inasistencias, {$summary['appointments_cancelled']} canceladas), con una tasa de inasistencia de {$noShowRate}%.",
            preg_match('/tratam|procedim|servici/u', $q) === 1 => "El procedimiento con mayor demanda en el periodo fue {$topProcText}. Ingresaron {$newPatients} paciente(s) nuevo(s).",
            default => "Resumen del periodo: ingresos de {$currency} {$totalIncome}, {$newPatients} paciente(s) nuevo(s), {$attended} citas atendidas (inasistencia {$noShowRate}%), procedimiento líder '{$topProcText}' y saldo total por cobrar de {$currency} {$debtTotal}.",
        };

        return [
            'provider' => 'local_ai',
            'answer' => $answer,
            'highlights' => $highlights,
        ];
    }

    /**
     * Genera mensajes personalizados por WhatsApp estilo Soyla IA / Contact Center IA (Doctocliq & Dentalink).
     *
     * @param  array{pending_balance: float, pending_budget: float, days_since_visit: int|null}  $financialStats
     * @return array{provider: string, message: string, whatsapp_url: string|null}
     */
    public static function draftWhatsappFollowup(
        Patient $patient,
        string $campaignType,
        Clinic $clinic,
        array $financialStats,
    ): array {
        $currency = $clinic->currency_symbol;
        $firstName = $patient->first_name;
        $clinicName = $clinic->name;
        $bookingUrl = $clinic->online_booking_enabled ? url('/reservar-cita') : null;

        $template = match ($campaignType) {
            'presupuesto' => "Hola {$firstName}, te saluda {$clinicName}. Revisamos tu plan de tratamiento pendiente".($financialStats['pending_budget'] > 0 ? " (valorizado en {$currency} ".number_format($financialStats['pending_budget'], 2).')' : '').'. Contamos con facilidades en cuotas y horarios disponibles esta semana para retomarlo.'
                .($bookingUrl ? " Puedes elegir tu horario aquí: {$bookingUrl}" : ' ¿Te gustaría que te reservemos un turno?'),
            'morosidad' => "Hola {$firstName}, te escribimos cordialmente de {$clinicName} para recordarte que tienes un saldo/cuota pendiente de {$currency} ".number_format($financialStats['pending_balance'], 2).'. Si ya realizaste tu pago por Yape/Plin o transferencia, por favor compártenos tu constancia por este medio.',
            'recall_6m' => "Hola {$firstName}, en {$clinicName} cuidamos tu sonrisa. Han pasado ".($financialStats['days_since_visit'] ?? 180).' días desde tu última atención y es momento ideal para tu control preventivo semestral y limpieza dental.'
                .($bookingUrl ? " Agenda tu cita en segundos aquí: {$bookingUrl}" : ' ¿Qué día te viene mejor esta semana?'),
            'reactivacion' => "¡Hola {$firstName}! Te extrañamos en {$clinicName}. Queremos acompañarte para completar tu cuidado dental sin complicaciones."
                .($bookingUrl ? " Reserva tu evaluación de control aquí: {$bookingUrl}" : ' Escríbenos para coordinar un horario cómodo para ti.'),
            default => "Hola {$firstName}, te saluda {$clinicName}. Queríamos saber cómo te sientes tras tu última atención odontológica y recordarte seguir las indicaciones indicadas en tu consulta.",
        };

        $provider = 'local_ai';

        if (self::hasExternalProvider($clinic)) {
            $llmMsg = self::callExternalText(
                $clinic,
                'Eres Soyla, asistente amable de un consultorio dental en Latinoamérica. Redacta UN único mensaje corto de WhatsApp (máximo 45 palabras, cálido, profesional y en español, sin corchetes ni marcadores de plantilla).',
                "Mensaje base a mejorar:\n{$template}",
            );
            if ($llmMsg !== null) {
                $template = $llmMsg;
                $provider = $clinic->ai_provider;
            }
        }

        $digits = preg_replace('/\D+/', '', (string) $patient->phone) ?? '';
        if ($digits !== '' && strlen($digits) === 9) {
            $digits = '51'.$digits;
        }

        $whatsappUrl = $digits !== ''
            ? 'https://wa.me/'.$digits.'?text='.rawurlencode($template)
            : null;

        return [
            'provider' => $provider,
            'message' => $template,
            'whatsapp_url' => $whatsappUrl,
        ];
    }

    private static function hasExternalProvider(Clinic $clinic): bool
    {
        return in_array($clinic->ai_provider, ['gemini', 'openai'], true)
            && is_string($clinic->ai_api_key)
            && trim($clinic->ai_api_key) !== '';
    }

    private static function callExternalText(Clinic $clinic, string $systemPrompt, string $userPrompt): ?string
    {
        $apiKey = trim((string) $clinic->ai_api_key);
        if ($apiKey === '') {
            return null;
        }

        try {
            if ($clinic->ai_provider === 'gemini') {
                $response = Http::timeout(18)
                    ->acceptJson()
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}", [
                        'systemInstruction' => [
                            'parts' => [['text' => $systemPrompt]],
                        ],
                        'contents' => [
                            ['parts' => [['text' => $userPrompt]]],
                        ],
                        'generationConfig' => ['temperature' => 0.2],
                    ]);

                if ($response->successful()) {
                    $text = $response->json('candidates.0.content.parts.0.text');

                    return is_string($text) && trim($text) !== '' ? trim($text) : null;
                }

                return null;
            }

            if ($clinic->ai_provider === 'openai') {
                $response = Http::timeout(18)
                    ->withToken($apiKey)
                    ->acceptJson()
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => 'gpt-4o-mini',
                        'temperature' => 0.2,
                        'messages' => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user', 'content' => $userPrompt],
                        ],
                    ]);

                if ($response->successful()) {
                    $text = $response->json('choices.0.message.content');

                    return is_string($text) && trim($text) !== '' ? trim($text) : null;
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function callExternalJson(Clinic $clinic, string $systemPrompt, string $userPrompt): ?array
    {
        $text = self::callExternalText($clinic, $systemPrompt, $userPrompt);
        if ($text === null) {
            return null;
        }

        $stripped = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($text)) ?? trim($text);

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($stripped, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function callGeminiVisionJson(string $apiKey, string $imageBytes, string $mime, string $prompt): ?array
    {
        try {
            $response = Http::timeout(25)
                ->acceptJson()
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                                [
                                    'inlineData' => [
                                        'mimeType' => $mime,
                                        'data' => base64_encode($imageBytes),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => ['temperature' => 0.1],
                ]);

            if (! $response->successful()) {
                return null;
            }

            $text = $response->json('candidates.0.content.parts.0.text');
            if (! is_string($text) || trim($text) === '') {
                return null;
            }

            $stripped = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($text)) ?? trim($text);
            /** @var mixed $decoded */
            $decoded = json_decode($stripped, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : null;
        } catch (Throwable) {
            return null;
        }
    }

    public static function hasUpcomingConfirmedBookings(Patient $patient): int
    {
        return $patient->appointments()
            ->whereIn('status', [AppointmentStatus::Reservada, AppointmentStatus::Confirmada])
            ->where('starts_at', '>=', now())
            ->count();
    }
}
