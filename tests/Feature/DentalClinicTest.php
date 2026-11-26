<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\CashClosing;
use App\Models\Clinic;
use App\Models\ClinicalDocument;
use App\Models\ClinicalEvolution;
use App\Models\InventoryItem;
use App\Models\LabOrder;
use App\Models\Odontogram;
use App\Models\OdontogramFinding;
use App\Models\OrthodonticControl;
use App\Models\Patient;
use App\Models\PatientImage;
use App\Models\Payment;
use App\Models\PaymentInstallment;
use App\Models\Periodontogram;
use App\Models\SterilizationCycle;
use App\Models\TreatmentCatalog;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Models\User;
use App\Services\ClinicProvisioner;
use App\Services\UserManualCatalog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

test('el sistema funciona como un solo consultorio sin registro publico abierto y comparte un consultorio unico', function () {
    $clinic = ClinicProvisioner::createClinicWithDefaults([
        'name' => 'Clínica Dental Única',
    ]);
    $admin = User::factory()->forClinic($clinic, UserRole::AdminConsultorio)->create();

    expect(Clinic::current()?->id)->toBe($clinic->id);

    // El registro público permanece cerrado cuando ya existe un consultorio configurado
    $this->post(route('register.store'), [
        'name' => 'Usuario Externo',
        'email' => 'externo@clinica.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertForbidden();

    // El administrador crea usuarios internos vinculados al mismo consultorio único
    $this->actingAs($admin)->post('/clinic/users', [
        'name' => 'Dra. Nuevo Ingreso',
        'email' => 'nueva@clinica.test',
        'role' => UserRole::Odontologo->value,
        'password' => 'ClaveSegura123!',
    ])->assertRedirect();

    $newUser = User::query()->where('email', 'nueva@clinica.test')->firstOrFail();
    expect($newUser->clinic_id)->toBe($clinic->id);
    expect($newUser->role)->toBe(UserRole::Odontologo);
});

test('cada rol ve y ejecuta unicamente lo que le corresponde en el consultorio', function () {
    $clinic = ClinicProvisioner::createClinicWithDefaults([
        'name' => 'Clínica Roles',
    ]);

    $admin = User::factory()->forClinic($clinic, UserRole::AdminConsultorio)->create();
    $assistant = User::factory()->forClinic($clinic, UserRole::Asistente)->create();
    $accountant = User::factory()->forClinic($clinic, UserRole::Contador)->create();

    $patient = Patient::withoutGlobalScopes()->create([
        'clinic_id' => $clinic->id,
        'history_number' => 'HC-0001',
        'first_name' => 'María',
        'last_name' => 'López',
        'created_by' => $admin->id,
    ]);

    // Asistente gestiona pacientes, agenda y caja, pero no edita historia clínica ni configuración
    $this->actingAs($assistant)->get('/patients')->assertOk();
    $this->actingAs($assistant)->get('/caja')->assertOk();
    $this->actingAs($assistant)->put("/patients/{$patient->id}/medical-history", ['chief_complaint' => 'Dolor'])->assertForbidden();
    $this->actingAs($assistant)->get('/clinic/settings')->assertForbidden();

    // Contador ve caja, reportes y auditoría, pero no registra pacientes ni altera historia clínica
    $this->actingAs($accountant)->get('/clinic/audit')->assertOk();
    $this->actingAs($accountant)->get('/caja')->assertOk();
    $this->actingAs($accountant)->get('/reportes')->assertOk();
    $this->actingAs($accountant)->post('/patients', [
        'first_name' => 'No',
        'last_name' => 'Permitido',
        'document_type' => 'DNI',
    ])->assertForbidden();
});

test('un odontologo registra paciente, historia, evolucion firmada bloqueada y correccion trazable', function () {
    $clinic = ClinicProvisioner::createClinicWithDefaults([
        'name' => 'Clínica Sonrisa',
    ]);
    $dentist = User::factory()->forClinic($clinic, UserRole::Odontologo)->create();

    $this->actingAs($dentist)
        ->post('/patients', [
            'first_name' => 'Sofía',
            'last_name' => 'Torres',
            'document_type' => 'DNI',
            'document_number' => '71234567',
            'birth_date' => now()->subYears(8)->toDateString(),
            'gender' => 'F',
            'guardian_name' => 'Ana Torres',
            'guardian_phone' => '999888777',
            'tags' => ['niño'],
        ])
        ->assertRedirect();

    $patient = Patient::withoutGlobalScopes()->where('clinic_id', $clinic->id)->firstOrFail();
    expect($patient->history_number)->toBe('HC-0001');
    expect($patient->odontograms()->first()?->dentition)->toBe('nino');

    $this->put("/patients/{$patient->id}/medical-history", [
        'chief_complaint' => 'Dolor en molar temporal',
        'allergies' => 'Penicilina',
        'vital_signs' => ['pa' => '110/70', 'fc' => '75', 'fr' => '16', 'temp' => '36.5', 'sato2' => '99%'],
        'medical_alerts' => [
            ['type' => 'alergia', 'label' => 'Alergia a Penicilina', 'severity' => 'alta'],
        ],
        'definitive_diagnosis' => 'Caries de la dentina',
        'cie10_code' => 'K02.1',
    ])->assertRedirect();

    $this->post("/patients/{$patient->id}/evolutions", [
        'attended_at' => now()->toDateTimeString(),
        'procedure_summary' => 'Sellante y restauración pieza 75',
        'clinical_notes' => 'Procedimiento atraumático completado.',
        'prescription_notes' => 'Control en 3 meses.',
        'sign_now' => true,
    ])->assertRedirect();

    $evolution = ClinicalEvolution::withoutGlobalScopes()->where('patient_id', $patient->id)->firstOrFail();
    expect($evolution->is_locked)->toBeTrue();

    $this->patch("/patients/{$patient->id}/evolutions/{$evolution->id}", [
        'attended_at' => now()->toDateTimeString(),
        'procedure_summary' => 'Editado ilegalmente',
        'clinical_notes' => 'Intento de alteración',
    ])->assertForbidden();

    $this->post("/patients/{$patient->id}/evolutions", [
        'attended_at' => now()->toDateTimeString(),
        'procedure_summary' => 'Corrección: Sellante pieza 75',
        'clinical_notes' => 'Se precisa uso de ionómero vitreo modificado con resina.',
        'parent_evolution_id' => $evolution->id,
        'correction_reason' => 'Precisión de material restaurador',
        'sign_now' => true,
    ])->assertRedirect();

    expect($evolution->fresh()?->corrections)->toHaveCount(1);
});

test('odontograma guarda hallazgos, convierte a tratamiento y actualiza al marcar realizado', function () {
    $clinic = ClinicProvisioner::createClinicWithDefaults([
        'name' => 'Clínica Dental Pro',
    ]);
    $dentist = User::factory()->forClinic($clinic, UserRole::Odontologo)->create();

    $patient = Patient::withoutGlobalScopes()->create([
        'clinic_id' => $clinic->id,
        'history_number' => 'HC-0001',
        'first_name' => 'Diego',
        'last_name' => 'Ramos',
        'created_by' => $dentist->id,
    ]);

    $odontogram = Odontogram::withoutGlobalScopes()->create([
        'clinic_id' => $clinic->id,
        'patient_id' => $patient->id,
        'created_by' => $dentist->id,
        'type' => 'inicial',
        'dentition' => 'adulto',
        'notation' => 'FDI',
        'recorded_at' => now(),
    ]);

    $this->actingAs($dentist)
        ->put("/patients/{$patient->id}/odontograms/{$odontogram->id}", [
            'dentition' => 'adulto',
            'notation' => 'FDI',
            'notes' => 'Evaluación inicial completa',
            'expected_version' => 1,
            'findings' => [
                [
                    'tooth_code' => '46',
                    'surface' => 'O',
                    'condition_code' => 'caries',
                    'status' => 'por_realizar',
                    'notes' => 'Caries oclusal',
                ],
            ],
        ])
        ->assertRedirect();

    $finding = OdontogramFinding::withoutGlobalScopes()->where('odontogram_id', $odontogram->id)->firstOrFail();

    $this->post("/patients/{$patient->id}/odontograms/{$odontogram->id}/findings/{$finding->id}/convert")
        ->assertRedirect();

    $planItem = TreatmentPlanItem::withoutGlobalScopes()->where('clinic_id', $clinic->id)->firstOrFail();
    expect($planItem->tooth_code)->toBe('46');

    $this->patch("/patients/{$patient->id}/treatment-plan-items/{$planItem->id}", [
        'status' => 'realizado',
    ])->assertRedirect();

    expect($finding->fresh()?->status)->toBe('realizado');
    expect($finding->fresh()?->condition_code)->toBe('obturacion');

    $pdfHistory = $this->get("/patients/{$patient->id}/pdf/history")->assertOk();
    expect(substr((string) $pdfHistory->getContent(), 0, 5))->toBe('%PDF-');

    $pdfOdontogram = $this->get("/patients/{$patient->id}/pdf/odontogram/{$odontogram->id}")->assertOk();
    expect(substr((string) $pdfOdontogram->getContent(), 0, 5))->toBe('%PDF-');
});

test('modulos premium: caja diaria, cierre, documentos firmados, imagenes, periodontograma, ortodoncia y reportes funcionan', function () {
    Storage::fake('local');

    $clinic = ClinicProvisioner::createClinicWithDefaults([
        'name' => 'Clínica Integral',
    ]);
    $dentist = User::factory()->forClinic($clinic, UserRole::AdminConsultorio)->create();
    $patient = Patient::withoutGlobalScopes()->create([
        'clinic_id' => $clinic->id,
        'history_number' => 'HC-0001',
        'first_name' => 'Camila',
        'last_name' => 'Vargas',
        'created_by' => $dentist->id,
    ]);

    $this->actingAs($dentist);

    // Pago y comprobante PDF + Cierre de caja diario
    $this->post("/patients/{$patient->id}/payments", [
        'amount' => 250.00,
        'payment_method' => 'yape_plin',
        'document_type' => 'boleta',
        'concept' => 'Endodoncia pieza 16',
    ])->assertRedirect();

    $payment = Payment::withoutGlobalScopes()->firstOrFail();
    expect($payment->receipt_number)->toBe('BOL-0001');

    $receiptPdf = $this->get("/payments/{$payment->id}/pdf")->assertOk();
    expect(substr((string) $receiptPdf->getContent(), 0, 5))->toBe('%PDF-');

    $this->post('/caja/close', [
        'closing_date' => Carbon::today()->toDateString(),
        'opening_amount' => 100,
        'notes' => 'Cierre conforme',
    ])->assertRedirect();

    $closing = CashClosing::withoutGlobalScopes()->firstOrFail();
    expect((float) $closing->grand_total)->toBe(250.0);

    // Documento clínico (consentimiento) + firma digital en pantalla + PDF
    $this->post("/patients/{$patient->id}/documents", [
        'type' => 'consentimiento',
        'title' => 'Consentimiento Informado Endodoncia',
        'content' => 'Autorizo el procedimiento.',
    ])->assertRedirect();

    $doc = ClinicalDocument::withoutGlobalScopes()->firstOrFail();

    $this->post("/patients/{$patient->id}/documents/{$doc->id}/sign", [
        'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        'signed_by_name' => 'Camila Vargas',
    ])->assertRedirect();

    expect($doc->fresh()?->signed_at)->not->toBeNull();
    $docPdf = $this->get("/patients/{$patient->id}/documents/{$doc->id}/pdf")->assertOk();
    expect(substr((string) $docPdf->getContent(), 0, 5))->toBe('%PDF-');

    // Galería de radiografías / imágenes
    $this->post("/patients/{$patient->id}/images", [
        'category' => 'radiografia',
        'title' => 'Rx Periapical 16',
        'tooth_code' => '16',
        'taken_at' => now()->toDateString(),
        'image' => UploadedFile::fake()->image('rx16.jpg', 400, 400),
    ])->assertRedirect();

    $image = PatientImage::withoutGlobalScopes()->firstOrFail();
    Storage::disk('local')->assertExists($image->file_path);
    $this->get($image->url)->assertOk();

    // Periodontograma y cálculo automático de índice de sangrado
    $this->post("/patients/{$patient->id}/periodontograms", [
        'entries' => [
            '16' => ['pd' => [4, 3, 4, 3, 2, 3], 'bop' => true, 'plaque' => true, 'recession' => 1, 'mobility' => 0, 'furcation' => 0],
            '11' => ['pd' => [2, 2, 2, 2, 1, 2], 'bop' => false, 'plaque' => false, 'recession' => 0, 'mobility' => 0, 'furcation' => 0],
        ],
        'notes' => 'Control inicial periodontal',
    ])->assertRedirect();

    $perio = Periodontogram::withoutGlobalScopes()->firstOrFail();
    expect((float) $perio->bleeding_index)->toBe(50.0);

    // Control de ortodoncia
    $this->post("/patients/{$patient->id}/orthodontic-controls", [
        'control_date' => now()->toDateTimeString(),
        'stage' => 'Alineación y nivelación',
        'upper_archwire' => 'NiTi 0.016"',
        'hygiene_status' => 'buena',
        'clinical_notes' => 'Cambio de ligaduras y arco superior.',
    ])->assertRedirect();

    expect(OrthodonticControl::withoutGlobalScopes()->count())->toBe(1);

    // Reportes y CSV
    $this->get('/reportes')->assertOk();
    $this->get('/reportes/export-csv')->assertOk();
});

test('agenda gestiona citas, evita cruces y sincroniza modo offline con deteccion de conflictos', function () {
    $clinic = ClinicProvisioner::createClinicWithDefaults([
        'name' => 'Clínica Agenda',
    ]);
    $dentist = User::factory()->forClinic($clinic, UserRole::Odontologo)->create();
    $patient = Patient::withoutGlobalScopes()->create([
        'clinic_id' => $clinic->id,
        'history_number' => 'HC-0001',
        'first_name' => 'Laura',
        'last_name' => 'Méndez',
        'created_by' => $dentist->id,
    ]);

    $this->actingAs($dentist);

    $start = Carbon::today()->setHour(10)->setMinute(0);
    $end = $start->copy()->addMinutes(45);

    $this->post('/agenda', [
        'patient_id' => $patient->id,
        'dentist_id' => $dentist->id,
        'chair' => 'Sillón 1',
        'title' => 'Profilaxis',
        'starts_at' => $start->toDateTimeString(),
        'ends_at' => $end->toDateTimeString(),
    ])->assertRedirect();

    $this->post('/agenda', [
        'patient_id' => $patient->id,
        'dentist_id' => $dentist->id,
        'chair' => 'Sillón 1',
        'title' => 'Cita cruzada',
        'starts_at' => $start->copy()->addMinutes(15)->toDateTimeString(),
        'ends_at' => $end->copy()->addMinutes(15)->toDateTimeString(),
    ])->assertSessionHas('error');

    $appointment = Appointment::withoutGlobalScopes()->firstOrFail();

    $this->getJson('/offline-sync/bootstrap')
        ->assertOk()
        ->assertJsonCount(1, 'appointments')
        ->assertJsonCount(1, 'patients');

    $appointment->update(['status' => AppointmentStatus::Confirmada, 'version' => 2]);

    $this->postJson('/offline-sync/push', [
        'mutations' => [
            [
                'id' => 'mut-1',
                'type' => 'appointment.status',
                'payload' => [
                    'appointment_id' => $appointment->id,
                    'expected_version' => 1,
                    'status' => 'en_sala',
                ],
            ],
        ],
    ])->assertOk()
        ->assertJsonCount(0, 'applied')
        ->assertJsonCount(1, 'conflicts');

    $this->postJson('/offline-sync/push', [
        'mutations' => [
            [
                'id' => 'mut-1',
                'type' => 'appointment.status',
                'force' => true,
                'payload' => [
                    'appointment_id' => $appointment->id,
                    'expected_version' => 1,
                    'status' => 'en_sala',
                ],
            ],
        ],
    ])->assertOk()
        ->assertJsonCount(1, 'applied')
        ->assertJsonCount(0, 'conflicts');

    expect($appointment->fresh()?->status)->toBe(AppointmentStatus::EnSala);
});

test('ajustes del consultorio guarda logotipo, formatos de impresion y token apiperu cifrado', function () {
    Storage::fake('public');

    $clinic = ClinicProvisioner::createClinicWithDefaults([
        'name' => 'Clínica Ajustes',
    ]);
    $admin = User::factory()->forClinic($clinic, UserRole::AdminConsultorio)->create();

    $this->actingAs($admin)
        ->post('/clinic/settings', [
            '_method' => 'patch',
            'name' => 'Clínica Ajustes Premium',
            'ruc' => '20609998881',
            'primary_color' => '#0E8FA3',
            'currency_symbol' => 'S/',
            'chairs' => ['Sillón 1', 'Sillón 2'],
            'odontogram_notation' => 'FDI',
            'auto_lock_minutes' => 20,
            'logo' => UploadedFile::fake()->image('logo-clinic.png', 200, 200),
            'apiperu_token' => 'tok-super-secreto-987654',
            'print_settings' => [
                'paper_size' => 'letter',
                'thermal_width' => '58mm',
                'show_logo' => true,
                'show_cop' => false,
                'header_text' => 'Horario L-S',
                'footer_text' => 'Sonrisa saludable',
            ],
        ])
        ->assertRedirect();

    $clinic->refresh();
    expect($clinic->name)->toBe('Clínica Ajustes Premium');
    expect($clinic->logo_path)->not->toBeNull();
    Storage::disk('public')->assertExists((string) $clinic->logo_path);
    expect($clinic->apiperu_token)->toBe('tok-super-secreto-987654');
    expect($clinic->resolvedPrintSettings()['thermal_width'])->toBe('58mm');
    expect($clinic->resolvedPrintSettings()['paper_size'])->toBe('letter');

    $response = $this->actingAs($admin)->get('/clinic/settings')->assertOk();
    expect($response->getContent())->not->toContain('tok-super-secreto-987654');
});

test('busqueda de paciente por DNI verifica primero localmente y luego consulta apiperu.dev', function () {
    $clinic = ClinicProvisioner::createClinicWithDefaults([
        'name' => 'Clínica RENIEC',
    ]);
    $admin = User::factory()->forClinic($clinic, UserRole::AdminConsultorio)->create();

    // Sin token configurado retorna 422
    $this->actingAs($admin)
        ->postJson('/api-integrations/dni-lookup', ['dni' => '45678912'])
        ->assertStatus(422);

    $clinic->update(['apiperu_token' => 'token-valido-123']);

    Http::fake([
        'https://apiperu.dev/api/dni' => Http::response([
            'success' => true,
            'data' => [
                'numero' => '45678912',
                'nombres' => 'JUAN CARLOS',
                'apellido_paterno' => 'PEREZ',
                'apellido_materno' => 'LOPEZ',
                'direccion_completa' => 'Av. Arequipa 1200, Lima',
            ],
        ], 200),
    ]);

    $this->actingAs($admin)
        ->postJson('/api-integrations/dni-lookup', ['dni' => '45678912'])
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'found_local' => false,
            'data' => [
                'dni' => '45678912',
                'first_name' => 'Juan Carlos',
                'last_name' => 'Perez Lopez',
                'address' => 'Av. Arequipa 1200, Lima',
            ],
        ]);

    // Creamos al paciente localmente y comprobamos que no vuelva a consultar apiperu.dev
    Patient::withoutGlobalScopes()->create([
        'clinic_id' => $clinic->id,
        'history_number' => 'HC-0001',
        'first_name' => 'Juan Carlos',
        'last_name' => 'Perez Lopez',
        'document_type' => 'DNI',
        'document_number' => '45678912',
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->postJson('/api-integrations/dni-lookup', ['dni' => '45678912'])
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'found_local' => true,
            'patient' => [
                'history_number' => 'HC-0001',
            ],
        ]);

    Http::assertSentCount(1);
});

