<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ClinicalDocument;
use App\Models\Patient;
use App\Models\User;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ClinicalDocumentController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        Gate::authorize('editClinical', $patient);

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:consentimiento,receta,certificado'],
            'title' => ['required', 'string', 'max:180'],
            'content' => ['required', 'string', 'max:10000'],
            'signature_data' => ['nullable', 'string', 'max:500000'],
            'signed_by_name' => ['nullable', 'string', 'max:150'],
        ]);

        $hasSignature = ! empty($validated['signature_data']);

        $document = ClinicalDocument::create([
            'clinic_id' => $patient->clinic_id,
            'patient_id' => $patient->id,
            'created_by' => $user->id,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'content' => $validated['content'],
            'signature_data' => $validated['signature_data'] ?? null,
            'signed_by_name' => $hasSignature ? ($validated['signed_by_name'] ?? $patient->full_name) : null,
            'signed_at' => $hasSignature ? now() : null,
            'issued_at' => now(),
        ]);

        AuditLogger::log('create', $document, "Emitió documento clínico ({$document->type}): {$document->title} para {$patient->full_name}", [], $request);

        return back()->with('success', 'Documento clínico guardado.');
    }

    public function sign(Request $request, Patient $patient, ClinicalDocument $document): RedirectResponse
    {
        if ($document->patient_id !== $patient->id) {
            abort(404);
        }

        Gate::authorize('view', $patient);

        $validated = $request->validate([
            'signature_data' => ['required', 'string', 'max:500000'],
            'signed_by_name' => ['required', 'string', 'max:150'],
        ]);

        $document->update([
            'signature_data' => $validated['signature_data'],
            'signed_by_name' => $validated['signed_by_name'],
            'signed_at' => now(),
        ]);

        AuditLogger::log('sign', $document, "Firmó en pantalla '{$document->title}' ({$patient->full_name})", [], $request);

        return back()->with('success', 'Documento firmado digitalmente.');
    }

    public function pdf(Request $request, Patient $patient, ClinicalDocument $document): Response
    {
        if ($document->patient_id !== $patient->id) {
            abort(404);
        }

        Gate::authorize('exportPdf', $patient);

        $document->load(['creator:id,name,specialty,license_number', 'clinic']);
        $clinic = $document->clinic;
        $printSettings = $clinic ? $clinic->resolvedPrintSettings() : [
            'paper_size' => 'a4',
            'thermal_width' => '80mm',
            'show_logo' => true,
            'show_cop' => true,
            'header_text' => '',
            'footer_text' => 'Gracias por confiar su salud bucal en nosotros.',
        ];
        $logoDataUri = $printSettings['show_logo'] ? $clinic?->logoDataUri() : null;

        AuditLogger::log('export_pdf', $document, "Descargó PDF de documento '{$document->title}'", [], $request);

        $pdf = Pdf::loadView('pdf.document', [
            'document' => $document,
            'patient' => $patient,
            'clinic' => $clinic,
            'printSettings' => $printSettings,
            'logoDataUri' => $logoDataUri,
        ])->setPaper($printSettings['paper_size'], 'portrait');

        return $pdf->stream("Documento-{$patient->history_number}-{$document->id}.pdf");
    }

    public function update(Request $request, Patient $patient, ClinicalDocument $document): RedirectResponse
    {
        if ($document->patient_id !== $patient->id) {
            abort(404);
        }

        Gate::authorize('editClinical', $patient);

        if ($document->signed_at !== null) {
            abort(403, 'Un documento firmado no puede modificarse.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'content' => ['required', 'string', 'max:10000'],
        ]);

        $document->update($validated);

        AuditLogger::log('update', $document, "Editó documento clínico '{$document->title}'", [], $request);

        return back()->with('success', 'Documento clínico actualizado.');
    }

    public function destroy(Request $request, Patient $patient, ClinicalDocument $document): RedirectResponse
    {
        if ($document->patient_id !== $patient->id) {
            abort(404);
        }

        Gate::authorize('editClinical', $patient);

        if ($document->signed_at !== null) {
            return back()->with('error', 'No se puede eliminar un documento firmado.');
        }

        $title = $document->title;
        $clinicId = $document->clinic_id;
        $document->delete();

        AuditLogger::log('delete', ClinicalDocument::class, "Eliminó documento '{$title}' de {$patient->full_name}", [], $request, $clinicId);

        return back()->with('success', 'Documento eliminado.');
    }
}
