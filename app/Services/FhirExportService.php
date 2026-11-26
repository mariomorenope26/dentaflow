<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Clinic;
use App\Models\Patient;
use Illuminate\Support\Str;

class FhirExportService
{
    /**
     * Build an HL7 FHIR R4 Document Bundle for international EHR interoperability.
     *
     * @return array<string, mixed>
     */
    public static function buildPatientBundle(Patient $patient, ?Clinic $clinic): array
    {
        $patient->loadMissing([
            'medicalHistory.updater:id,name',
            'clinicalEvolutions.author:id,name,license_number',
            'clinicalEvolutions.signer:id,name',
            'odontograms.findings',
            'clinicalDocuments.creator:id,name,license_number',
        ]);

        $patientUuid = $patient->fhir_uuid ?: (string) Str::uuid();
        $orgId = 'clinic-'.($clinic !== null ? (string) $clinic->id : '1');
        $history = $patient->medicalHistory;
        $entries = [];

        $identifiers = [[
            'use' => 'official',
            'system' => 'urn:dentaflow:history-number',
            'value' => $patient->history_number,
        ]];
        if ($patient->document_number) {
            $identifiers[] = [
                'use' => 'official',
                'type' => ['text' => $patient->document_type],
                'value' => $patient->document_number,
            ];
        }

        $extensions = [];
        if ($patient->blood_type) {
            $extensions[] = [
                'url' => 'http://hl7.org/fhir/StructureDefinition/patient-bloodType',
                'valueString' => $patient->blood_type,
            ];
        }

        $entries[] = [
            'fullUrl' => "urn:uuid:{$patientUuid}",
            'resource' => [
                'resourceType' => 'Patient',
                'id' => $patientUuid,
                'meta' => [
                    'profile' => ['http://hl7.org/fhir/StructureDefinition/Patient'],
                ],
                'identifier' => $identifiers,
                'active' => $patient->is_active,
                'name' => [[
                    'use' => 'official',
                    'family' => $patient->last_name,
                    'given' => preg_split('/\s+/', trim($patient->first_name)) ?: [$patient->first_name],
                    'text' => $patient->full_name,
                ]],
                'gender' => match ($patient->gender) {
                    'M' => 'male',
                    'F' => 'female',
                    default => 'other',
                },
                'birthDate' => $patient->birth_date?->toDateString(),
                'telecom' => array_values(array_filter([
                    $patient->phone ? ['system' => 'phone', 'value' => $patient->phone] : null,
                    $patient->email ? ['system' => 'email', 'value' => $patient->email] : null,
                ])),
                'address' => $patient->address ? [['text' => $patient->address]] : [],
                'extension' => $extensions,
            ],
        ];

        if ($clinic instanceof Clinic) {
            $entries[] = [
                'fullUrl' => "urn:dentaflow:organization:{$orgId}",
                'resource' => [
                    'resourceType' => 'Organization',
                    'id' => $orgId,
                    'identifier' => $clinic->ruc ? [[
                        'system' => 'urn:pe:sunat:ruc',
                        'value' => $clinic->ruc,
                    ]] : [],
                    'name' => $clinic->name,
                    'telecom' => array_values(array_filter([
                        $clinic->phone ? ['system' => 'phone', 'value' => $clinic->phone] : null,
                        $clinic->email ? ['system' => 'email', 'value' => $clinic->email] : null,
                    ])),
                    'address' => $clinic->address ? [['text' => $clinic->address]] : [],
                ],
            ];
        }

        if ($history !== null) {
            if (! empty($history->allergies)) {
                $entries[] = [
                    'resource' => [
                        'resourceType' => 'AllergyIntolerance',
                        'id' => "allergy-{$patient->id}",
                        'clinicalStatus' => ['coding' => [['code' => 'active']]],
                        'criticality' => 'high',
                        'code' => ['text' => $history->allergies],
                        'patient' => ['reference' => "urn:uuid:{$patientUuid}"],
                    ],
                ];
            }

            if (! empty($history->definitive_diagnosis) || ! empty($history->presumptive_diagnosis) || ! empty($history->cie10_code)) {
                $entries[] = [
                    'resource' => [
                        'resourceType' => 'Condition',
                        'id' => "diagnosis-{$patient->id}",
                        'clinicalStatus' => ['coding' => [['code' => 'active']]],
                        'verificationStatus' => [
                            'coding' => [[
                                'code' => ! empty($history->definitive_diagnosis) ? 'confirmed' : 'provisional',
                            ]],
                        ],
                        'code' => [
                            'coding' => ! empty($history->cie10_code) ? [[
                                'system' => 'http://hl7.org/fhir/sid/icd-10',
                                'code' => $history->cie10_code,
                                'display' => $history->definitive_diagnosis ?? $history->presumptive_diagnosis ?? '',
                            ]] : [],
                            'text' => $history->definitive_diagnosis ?? $history->presumptive_diagnosis ?? 'Diagnóstico estomatológico',
                        ],
                        'subject' => ['reference' => "urn:uuid:{$patientUuid}"],
                    ],
                ];
            }

            $vitals = $history->vital_signs ?? [];
            foreach ([
                'pa' => ['label' => 'Presión Arterial', 'code' => '85354-9', 'unit' => 'mmHg'],
                'fc' => ['label' => 'Frecuencia Cardíaca', 'code' => '8867-4', 'unit' => 'lpm'],
                'temp' => ['label' => 'Temperatura Corporal', 'code' => '8310-5', 'unit' => 'C'],
                'sato2' => ['label' => 'Saturación O2', 'code' => '59408-5', 'unit' => '%'],
            ] as $key => $meta) {
                $val = $vitals[$key] ?? null;
                if (is_string($val) && trim($val) !== '') {
                    $entries[] = [
                        'resource' => [
                            'resourceType' => 'Observation',
                            'id' => "vital-{$key}-{$patient->id}",
                            'status' => 'final',
                            'category' => [['coding' => [['code' => 'vital-signs']]]],
                            'code' => [
                                'coding' => [[
                                    'system' => 'http://loinc.org',
                                    'code' => $meta['code'],
                                    'display' => $meta['label'],
                                ]],
                            ],
                            'subject' => ['reference' => "urn:uuid:{$patientUuid}"],
                            'valueString' => trim($val).' '.$meta['unit'],
                        ],
                    ];
                }
            }
        }

        foreach ($patient->clinicalEvolutions as $evol) {
            $entries[] = [
                'resource' => [
                    'resourceType' => 'Procedure',
                    'id' => "evolution-{$evol->id}",
                    'status' => $evol->signed_at !== null ? 'completed' : 'in-progress',
                    'code' => [
                        'coding' => $evol->cie10_code ? [[
                            'system' => 'http://hl7.org/fhir/sid/icd-10',
                            'code' => $evol->cie10_code,
                        ]] : [],
                        'text' => $evol->procedure_summary,
                    ],
                    'subject' => ['reference' => "urn:uuid:{$patientUuid}"],
                    'performedDateTime' => $evol->attended_at->toIso8601String(),
                    'note' => array_values(array_filter([
                        ['text' => $evol->clinical_notes],
                        $evol->soap_subjective ? ['text' => 'S: '.$evol->soap_subjective] : null,
                        $evol->soap_objective ? ['text' => 'O: '.$evol->soap_objective] : null,
                        $evol->soap_assessment ? ['text' => 'A: '.$evol->soap_assessment] : null,
                        $evol->soap_plan ? ['text' => 'P: '.$evol->soap_plan] : null,
                    ])),
                ],
            ];
        }

        return [
            'resourceType' => 'Bundle',
            'id' => 'dentaflow-hce-'.$patient->history_number,
            'meta' => [
                'lastUpdated' => now()->toIso8601String(),
                'profile' => ['http://hl7.org/fhir/StructureDefinition/Bundle'],
                'tag' => [[
                    'system' => 'urn:dentaflow:standard',
                    'code' => 'HL7-FHIR-R4-ISO-13606-FDI-3950',
                    'display' => 'Historia Clínica Electrónica Odontológica Homologada',
                ]],
            ],
            'type' => 'collection',
            'timestamp' => now()->toIso8601String(),
            'total' => count($entries),
            'entry' => $entries,
        ];
    }
}