test('cruds completos permiten editar y eliminar registros clinicos, catalogo, equipo y pacientes', function () {
    $clinic = ClinicProvisioner::createClinicWithDefaults([
        'name' => 'Clínica CRUD',
    ]);
    $admin = User::factory()->forClinic($clinic, UserRole::AdminConsultorio)->create();
    $assistant = User::factory()->forClinic($clinic, UserRole::Asistente)->create();

    $this->actingAs($admin);

    // Equipo CRUD
    $this->patch("/clinic/users/{$assistant->id}", [
        'name' => 'Asistente Actualizada',
        'phone' => '999000111',
    ])->assertRedirect();
    expect($assistant->fresh()?->name)->toBe('Asistente Actualizada');

    $this->delete("/clinic/users/{$assistant->id}")->assertRedirect();
    expect(User::query()->find($assistant->id))->toBeNull();

    // No puede eliminarse a sí mismo ni degradar/desactivar/eliminar a un Administrador protegido
    $this->delete("/clinic/users/{$admin->id}")->assertSessionHas('error');

    $this->patch("/clinic/users/{$admin->id}", [
        'role' => UserRole::Odontologo->value,
    ])->assertSessionHasErrors('role');
    expect($admin->fresh()?->role)->toBe(UserRole::AdminConsultorio);

    $secondAdmin = User::factory()->forClinic($clinic, UserRole::AdminConsultorio)->create();
    $this->patch("/clinic/users/{$secondAdmin->id}", [
        'role' => UserRole::Asistente->value,
    ])->assertSessionHasErrors('role');
    expect($secondAdmin->fresh()?->role)->toBe(UserRole::AdminConsultorio);

    $this->delete("/clinic/users/{$secondAdmin->id}")->assertSessionHas('error');
    expect(User::query()->find($secondAdmin->id))->not->toBeNull();

    // Catálogo de tratamientos CRUD
    $this->post('/treatments', [
        'code' => 'TEST-01',
        'name' => 'Tratamiento Prueba',
        'category' => 'Preventiva',
        'default_price' => 95,
        'duration_minutes' => 30,
    ])->assertRedirect();

    $catalogItem = TreatmentCatalog::withoutGlobalScopes()
        ->where('code', 'TEST-01')
        ->firstOrFail();

    $this->patch("/treatments/{$catalogItem->id}", [
        'name' => 'Tratamiento Editado',
        'category' => 'Preventiva',
        'default_price' => 110,
        'duration_minutes' => 45,
        'is_active' => true,
    ])->assertRedirect();
    expect($catalogItem->fresh()?->name)->toBe('Tratamiento Editado');

    $this->delete("/treatments/{$catalogItem->id}")->assertRedirect();
    expect(TreatmentCatalog::withoutGlobalScopes()->find($catalogItem->id))->toBeNull();

    // Paciente + documentos no firmados + perio + ortodoncia + plan
    $this->post('/patients', [
        'first_name' => 'Camila',
        'last_name' => 'Rojas',
        'document_type' => 'DNI',
        'document_number' => '72223334',
    ])->assertRedirect();
    $patient = Patient::withoutGlobalScopes()->where('document_number', '72223334')->firstOrFail();

    $this->patch("/patients/{$patient->id}", [
        'first_name' => 'Camila Elena',
        'last_name' => 'Rojas Vega',
        'document_type' => 'DNI',
        'document_number' => '72223334',
    ])->assertRedirect();
    expect($patient->fresh()?->first_name)->toBe('Camila Elena');

    $this->post("/patients/{$patient->id}/documents", [
        'type' => 'receta',
        'title' => 'Receta inicial',
        'content' => 'Amoxicilina 500mg',
    ])->assertRedirect();
    $doc = ClinicalDocument::withoutGlobalScopes()->firstOrFail();

    $this->patch("/patients/{$patient->id}/documents/{$doc->id}", [
        'title' => 'Receta ajustada',
        'content' => 'Amoxicilina + Ácido Clavulánico',
    ])->assertRedirect();
    expect($doc->fresh()?->title)->toBe('Receta ajustada');

    $this->delete("/patients/{$patient->id}/documents/{$doc->id}")->assertRedirect();
    expect(ClinicalDocument::withoutGlobalScopes()->find($doc->id))->toBeNull();

    $this->post("/patients/{$patient->id}/periodontograms", [
        'entries' => [
            '11' => ['pd' => [2, 2, 2, 2, 2, 2], 'bop' => true, 'plaque' => false, 'recession' => 0, 'mobility' => 0, 'furcation' => 0],
        ],
    ])->assertRedirect();
    $perio = Periodontogram::withoutGlobalScopes()->firstOrFail();
    $this->delete("/patients/{$patient->id}/periodontograms/{$perio->id}")->assertRedirect();
    expect(Periodontogram::withoutGlobalScopes()->find($perio->id))->toBeNull();

    $this->post("/patients/{$patient->id}/orthodontic-controls", [
        'control_date' => now()->toDateTimeString(),
        'stage' => 'Alineación',
        'hygiene_status' => 'buena',
        'clinical_notes' => 'Control mensual',
    ])->assertRedirect();
    $ortho = OrthodonticControl::withoutGlobalScopes()->firstOrFail();
    $this->delete("/patients/{$patient->id}/orthodontic-controls/{$ortho->id}")->assertRedirect();
    expect(OrthodonticControl::withoutGlobalScopes()->find($ortho->id))->toBeNull();

    $this->post("/patients/{$patient->id}/treatment-plans", ['title' => 'Plan A'])->assertRedirect();
    $plan = TreatmentPlan::withoutGlobalScopes()->firstOrFail();
    $this->post("/patients/{$patient->id}/treatment-plans/{$plan->id}/items", [
        'description' => 'Limpieza',
        'phase' => 'Fase 1',
        'priority' => 'alta',
        'quantity' => 1,
        'unit_price' => 100,
    ])->assertRedirect();
    $item = TreatmentPlanItem::withoutGlobalScopes()->firstOrFail();

    $this->delete("/patients/{$patient->id}/treatment-plan-items/{$item->id}")->assertRedirect();
    expect(TreatmentPlanItem::withoutGlobalScopes()->find($item->id))->toBeNull();

    $this->delete("/patients/{$patient->id}/treatment-plans/{$plan->id}")->assertRedirect();
    expect(TreatmentPlan::withoutGlobalScopes()->find($plan->id))->toBeNull();

    $this->delete("/patients/{$patient->id}")->assertRedirect('/patients');
    expect(Patient::withoutGlobalScopes()->find($patient->id))->toBeNull();
});

