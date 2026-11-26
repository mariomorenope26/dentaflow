<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json(['patients' => []]);
        }

        $patients = Patient::query()
            ->with('medicalHistory:id,patient_id,medical_alerts,allergies')
            ->where(function ($query) use ($q): void {
                $query->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('document_number', 'like', "%{$q}%")
                    ->orWhere('history_number', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            })
            ->orderBy('last_name')
            ->limit(8)
            ->get()
            ->map(fn (Patient $patient): array => [
                'id' => $patient->id,
                'history_number' => $patient->history_number,
                'full_name' => $patient->full_name,
                'document_type' => $patient->document_type,
                'document_number' => $patient->document_number,
                'phone' => $patient->phone,
                'has_alerts' => $patient->medicalHistory !== null
                    && (! empty($patient->medicalHistory->medical_alerts) || ! empty($patient->medicalHistory->allergies)),
            ]);

        return response()->json([
            'patients' => $patients,
        ]);
    }
}
