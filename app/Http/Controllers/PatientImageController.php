<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientImage;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientImageController extends Controller
{
    public function show(Patient $patient, PatientImage $image): StreamedResponse
    {
        if ($image->patient_id !== $patient->id) {
            abort(404);
        }

        Gate::authorize('view', $patient);

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($image->file_path)) {
                return Storage::disk($disk)->response($image->file_path);
            }
        }

        abort(404);
    }

    public function store(Request $request, Patient $patient): RedirectResponse
    {
        Gate::authorize('editClinical', $patient);

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'category' => ['required', 'string', 'in:radiografia,intraoral,extraoral,antes_despues,documento'],
            'title' => ['required', 'string', 'max:160'],
            'tooth_code' => ['nullable', 'string', 'max:10'],
            'taken_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'image' => ['required', 'image', 'max:8192'],
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('image');
        $path = (string) $file->store("patient-images/{$patient->clinic_id}/{$patient->id}", 'local');

        $image = PatientImage::create([
            'clinic_id' => $patient->clinic_id,
            'patient_id' => $patient->id,
            'uploaded_by' => $user->id,
            'category' => $validated['category'],
            'title' => $validated['title'],
            'tooth_code' => $validated['tooth_code'] ?? null,
            'file_path' => $path,
            'taken_at' => $validated['taken_at'],
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLogger::log('create', $image, "Subió imagen clínica ({$image->category}): {$image->title} para {$patient->full_name}", [], $request);

        return back()->with('success', 'Imagen clínica cargada en la galería.');
    }

    public function updateAnnotations(Request $request, Patient $patient, PatientImage $image): RedirectResponse
    {
        if ($image->patient_id !== $patient->id) {
            abort(404);
        }

        Gate::authorize('editClinical', $patient);

        $validated = $request->validate([
            'calibration_mm_per_px' => ['nullable', 'numeric', 'min:0.001', 'max:10'],
            'measurements' => ['nullable', 'array'],
            'pins' => ['nullable', 'array'],
            'filters' => ['nullable', 'array'],
        ]);

        $image->update([
            'annotations' => [
                'calibration_mm_per_px' => $validated['calibration_mm_per_px'] ?? null,
                'measurements' => $validated['measurements'] ?? [],
                'pins' => $validated['pins'] ?? [],
                'filters' => $validated['filters'] ?? ['brightness' => 100, 'contrast' => 100, 'invert' => false],
            ],
        ]);

        AuditLogger::log('update', $image, "Guardó mediciones y anotaciones radiográficas en '{$image->title}'", [], $request);

        return back()->with('success', 'Mediciones y anotaciones guardadas.');
    }

    public function destroy(Request $request, Patient $patient, PatientImage $image): RedirectResponse
    {
        if ($image->patient_id !== $patient->id) {
            abort(404);
        }

        Gate::authorize('editClinical', $patient);

        Storage::disk('local')->delete($image->file_path);
        Storage::disk('public')->delete($image->file_path);
        $title = $image->title;
        $image->delete();

        AuditLogger::log('delete', PatientImage::class, "Eliminó imagen clínica '{$title}' de {$patient->full_name}", [], $request, $patient->clinic_id);

        return back()->with('success', 'Imagen eliminada de la galería.');
    }
}