test('caja genera reportes termicos 80mm y 58mm por medios de pago para cierres y tickets individuales', function () {
    $clinic = ClinicProvisioner::createClinicWithDefaults([
        'name' => 'Clínica Térmica',
    ]);
    $admin = User::factory()->forClinic($clinic, UserRole::AdminConsultorio)->create();
    $patient = Patient::withoutGlobalScopes()->create([
        'clinic_id' => $clinic->id,
        'history_number' => 'HC-0001',
        'first_name' => 'Pedro',
        'last_name' => 'Salas',
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin);

    foreach (['efectivo' => 150, 'yape_plin' => 200, 'tarjeta' => 300, 'transferencia' => 100] as $method => $amount) {
        $this->post("/patients/{$patient->id}/payments", [
            'amount' => $amount,
            'payment_method' => $method,
            'document_type' => 'boleta',
            'concept' => "Cobro {$method}",
        ])->assertRedirect();
    }

    $today = Carbon::today()->toDateString();
    $summaryPdf = $this->get("/caja/thermal-summary?date={$today}&size=80")->assertOk();
    expect($summaryPdf->getContent())->toStartWith('%PDF-');

    $this->post('/caja/close', [
        'closing_date' => $today,
        'opening_amount' => 50,
        'notes' => 'Caja cuadrada sin novedades',
    ])->assertRedirect();

    $closing = CashClosing::withoutGlobalScopes()->firstOrFail();
    $closingPdf58 = $this->get("/caja/closings/{$closing->id}/thermal?size=58")->assertOk();
    expect($closingPdf58->getContent())->toStartWith('%PDF-');

    $payment = Payment::withoutGlobalScopes()->firstOrFail();
    $ticketPdf = $this->get("/payments/{$payment->id}/thermal?size=80")->assertOk();
    expect($ticketPdf->getContent())->toStartWith('%PDF-');
});

test('respaldo json exporta, valida integridad, reinicia datos operativos y restaura la base completa', function () {
    $clinic = ClinicProvisioner::createClinicWithDefaults([
        'name' => 'Clínica Respaldo',
    ]);
    $admin = User::factory()->forClinic($clinic, UserRole::AdminConsultorio)->create();

    Patient::withoutGlobalScopes()->create([
        'clinic_id' => $clinic->id,
        'history_number' => 'HC-0001',
        'first_name' => 'Paciente',
        'last_name' => 'Respaldado',
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin);

    $exportRes = $this->get('/clinic/backup/export-json')->assertOk();
    $jsonString = $exportRes->streamedContent();
    /** @var array<string, mixed> $decoded */
    $decoded = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
    expect($decoded['app'])->toBe('DentaFlow');
    expect($decoded['checksum'])->not->toBeEmpty();

    // Validación de confirmación y contraseña al reiniciar
    $this->post('/clinic/backup/reset', [
        'confirmation' => 'NO_SIRVE',
        'password' => 'password',
    ])->assertSessionHasErrors('confirmation');

    $this->post('/clinic/backup/reset', [
        'confirmation' => 'REINICIAR',
        'password' => 'clave-incorrecta',
    ])->assertSessionHasErrors('password');

    // Reinicio autorizado conservando catálogo
    $this->post('/clinic/backup/reset', [
        'confirmation' => 'REINICIAR',
        'password' => 'password',
        'keep_catalog' => true,
    ])->assertRedirect('/dashboard');

    expect(Patient::withoutGlobalScopes()->count())->toBe(0);
    expect(User::query()->find($admin->id))->not->toBeNull();
    expect(TreatmentCatalog::withoutGlobalScopes()->count())->toBeGreaterThan(0);

    // Respaldo alterado es rechazado
    $tampered = $decoded;
    $tampered['checksum'] = str_repeat('a', 64);
    $tamperedFile = UploadedFile::fake()->createWithContent(
        'tampered.json',
        (string) json_encode($tampered),
    );
    $this->post('/clinic/backup/restore', [
        'backup_file' => $tamperedFile,
        'password' => 'password',
    ])->assertSessionHas('error');

    // Restauración legítima recupera los pacientes
    $validFile = UploadedFile::fake()->createWithContent('backup.json', $jsonString);
    $this->post('/clinic/backup/restore', [
        'backup_file' => $validFile,
        'password' => 'password',
    ])->assertSessionHas('success');

    expect(Patient::withoutGlobalScopes()->count())->toBe(1);
    expect(Patient::withoutGlobalScopes()->first()?->first_name)->toBe('Paciente');
});

test('recordatorio whatsapp y confirmacion publica 1-clic actualiza la agenda sin login', function () {
    $clinic = ClinicProvisioner::createClinicWithDefaults([
        'name' => 'Clínica WhatsApp',
    ]);
    $dentist = User::factory()->forClinic($clinic, UserRole::Odontologo)->create();
    $patient = Patient::withoutGlobalScopes()->create([
        'clinic_id' => $clinic->id,
        'history_number' => 'HC-0001',
        'first_name' => 'Elena',
        'last_name' => 'Vargas',
        'phone' => '987654321',
        'created_by' => $dentist->id,
    ]);

    $appointment = Appointment::withoutGlobalScopes()->create([
        'clinic_id' => $clinic->id,
        'patient_id' => $patient->id,
        'dentist_id' => $dentist->id,
        'chair' => 'Sillón 1',
        'title' => 'Evaluación integral',
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addMinutes(30),
        'status' => AppointmentStatus::Reservada,
    ]);

    $this->actingAs($dentist)
        ->postJson("/agenda/{$appointment->id}/whatsapp-reminder")
        ->assertOk()
        ->assertJson(['ok' => true]);

    $appointment->refresh();
    expect($appointment->reminder_sent_at)->not->toBeNull();
    expect($appointment->confirmation_token)->not->toBeNull();

    // El paciente confirma sin haber iniciado sesión
    auth()->logout();

    $this->get("/confirmar-cita/{$appointment->confirmation_token}")->assertOk();

    $this->post("/confirmar-cita/{$appointment->confirmation_token}", [
        'action' => 'confirm',
    ])->assertRedirect();

    $appointment->refresh();
    expect($appointment->patient_confirmed_at)->not->toBeNull();
    expect($appointment->status)->toBe(AppointmentStatus::Confirmada);
});

test('laboratorio dental, control autoclave y kardex con salidas trazables funcionan completo', function () {
    $clinic = ClinicProvisioner::createClinicWithDefaults([
        'name' => 'Clínica Bioseguridad',
    ]);
    $admin = User::factory()->forClinic($clinic, UserRole::AdminConsultorio)->create();
    $patient = Patient::withoutGlobalScopes()->create([
        'clinic_id' => $clinic->id,
        'history_number' => 'HC-0001',
        'first_name' => 'Marcos',
        'last_name' => 'Díaz',
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin);

    // Laboratorio
    $this->post('/laboratorio', [
        'patient_id' => $patient->id,
        'dentist_id' => $admin->id,
        'lab_name' => 'Lab NovaDent',
        'work_type' => 'Corona Zirconia',
        'tooth_code' => '46',
        'shade' => 'A2',
        'cost' => 320,
        'status' => 'en_laboratorio',
    ])->assertRedirect();

    $order = LabOrder::withoutGlobalScopes()->firstOrFail();
    $this->patch("/laboratorio/{$order->id}", ['status' => 'recibido'])->assertRedirect();
    expect($order->fresh()?->status)->toBe('recibido');
    expect($order->fresh()?->received_at)->not->toBeNull();

    // Esterilización
    $this->post('/esterilizacion-kardex/cycles', [
        'autoclave_name' => 'Euronda B',
        'cycle_number' => 'C-100',
        'sterilized_at' => now()->toDateTimeString(),
        'temperature_c' => 134.0,
        'pressure_bar' => 2.1,
        'duration_minutes' => 35,
        'load_summary' => '3 kits cirugía',
        'chemical_indicator' => 'conforme',
        'biological_indicator' => 'pendiente',
    ])->assertRedirect();

    $cycle = SterilizationCycle::withoutGlobalScopes()->firstOrFail();
    $this->patch("/esterilizacion-kardex/cycles/{$cycle->id}", [
        'biological_indicator' => 'negativo_conforme',
    ])->assertRedirect();
    expect($cycle->fresh()?->biological_indicator)->toBe('negativo_conforme');

    // Kardex
    $this->post('/esterilizacion-kardex/items', [
        'code' => 'ANES-01',
        'name' => 'Lidocaína 2%',
        'category' => 'Anestésicos',
        'unit' => 'cajas',
        'lot_number' => 'L100',
        'current_stock' => 5,
        'min_stock' => 2,
    ])->assertRedirect();

    $item = InventoryItem::withoutGlobalScopes()->firstOrFail();
    $this->post("/esterilizacion-kardex/items/{$item->id}/movements", [
        'type' => 'salida',
        'quantity' => 2,
        'reason' => 'Consumo en cirugía',
        'patient_id' => $patient->id,
    ])->assertRedirect();
    expect((float) $item->fresh()?->current_stock)->toBe(3.0);

    // Salida mayor al stock es bloqueada
    $this->post("/esterilizacion-kardex/items/{$item->id}/movements", [
        'type' => 'salida',
        'quantity' => 10,
        'reason' => 'Exceso',
    ])->assertSessionHas('error');
});

test('facturacion ubl 2.1 sunat, consulta ruc, cuotas de financiamiento y comisiones operan correctamente', function () {
    $clinic = ClinicProvisioner::createClinicWithDefaults([
        'name' => 'Clínica Tributaria',
        'ruc' => '20601234567',
        'apiperu_token' => 'tok-sunat-demo',
    ]);
    $admin = User::factory()->forClinic($clinic, UserRole::AdminConsultorio)->create([
        'commission_rate' => 45.0,
    ]);
    $patient = Patient::withoutGlobalScopes()->create([
        'clinic_id' => $clinic->id,
        'history_number' => 'HC-0001',
        'first_name' => 'Lucía',
        'last_name' => 'Paredes',
        'created_by' => $admin->id,
    ]);

    Http::fake([
        'https://apiperu.dev/api/ruc' => Http::response([
            'success' => true,
            'data' => [
                'ruc' => '20555666777',
                'nombre_o_razon_social' => 'EMPRESA DENTAL DEMO SAC',
                'estado' => 'ACTIVO',
                'condicion' => 'HABIDO',
                'direccion_completa' => 'Av. Javier Prado 500, Lima',
            ],
        ], 200),
    ]);

    $this->actingAs($admin);

    $this->postJson('/api-integrations/ruc-lookup', ['ruc' => '20555666777'])
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'data' => ['name' => 'EMPRESA DENTAL DEMO SAC'],
        ]);

    // Emisión de factura con IGV 18% y XML UBL 2.1
    $this->post("/patients/{$patient->id}/payments", [
        'amount' => 118.00,
        'payment_method' => 'transferencia',
        'document_type' => 'factura',
        'customer_doc_type' => '6',
        'customer_doc_number' => '20555666777',
        'customer_name' => 'EMPRESA DENTAL DEMO SAC',
        'customer_address' => 'Av. Javier Prado 500',
        'concept' => 'Implante dental',
    ])->assertRedirect();

    $payment = Payment::withoutGlobalScopes()->firstOrFail();
    expect((float) $payment->tax_subtotal)->toBe(100.0);
    expect((float) $payment->tax_igv)->toBe(18.0);
    expect($payment->sunat_series)->toBe('F001');

    $xmlRes = $this->get("/payments/{$payment->id}/ubl-xml")->assertOk();
    expect($xmlRes->getContent())->toContain('<cbc:UBLVersionID>2.1</cbc:UBLVersionID>');
    expect($xmlRes->getContent())->toContain('<cbc:TaxAmount currencyID="PEN">18.00</cbc:TaxAmount>');

    // Plan de financiamiento por cuotas y pago de primera cuota
    $this->post("/patients/{$patient->id}/installments/generate", [
        'total_amount' => 900,
        'down_payment' => 0,
        'installments_count' => 3,
        'first_due_date' => now()->toDateString(),
        'frequency_days' => 30,
    ])->assertRedirect();

    expect(PaymentInstallment::withoutGlobalScopes()->count())->toBe(3);
    $firstQuota = PaymentInstallment::withoutGlobalScopes()->orderBy('installment_number')->firstOrFail();

    $this->post("/patients/{$patient->id}/installments/{$firstQuota->id}/pay", [
        'amount' => 300,
        'payment_method' => 'yape_plin',
        'document_type' => 'boleta',
    ])->assertRedirect();

    expect($firstQuota->fresh()?->status)->toBe('pagado');
    expect(Payment::withoutGlobalScopes()->count())->toBe(2);

    $this->get('/reportes')->assertOk();
});

test('homologacion HCE internacional exporta HL7 FHIR R4, notas SOAP con hash SHA-256 y mediciones Rx en mm', function () {
    Storage::fake('public');

    $clinic = ClinicProvisioner::createClinicWithDefaults([
        'name' => 'Clínica HCE Internacional',
        'ruc' => '20601112223',
    ]);
    $dentist = User::factory()->forClinic($clinic, UserRole::Odontologo)->create();
    $patient = Patient::withoutGlobalScopes()->create([
        'clinic_id' => $clinic->id,
        'history_number' => 'HC-0001',
        'first_name' => 'Daniel',
        'last_name' => 'Quispe',
        'blood_type' => 'O+',
        'created_by' => $dentist->id,
    ]);

    expect($patient->fhir_uuid)->not->toBeNull();

    $this->actingAs($dentist);

    $this->put("/patients/{$patient->id}/medical-history", [
        'chief_complaint' => 'Control implantológico',
        'asa_classification' => 'ASA II',
        'family_history' => 'Padre con HTA',
        'review_of_systems' => ['cardiovascular' => true],
        'vital_signs' => [
            'pa' => '120/80',
            'fc' => '72',
            'weight_kg' => '70',
            'height_cm' => '170',
            'bmi' => '24.2',
        ],
        'stomatognathic_exam' => ['angle_class' => 'Clase I'],
        'definitive_diagnosis' => 'Edentulismo parcial',
        'cie10_code' => 'K08.1',
    ])->assertRedirect();

    $this->post("/patients/{$patient->id}/evolutions", [
        'attended_at' => now()->toDateTimeString(),
        'procedure_summary' => 'Colocación pilar implante 36',
        'clinical_notes' => 'Procedimiento quirúrgico sin complicaciones.',
        'soap_subjective' => 'Sin dolor',
        'soap_objective' => 'Encía sana',
        'soap_assessment' => 'Osteointegración favorable',
        'soap_plan' => 'Toma de impresión definitiva en 7 días',
        'cie10_code' => 'K08.1',
        'sign_now' => true,
    ])->assertRedirect();

    $evol = ClinicalEvolution::withoutGlobalScopes()->firstOrFail();
    expect($evol->signature_hash)->not->toBeNull();
    expect(strlen((string) $evol->signature_hash))->toBe(64);

    // Bundle HL7 FHIR R4
    $fhirRes = $this->get("/patients/{$patient->id}/fhir-bundle")->assertOk();
    /** @var array<string, mixed> $bundle */
    $bundle = json_decode($fhirRes->streamedContent(), true, 512, JSON_THROW_ON_ERROR);
    expect($bundle['resourceType'])->toBe('Bundle');
    expect($bundle['total'])->toBeGreaterThan(2);

    // Visor radiográfico medible en mm
    $this->post("/patients/{$patient->id}/images", [
        'category' => 'radiografia',
        'title' => 'Rx Periapical 36',
        'tooth_code' => '36',
        'taken_at' => now()->toDateString(),
        'image' => UploadedFile::fake()->image('rx36.jpg', 800, 600),
    ])->assertRedirect();

    $img = PatientImage::withoutGlobalScopes()->firstOrFail();
    $this->patch("/patients/{$patient->id}/images/{$img->id}/annotations", [
        'calibration_mm_per_px' => 0.05,
        'measurements' => [
            ['id' => 'm1', 'x1' => 100, 'y1' => 100, 'x2' => 100, 'y2' => 320, 'mm' => 11.0, 'label' => 'Conducto'],
        ],
        'pins' => [
            ['id' => 'p1', 'x' => 150, 'y' => 250, 'label' => 'Ápice 36', 'color' => '#F59E0B'],
        ],
        'filters' => ['brightness' => 110, 'contrast' => 125, 'invert' => true],
    ])->assertRedirect();

    expect($img->fresh()?->annotations['calibration_mm_per_px'])->toBe(0.05);
});

test('manual oficial en linea y descarga pdf requieren sesion iniciada e incluyen capturas reales', function () {
    $this->get('/manual')->assertRedirect(route('login'));
    $this->get('/manual/pdf')->assertRedirect(route('login'));

    $clinic = ClinicProvisioner::createClinicWithDefaults([
        'name' => 'Clínica Manual',
    ]);
    $dentist = User::factory()->forClinic($clinic, UserRole::Odontologo)->create();

    $this->actingAs($dentist);

    $this->get('/manual')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('manual/index')
            ->has('chapters', 15)
            ->has('roleMatrix', 12)
            ->where('pdfUrl', route('manual.pdf')),
        );

    foreach (UserManualCatalog::chapters() as $chapter) {
        expect($chapter['screenshots'])->not->toBeEmpty();
        foreach ($chapter['screenshots'] as $shot) {
            expect(is_file(public_path('images/manual/'.$shot['file'])))->toBeTrue();
        }
    }

    $manualHtml = view('pdf.user-manual', [
        'clinic' => $clinic,
        'logoDataUri' => null,
        'chapters' => UserManualCatalog::chapters(),
        'roleMatrix' => UserManualCatalog::roleMatrix(),
        'generatedBy' => $dentist->name,
        'generatedAt' => now(),
        'manualVersion' => 'v1.2 · Homologación HCE & Suite IA',
    ])->render();

    expect($manualHtml)
        ->toContain('Copiloto SOAP por Voz')
        ->toContain('Contralor IA')
        ->toContain('CRM Odontológico WhatsApp IA')
        ->toContain('Reserva Online 24/7')
        ->toContain('Reportes IA')
        ->toContain('Rayos X IA')
        ->toContain('PHP 8.3');

    $pdf = $this->get('/manual/pdf')->assertOk();
    expect(substr((string) $pdf->getContent(), 0, 5))->toBe('%PDF-');
    expect(strlen((string) $pdf->getContent()))->toBeGreaterThan(50000);
});

