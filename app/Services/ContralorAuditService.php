<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicalEvolution;
use App\Models\InventoryItem;
use App\Models\LabOrder;
use App\Models\OdontogramFinding;
use App\Models\Patient;
use App\Models\PaymentInstallment;

class ContralorAuditService
{
    /**
     * Escanea el consultorio detectando brechas clínicas, documentales y financieras (Dentalink Contralor IA).
     *
     * @return array{
     *     compliance_score: int,
     *     total_patients_audited: int,
     *     total_issues: int,
     *     estimated_unbudgeted_value: float,
     *     counts: array{
     *         missing_consent: int,
     *         unsigned_evolutions: int,
     *         incomplete_histories: int,
     *         unbudgeted_findings: int,
     *         lab_alerts: int,
     *         overdue_installments: int
     *     },
     *     issues: list<array{
     *         id: string,
     *         category: string,
     *         severity: string,
     *         title: string,
     *         detail: string,
     *         patient_name: string|null,
     *         action_label: string,
     *         action_url: string
     *     }>
     * }
     */
    public static function auditClinic(Clinic $clinic): array
    {
        $patients = Patient::withoutGlobalScopes()
            ->where('clinic_id', $clinic->id)
            ->where('is_active', true)
            ->with([
                'medicalHistory',
                'clinicalDocuments',
                'odontograms.findings',
                'treatmentPlans.items',
            ])
            ->get();

        $issues = [];
        $counts = [
            'missing_consent' => 0,
            'unsigned_evolutions' => 0,
            'incomplete_histories' => 0,
            'unbudgeted_findings' => 0,
            'lab_alerts' => 0,
            'overdue_installments' => 0,
        ];

        $catalogMap = collect(OdontogramCatalog::defaultTreatmentCatalog())->keyBy('odontogram_condition');
        $unbudgetedValue = 0.0;

        foreach ($patients as $patient) {
            // 1. Anamnesis HCE / ASA incompleta
            $mh = $patient->medicalHistory;
            if ($mh === null || (empty($mh->chief_complaint) && empty($mh->asa_classification))) {
                $counts['incomplete_histories']++;
                $issues[] = [
                    'id' => "mh-{$patient->id}",
                    'category' => 'Historia HCE',
                    'severity' => 'alta',
                    'title' => 'Anamnesis y clasificación ASA sin completar',
                    'detail' => "La ficha {$patient->history_number} aún no registra motivo de consulta o riesgo ASA.",
                    'patient_name' => $patient->full_name,
                    'action_label' => 'Completar Historia HCE',
                    'action_url' => "/patients/{$patient->id}?tab=historia",
                ];
            }

            // 2. Plan aprobado o tratamiento quirúrgico/endodóntico sin consentimiento firmado
            $hasSignedConsent = $patient->clinicalDocuments
                ->where('type', 'consentimiento')
                ->whereNotNull('signed_at')
                ->isNotEmpty();

            $hasActivePlan = $patient->treatmentPlans
                ->whereIn('status', ['aprobado', 'en_curso'])
                ->isNotEmpty();

            if ($hasActivePlan && ! $hasSignedConsent) {
                $counts['missing_consent']++;
                $issues[] = [
                    'id' => "consent-{$patient->id}",
                    'category' => 'Legal / Consentimiento',
                    'severity' => 'alta',
                    'title' => 'Tratamiento activo sin consentimiento informado firmado',
                    'detail' => 'El paciente tiene un plan aprobado/en curso sin documento de consentimiento firmado en pantalla.',
                    'patient_name' => $patient->full_name,
                    'action_label' => 'Emitir y firmar',
                    'action_url' => "/patients/{$patient->id}?tab=documentos",
                ];
            }

            // 3. Hallazgos patológicos en odontograma aún sin presupuestar (fuga de ingresos)
            $latestOdo = $patient->odontograms->first();
            $pendingFindings = $latestOdo !== null
                ? $latestOdo->findings->where('status', 'por_realizar')
                : collect();

            if ($pendingFindings->isNotEmpty() && $patient->treatmentPlans->where('status', '!=', 'cancelado')->isEmpty()) {
                $counts['unbudgeted_findings'] += $pendingFindings->count();
                $estimated = (float) $pendingFindings->sum(function (OdontogramFinding $f) use ($catalogMap): float {
                    $tpl = $catalogMap->get($f->condition_code);

                    return is_array($tpl) ? (float) $tpl['default_price'] : 120.0;
                });
                $unbudgetedValue += $estimated;

                $issues[] = [
                    'id' => "unbud-{$patient->id}",
                    'category' => 'Fuga de Ingresos',
                    'severity' => 'media',
                    'title' => "{$pendingFindings->count()} hallazgo(s) en odontograma sin presupuesto",
                    'detail' => 'Potencial estimado de '.$clinic->currency_symbol.' '.number_format($estimated, 2).' pendiente de cotizar al paciente.',
                    'patient_name' => $patient->full_name,
                    'action_label' => 'Convertir a presupuesto',
                    'action_url' => "/patients/{$patient->id}?tab=odontograma",
                ];
            }
        }

        // 4. Notas clínicas SOAP en borrador (sin firma SHA-256)
        $unsignedEvols = ClinicalEvolution::withoutGlobalScopes()
            ->where('clinic_id', $clinic->id)
            ->whereNull('signed_at')
            ->with('patient:id,first_name,last_name,history_number')
            ->limit(15)
            ->get();

        foreach ($unsignedEvols as $ev) {
            $counts['unsigned_evolutions']++;
            $issues[] = [
                'id' => "evol-{$ev->id}",
                'category' => 'Firma SOAP',
                'severity' => 'media',
                'title' => "Evolución sin firma digital: {$ev->procedure_summary}",
                'detail' => 'Nota en estado borrador desde '.$ev->attended_at->format('d/m/Y').'. Debe sellarse con firma SHA-256.',
                'patient_name' => $ev->patient->full_name ?? '-',
                'action_label' => 'Firmar evolución',
                'action_url' => "/patients/{$ev->patient_id}?tab=odontograma",
            ];
        }

        // 5. Órdenes de laboratorio pendientes con cita próxima
        $urgentLabs = LabOrder::withoutGlobalScopes()
            ->where('clinic_id', $clinic->id)
            ->whereIn('status', ['por_enviar', 'en_laboratorio', 'rehacer'])
            ->whereHas('appointment', function ($q): void {
                $q->where('starts_at', '>=', now()->startOfDay())
                    ->where('starts_at', '<=', now()->addDays(3)->endOfDay())
                    ->whereNotIn('status', [AppointmentStatus::Cancelada->value, AppointmentStatus::NoAsistio->value]);
            })
            ->with('patient:id,first_name,last_name')
            ->get();

        foreach ($urgentLabs as $lab) {
            $counts['lab_alerts']++;
            $issues[] = [
                'id' => "lab-{$lab->id}",
                'category' => 'Laboratorio',
                'severity' => 'alta',
                'title' => "Trabajo {$lab->order_code} ({$lab->work_type}) pendiente con cita próxima",
                'detail' => "Estado actual: {$lab->status}. Confirmar recepción antes de atender al paciente.",
                'patient_name' => $lab->patient->full_name ?? '-',
                'action_label' => 'Gestionar laboratorio',
                'action_url' => '/laboratorio',
            ];
        }

        // 6. Cuotas vencidas sin cobro + Stock crítico
        $overdueInstallments = PaymentInstallment::withoutGlobalScopes()
            ->where('clinic_id', $clinic->id)
            ->whereIn('status', ['pendiente', 'parcial'])
            ->whereDate('due_date', '<', today()->toDateString())
            ->with('patient:id,first_name,last_name')
            ->get();

        foreach ($overdueInstallments as $inst) {
            $counts['overdue_installments']++;
            $issues[] = [
                'id' => "inst-{$inst->id}",
                'category' => 'Morosidad Cuotas',
                'severity' => 'media',
                'title' => "Cuota #{$inst->installment_number} vencida ({$clinic->currency_symbol} ".number_format((float) $inst->amount - (float) $inst->paid_amount, 2).')',
                'detail' => 'Venció el '.$inst->due_date->format('d/m/Y').'. Enviar recordatorio de cobranza por WhatsApp.',
                'patient_name' => $inst->patient->full_name ?? '-',
                'action_label' => 'Cobrar / Recordar',
                'action_url' => '/caja',
            ];
        }

        $lowStockCount = InventoryItem::withoutGlobalScopes()
            ->where('clinic_id', $clinic->id)
            ->where('is_active', true)
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->count();

        if ($lowStockCount > 0) {
            $issues[] = [
                'id' => 'kardex-low',
                'category' => 'Bioseguridad / Stock',
                'severity' => 'baja',
                'title' => "{$lowStockCount} insumo(s) crítico(s) bajo stock mínimo",
                'detail' => 'Revisar reposición de anestésicos, resinas o material estéril en Kardex.',
                'patient_name' => null,
                'action_label' => 'Abrir Kardex',
                'action_url' => '/esterilizacion-kardex',
            ];
        }

        $appointmentsWaitingUnconfirmed = Appointment::withoutGlobalScopes()
            ->where('clinic_id', $clinic->id)
            ->where('status', AppointmentStatus::Reservada->value)
            ->whereBetween('starts_at', [now()->startOfDay(), now()->addDay()->endOfDay()])
            ->count();

        if ($appointmentsWaitingUnconfirmed > 0) {
            $issues[] = [
                'id' => 'agenda-unconfirmed',
                'category' => 'Confirmación Agenda',
                'severity' => 'baja',
                'title' => "{$appointmentsWaitingUnconfirmed} cita(s) de hoy/mañana sin confirmar",
                'detail' => 'Enviar recordatorio por WhatsApp en 1 clic para prevenir inasistencias.',
                'patient_name' => null,
                'action_label' => 'Ir a Agenda',
                'action_url' => '/agenda',
            ];
        }

        $totalChecks = max(1, ($patients->count() * 3) + 5);
        $weightedPenalty = ($counts['missing_consent'] * 3)
            + ($counts['unsigned_evolutions'] * 2)
            + ($counts['incomplete_histories'] * 2)
            + ($counts['lab_alerts'] * 2)
            + $counts['overdue_installments']
            + min(5, $counts['unbudgeted_findings']);

        $score = (int) max(25, min(100, round(100 - (($weightedPenalty / $totalChecks) * 100))));

        return [
            'compliance_score' => $score,
            'total_patients_audited' => $patients->count(),
            'total_issues' => count($issues),
            'estimated_unbudgeted_value' => round($unbudgetedValue, 2),
            'counts' => $counts,
            'issues' => $issues,
        ];
    }
}
