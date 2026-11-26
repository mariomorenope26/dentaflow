<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Odontogram;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\FhirExportService;
use App\Services\OdontogramCatalog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfExportController extends Controller
{
    public function clinicalHistory(Request $request, Patient $patient): Response
    {
        Gate::authorize('exportPdf', $patient);

        $patient->load([
            'clinic',
            'medicalHistory.updater:id,name',
            'clinicalEvolutions.author:id,name,license_number',
            'clinicalEvolutions.signer:id,name',
            'clinicalEvolutions.corrections.author:id,name',
            'odontograms.findings',
        ]);

        $clinic = $patient->clinic;
        $printSettings = $clinic ? $clinic->resolvedPrintSettings() : [
            'paper_size' => 'a4',
            'thermal_width' => '80mm',
            'show_logo' => true,
            'show_cop' => true,
            'header_text' => '',
            'footer_text' => 'Gracias por confiar su salud bucal en nosotros.',
        ];
        $logoDataUri = $printSettings['show_logo'] ? $clinic?->logoDataUri() : null;

        AuditLogger::log('export_pdf', $patient, "Exportó Historia Clínica en PDF de {$patient->full_name} ({$patient->history_number})", [], $request);

        $pdf = Pdf::loadView('pdf.clinical-history', [
            'patient' => $patient,
            'clinic' => $clinic,
            'history' => $patient->medicalHistory,
            'evolutions' => $patient->clinicalEvolutions,
            'latestOdontogram' => $patient->odontograms->first(),
            'conditionsMap' => collect(OdontogramCatalog::conditions())->keyBy('code')->all(),
            'printSettings' => $printSettings,
            'logoDataUri' => $logoDataUri,
            'generatedAt' => now(),
        ])->setPaper($printSettings['paper_size'], 'portrait');

        return $pdf->stream("HistoriaClinica-{$patient->history_number}.pdf");
    }

    public function odontogram(Request $request, Patient $patient, ?Odontogram $odontogram = null): Response
    {
        Gate::authorize('exportPdf', $patient);

        $patient->load('clinic');

        if ($odontogram !== null && $odontogram->patient_id !== $patient->id) {
            abort(404);
        }

        $target = $odontogram ?? $patient->odontograms()->with(['findings', 'creator:id,name'])->first();
        if ($target === null) {
            abort(404, 'El paciente aún no tiene un odontograma registrado.');
        }

        $target->loadMissing(['findings', 'creator:id,name']);

        $clinic = $patient->clinic;
        $printSettings = $clinic ? $clinic->resolvedPrintSettings() : [
            'paper_size' => 'a4',
            'thermal_width' => '80mm',
            'show_logo' => true,
            'show_cop' => true,
            'header_text' => '',
            'footer_text' => 'Gracias por confiar su salud bucal en nosotros.',
        ];
        $logoDataUri = $printSettings['show_logo'] ? $clinic?->logoDataUri() : null;

        AuditLogger::log('export_pdf', $target, "Exportó Odontograma PDF de {$patient->full_name} ({$patient->history_number})", [], $request);

        $pdf = Pdf::loadView('pdf.odontogram', [
            'patient' => $patient,
            'clinic' => $clinic,
            'odontogram' => $target,
            'conditionsMap' => collect(OdontogramCatalog::conditions())->keyBy('code')->all(),
            'printSettings' => $printSettings,
            'logoDataUri' => $logoDataUri,
            'generatedAt' => now(),
        ])->setPaper($printSettings['paper_size'], 'landscape');

        return $pdf->stream("Odontograma-{$patient->history_number}.pdf");
    }

    public function fhirBundle(Request $request, Patient $patient): StreamedResponse
    {
        Gate::authorize('exportPdf', $patient);

        $patient->loadMissing('clinic');
        $bundle = FhirExportService::buildPatientBundle($patient, $patient->clinic);
        $filename = "HL7-FHIR-R4-{$patient->history_number}.json";

        AuditLogger::log('export_pdf', $patient, "Exportó Historia Clínica interoperable HL7 FHIR R4 ({$filename})", [], $request);

        return response()->streamDownload(function () use ($bundle): void {
            echo (string) json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $filename, [
            'Content-Type' => 'application/fhir+json; charset=UTF-8',
        ]);
    }
}