test('funciones ia y crm inspiradas en dentalink y doctocliq funcionan de extremo a extremo', function () {
    $clinic = ClinicProvisioner::createClinicWithDefaults([
        'name' => 'Clínica IA Dental',
    ]);
    $admin = User::factory()->forClinic($clinic, UserRole::AdminConsultorio)->create();
    $patient = Patient::withoutGlobalScopes()->create([
        'clinic_id' => $clinic->id,
        'history_number' => 'HC-0001',
        'first_name' => 'Sofía',
        'last_name' => 'Mendoza',
        'document_type' => 'DNI',
        'document_number' => '45678123',
        'phone' => '987654321',
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin);

    // 1. Resumen Clínico IA Pre-Consulta
    $this->postJson("/patients/{$patient->id}/ai-clinical-summary")
        ->assertOk()
        ->assertJsonStructure(['provider', 'risk_level', 'headline', 'bullets', 'recommended_next_step']);

    // 2. Copiloto SOAP por Voz -> Estructura SOAP + CIE-10
    $this->postJson("/patients/{$patient->id}/ai-parse-soap", [
        'transcript' => 'Paciente refiere dolor al frío en pieza 46. Al examen clínico caries dentinaria profunda sin fístula. Diagnóstico pulpitis reversible. Se realizó protección pulpar indirecta con ionómero.',
    ])->assertOk()
        ->assertJsonPath('cie10_code', 'K04.0');

    // 3. Análisis Diagnóstico IA en Visor Radiográfico
    Storage::fake('local');
    $this->post("/patients/{$patient->id}/images", [
        'category' => 'radiografia',
        'title' => 'Rx 46',
        'tooth_code' => '46',
        'taken_at' => now()->toDateString(),
        'image' => UploadedFile::fake()->image('rx46.jpg', 400, 400),
    ])->assertRedirect();
    $img = PatientImage::withoutGlobalScopes()->firstOrFail();

    $this->postJson("/patients/{$patient->id}/images/{$img->id}/ai-analyze")
        ->assertOk()
        ->assertJsonStructure(['provider', 'summary', 'disclaimer', 'findings']);
    expect($img->fresh()?->ai_analysis)->not->toBeNull();

    // 4. Contralor IA
    $this->get('/contralor-ia')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('contralor/index')
            ->has('audit.issues'),
        );

    // 5. Reportes Conversacionales IA
    $this->postJson('/reportes/ai-ask', [
        'question' => '¿Cuánto recaudamos este mes y por qué medio de pago?',
    ])->assertOk()
        ->assertJsonStructure(['provider', 'answer', 'highlights']);

    // 6. CRM Odontológico y Redacción WhatsApp IA
    $this->get('/crm')->assertOk();
    $this->patch("/crm/patients/{$patient->id}", [
        'crm_stage' => 'presupuesto_pendiente',
        'crm_notes' => 'Enviar facilidades en cuotas.',
    ])->assertRedirect();
    expect($patient->fresh()?->crm_stage)->toBe('presupuesto_pendiente');

    $this->postJson("/crm/patients/{$patient->id}/whatsapp-draft", [
        'campaign_type' => 'presupuesto',
    ])->assertOk()
        ->assertJsonStructure(['provider', 'message', 'whatsapp_url']);

    // 7. Reserva Online 24/7 Pública (sin login)
    $this->post('/logout');
    $this->get('/reservar-cita')->assertOk();

    $futureDate = Carbon::tomorrow()->toDateString();
    $slotsRes = $this->getJson("/reservar-cita/disponibilidad?dentist_id={$admin->id}&date={$futureDate}&duration=30")
        ->assertOk();
    $firstSlot = $slotsRes->json('slots.0.start');
    expect($firstSlot)->not->toBeNull();

    $this->post('/reservar-cita', [
        'dentist_id' => $admin->id,
        'service_title' => 'Evaluación Integral',
        'date' => $futureDate,
        'start_time' => $firstSlot,
        'duration' => 30,
        'first_name' => 'Diego',
        'last_name' => 'Vargas',
        'document_number' => '71234567',
        'phone' => '999888777',
    ])->assertRedirect();

    $onlineAppt = Appointment::withoutGlobalScopes()->latest('id')->firstOrFail();
    expect($onlineAppt->title)->toBe('Evaluación Integral');
    expect($onlineAppt->status)->toBe(AppointmentStatus::Reservada);
});
