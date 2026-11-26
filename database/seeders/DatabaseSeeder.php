<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\ClinicalDocument;
use App\Models\ClinicalEvolution;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\LabOrder;
use App\Models\MedicalHistory;
use App\Models\Odontogram;
use App\Models\OdontogramFinding;
use App\Models\OrthodonticControl;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentInstallment;
use App\Models\Periodontogram;
use App\Models\SterilizationCycle;
use App\Models\TreatmentCatalog;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ClinicProvisioner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $clinic = ClinicProvisioner::createClinicWithDefaults([
            'name' => 'Clínica Dental Sonrisa Integral',
            'ruc' => '20601234567',
            'slogan' => 'Odontología Integral, Estética y Especialidades',
            'phone' => '(01) 442-8890',
            'email' => 'contacto@sonrisaintegral.pe',
            'address' => 'Av. Arequipa 2450, Lince, Lima',
            'primary_color' => '#0E8FA3',
            'currency_symbol' => 'S/',
            'chairs' => ['Sillón 1 - General', 'Sillón 2 - Endodoncia', 'Sillón 3 - Pediatría'],
            'settings' => [
                'odontogram_notation' => 'FDI',
                'auto_lock_minutes' => 15,
            ],
        ]);

        $admin = User::create([
            'clinic_id' => $clinic->id,
            'name' => 'Dr. Mateo Salazar',
            'email' => 'admin@sonrisa.test',
            'role' => UserRole::AdminConsultorio,
            'specialty' => 'Rehabilitación Oral',
            'license_number' => 'COP-28419',
            'phone' => '998112233',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $dentist = User::create([
            'clinic_id' => $clinic->id,
            'name' => 'Dra. Valeria Vega',
            'email' => 'dra.vega@sonrisa.test',
            'role' => UserRole::Odontologo,
            'specialty' => 'Endodoncia y Operatoria',
            'license_number' => 'COP-31904',
            'phone' => '997445566',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $assistant = User::create([
            'clinic_id' => $clinic->id,
            'name' => 'Camila Paredes',
            'email' => 'recepcion@sonrisa.test',
            'role' => UserRole::Asistente,
            'phone' => '996778899',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        User::create([
            'clinic_id' => $clinic->id,
            'name' => 'Luis Mendoza',
            'email' => 'caja@sonrisa.test',
            'role' => UserRole::Contador,
            'phone' => '995332211',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        // Paciente 1: Lucía Fernández (Adulto con historia, odontograma evolutivo, plan, pagos, documentos y ortodoncia)
        $patient1 = Patient::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'history_number' => 'HC-0001',
            'first_name' => 'Lucía',
            'last_name' => 'Fernández Quispe',
            'document_type' => 'DNI',
            'document_number' => '45891234',
            'birth_date' => '1991-04-18',
            'gender' => 'F',
            'phone' => '987654321',
            'email' => 'lucia.fernandez@ejemplo.pe',
            'address' => 'Jr. Los Laureles 310, San Isidro',
            'occupation' => 'Arquitecta',
            'insurance_provider' => 'Rimac Salud',
            'insurance_number' => 'RM-883412',
            'tags' => ['VIP', 'ortodoncia'],
            'created_by' => $assistant->id,
        ]);

        MedicalHistory::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient1->id,
            'chief_complaint' => 'Sensibilidad al frío en molar inferior derecho desde hace 2 semanas.',
            'current_illness' => 'Dolor provocado de corta duración ante bebidas frías en zona posterior derecha.',
            'medical_history_notes' => 'Alergia confirmada a la penicilina. Control médico anual sin complicaciones.',
            'dental_history_notes' => 'Restauraciones previas hace 3 años. Limpieza dental semestral.',
            'allergies' => 'Penicilina y derivados betalactámicos',
            'current_medications' => 'Ninguna actualmente',
            'chronic_conditions' => [],
            'habits' => ['Bruxismo nocturno'],
            'is_pregnant' => false,
            'medical_alerts' => [
                ['type' => 'alergia', 'label' => 'Alergia a Penicilina', 'severity' => 'alta'],
                ['type' => 'habito', 'label' => 'Bruxismo nocturno', 'severity' => 'media'],
            ],
            'vital_signs' => ['pa' => '115/75 mmHg', 'fc' => '70 lpm', 'fr' => '16 rpm', 'temp' => '36.5 °C', 'sato2' => '99%'],
            'extraoral_exam' => 'Simetría facial conservada, sin adenopatías palpables.',
            'intraoral_exam' => 'Mucosas hidratadas. Caries oclusal profunda en pieza 46, restauración filtrada en 36.',
            'tmj_exam' => 'Leve chasquido bilateral sin limitación de apertura (44 mm).',
            'soft_tissues_exam' => 'Encía con inflamación leve localizada en sector anteroinferior.',
            'periodontal_exam' => 'Profundidad de sondaje 2-3 mm, cálculo supragingival leve.',
            'occlusion_exam' => 'Clase I molar bilateral, overjet 2 mm, overbite 2 mm, facetas de desgaste en caninos.',
            'presumptive_diagnosis' => 'Caries de la dentina en pieza 46; Pulpitis reversible.',
            'definitive_diagnosis' => 'Caries de la dentina profunda (46) y restauración defectuosa (36).',
            'cie10_code' => 'K02.1',
            'updated_by' => $dentist->id,
        ]);

        $odoInitial = Odontogram::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient1->id,
            'created_by' => $dentist->id,
            'type' => 'inicial',
            'dentition' => 'adulto',
            'notation' => 'FDI',
            'recorded_at' => now()->subDays(15),
            'notes' => 'Registro de primera consulta integral.',
        ]);

        $initialFindings = [
            ['tooth_code' => '46', 'surface' => 'O', 'condition_code' => 'caries', 'status' => 'por_realizar', 'notes' => 'Caries dentinaria profunda'],
            ['tooth_code' => '36', 'surface' => 'O', 'condition_code' => 'obturacion', 'status' => 'por_realizar', 'notes' => 'Resina filtrada para recambio'],
            ['tooth_code' => '16', 'surface' => 'O', 'condition_code' => 'obturacion', 'status' => 'realizado', 'notes' => 'Resina en buen estado'],
            ['tooth_code' => '26', 'surface' => 'FULL', 'condition_code' => 'endodoncia', 'status' => 'realizado', 'notes' => 'Endodoncia previa funcional'],
            ['tooth_code' => '26', 'surface' => 'FULL', 'condition_code' => 'corona', 'status' => 'realizado', 'notes' => 'Corona zirconia adaptada'],
            ['tooth_code' => '18', 'surface' => 'FULL', 'condition_code' => 'ausente', 'status' => 'ausente', 'notes' => 'Exodoncia previa'],
            ['tooth_code' => '28', 'surface' => 'FULL', 'condition_code' => 'ausente', 'status' => 'ausente', 'notes' => 'Exodoncia previa'],
            ['tooth_code' => '48', 'surface' => 'FULL', 'condition_code' => 'impactado', 'status' => 'por_realizar', 'notes' => 'Tercera molar semiimpactada'],
        ];

        foreach ($initialFindings as $f) {
            OdontogramFinding::withoutGlobalScopes()->create([
                'clinic_id' => $clinic->id,
                'odontogram_id' => $odoInitial->id,
                'recorded_by' => $dentist->id,
                ...$f,
            ]);
        }

        $odoEvol = Odontogram::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient1->id,
            'created_by' => $dentist->id,
            'type' => 'evolutivo',
            'dentition' => 'adulto',
            'notation' => 'FDI',
            'recorded_at' => now()->subDays(2),
            'notes' => 'Actualización post-restauración pieza 36.',
        ]);

        foreach ($initialFindings as $f) {
            $status = ($f['tooth_code'] === '36') ? 'realizado' : $f['status'];
            $notes = ($f['tooth_code'] === '36') ? 'Recambio de resina completado' : $f['notes'];
            OdontogramFinding::withoutGlobalScopes()->create([
                'clinic_id' => $clinic->id,
                'odontogram_id' => $odoEvol->id,
                'recorded_by' => $dentist->id,
                'tooth_code' => $f['tooth_code'],
                'surface' => $f['surface'],
                'condition_code' => $f['condition_code'],
                'status' => $status,
                'notes' => $notes,
            ]);
        }

        $catalogResina = TreatmentCatalog::withoutGlobalScopes()->where('clinic_id', $clinic->id)->where('code', 'OPER-01')->first();
        $catalogCirugia = TreatmentCatalog::withoutGlobalScopes()->where('clinic_id', $clinic->id)->where('code', 'CIRU-02')->first();

        $plan1 = TreatmentPlan::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient1->id,
            'created_by' => $dentist->id,
            'title' => 'Rehabilitación funcional posterior',
            'status' => 'en_curso',
            'discount_amount' => 30.00,
            'valid_until' => now()->addDays(30)->toDateString(),
            'approved_at' => now()->subDays(10),
            'notes' => 'Paciente aprobó fase operatoria. Pendiente programar cirugía de 48.',
        ]);

        TreatmentPlanItem::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'treatment_plan_id' => $plan1->id,
            'treatment_catalog_id' => $catalogResina?->id,
            'tooth_code' => '36',
            'surface' => 'O',
            'description' => 'Recambio de restauración con resina compuesta (Pieza 36 O)',
            'phase' => 'Fase 2: Restauradora',
            'priority' => 'alta',
            'quantity' => 1,
            'unit_price' => 150.00,
            'discount' => 0,
            'status' => 'realizado',
            'completed_at' => now()->subDays(2),
            'completed_by' => $dentist->id,
        ]);

        TreatmentPlanItem::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'treatment_plan_id' => $plan1->id,
            'treatment_catalog_id' => $catalogResina?->id,
            'tooth_code' => '46',
            'surface' => 'O',
            'description' => 'Restauración con resina compuesta profunda + base cavitaria (Pieza 46 O)',
            'phase' => 'Fase 2: Restauradora',
            'priority' => 'alta',
            'quantity' => 1,
            'unit_price' => 180.00,
            'discount' => 0,
            'status' => 'pendiente',
        ]);

        TreatmentPlanItem::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'treatment_plan_id' => $plan1->id,
            'treatment_catalog_id' => $catalogCirugia?->id,
            'tooth_code' => '48',
            'surface' => 'FULL',
            'description' => 'Exodoncia quirúrgica de tercera molar semiimpactada (Pieza 48)',
            'phase' => 'Fase 3: Rehabilitación/Mantenimiento',
            'priority' => 'media',
            'quantity' => 1,
            'unit_price' => 450.00,
            'discount' => 0,
            'status' => 'pendiente',
        ]);

        // Pagos de Lucía
        Payment::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient1->id,
            'treatment_plan_id' => $plan1->id,
            'received_by' => $assistant->id,
            'receipt_number' => 'BOL-0001',
            'document_type' => 'boleta',
            'payment_method' => 'yape_plin',
            'amount' => 150.00,
            'paid_at' => now()->subDays(2)->setHour(11)->setMinute(20),
            'concept' => 'Pago recambio de resina pieza 36',
            'status' => 'registrado',
        ]);

        Payment::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient1->id,
            'treatment_plan_id' => $plan1->id,
            'received_by' => $assistant->id,
            'receipt_number' => 'BOL-0002',
            'document_type' => 'boleta',
            'payment_method' => 'tarjeta',
            'amount' => 200.00,
            'paid_at' => now()->setHour(9)->setMinute(40),
            'concept' => 'Adelanto rehabilitación pieza 46 y cirugía 48',
            'status' => 'registrado',
        ]);

        // Documentos clínicos de Lucía
        ClinicalDocument::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient1->id,
            'created_by' => $dentist->id,
            'type' => 'receta',
            'title' => 'Receta · Indicaciones post-restauración profunda',
            'content' => "1. Ibuprofeno 400 mg: 1 tableta cada 8 horas por 2 días solo en caso de sensibilidad.\n2. Cepillado suave con pasta desensibilizante durante 7 días.",
            'issued_at' => now()->subDays(2),
        ]);

        // Control periodontal y ortodoncia de Lucía
        Periodontogram::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient1->id,
            'created_by' => $dentist->id,
            'recorded_at' => now()->subDays(10),
            'entries' => [
                '16' => ['pd' => [3, 2, 3, 2, 2, 3], 'bop' => false, 'plaque' => true, 'recession' => 0, 'mobility' => 0, 'furcation' => 0],
                '11' => ['pd' => [2, 2, 2, 2, 1, 2], 'bop' => false, 'plaque' => false, 'recession' => 0, 'mobility' => 0, 'furcation' => 0],
                '26' => ['pd' => [3, 2, 3, 3, 2, 3], 'bop' => true, 'plaque' => true, 'recession' => 1, 'mobility' => 0, 'furcation' => 0],
                '36' => ['pd' => [3, 2, 3, 2, 2, 3], 'bop' => false, 'plaque' => false, 'recession' => 0, 'mobility' => 0, 'furcation' => 0],
                '46' => ['pd' => [4, 3, 3, 3, 2, 3], 'bop' => true, 'plaque' => true, 'recession' => 0, 'mobility' => 0, 'furcation' => 0],
            ],
            'bleeding_index' => 40.00,
            'plaque_index' => 60.00,
            'notes' => 'Sangrado leve localizado en zona de 26 y 46. Refuerzo de hilo dental.',
        ]);

        OrthodonticControl::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient1->id,
            'dentist_id' => $admin->id,
            'control_date' => now()->subDays(20),
            'stage' => 'Contención',
            'upper_archwire' => 'Contenedor fijo + acetato nocturno',
            'lower_archwire' => 'Retenedor lingual 3-3',
            'elastics' => 'No aplica',
            'hygiene_status' => 'buena',
            'clinical_notes' => 'Estabilidad oclusal conservada. Se indica uso nocturno continuo de placa miorelajante por bruxismo.',
            'next_control_date' => now()->addMonths(3)->toDateString(),
        ]);

        // Evolución clínica firmada + corrección
        $signedEvol = ClinicalEvolution::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient1->id,
            'user_id' => $dentist->id,
            'attended_at' => now()->subDays(2)->setHour(10)->setMinute(30),
            'procedure_summary' => 'Recambio de resina oclusal en pieza 36',
            'clinical_notes' => 'Anestesia infiltrativa con lidocaína 2% + epinefrina. Aislamiento absoluto, retiro de resina filtrada sin compromiso pulpar. Restauración con resina nanohíbrida tono A2 y ajuste oclusal.',
            'prescription_notes' => 'Evitar alimentos duros o muy pigmentados por 24 horas.',
            'signed_at' => now()->subDays(2)->setHour(11)->setMinute(15),
            'signed_by' => $dentist->id,
        ]);

        ClinicalEvolution::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient1->id,
            'user_id' => $dentist->id,
            'attended_at' => now()->subDays(2)->setHour(11)->setMinute(25),
            'procedure_summary' => 'Aclaración sobre protección pulpar indirecta (Pieza 36)',
            'clinical_notes' => 'Se precisa que se colocó recubrimiento con ionómero de vidrio fotopolimerizable como base cavitaria en pared pulpar.',
            'parent_evolution_id' => $signedEvol->id,
            'correction_reason' => 'Agregar detalle de material de base cavitaria en registro firmado.',
            'signed_at' => now()->subDays(2)->setHour(11)->setMinute(30),
            'signed_by' => $dentist->id,
        ]);

        // Paciente 2: Niño con odontograma temporal
        $patient2 = Patient::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'history_number' => 'HC-0002',
            'first_name' => 'Mateo',
            'last_name' => 'Chávez Rojas',
            'document_type' => 'DNI',
            'document_number' => '78945612',
            'birth_date' => now()->subYears(7)->toDateString(),
            'gender' => 'M',
            'phone' => '981223344',
            'guardian_name' => 'Rosa Rojas Medina',
            'guardian_phone' => '981223344',
            'guardian_relationship' => 'Madre',
            'tags' => ['niño'],
            'created_by' => $assistant->id,
        ]);

        MedicalHistory::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient2->id,
            'chief_complaint' => 'Control preventivo y sellantes en molares temporales.',
            'allergies' => 'Sin alergias conocidas',
            'medical_alerts' => [],
            'presumptive_diagnosis' => 'Dentición mixta saludable con surcos profundos en 75 y 85',
            'cie10_code' => 'K02.0',
            'updated_by' => $admin->id,
        ]);

        $odoChild = Odontogram::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient2->id,
            'created_by' => $admin->id,
            'type' => 'inicial',
            'dentition' => 'nino',
            'notation' => 'FDI',
            'recorded_at' => now()->subDays(5),
            'notes' => 'Evaluación odontopediátrica inicial.',
        ]);

        OdontogramFinding::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'odontogram_id' => $odoChild->id,
            'tooth_code' => '55',
            'surface' => 'O',
            'condition_code' => 'sellante',
            'status' => 'realizado',
            'recorded_by' => $admin->id,
        ]);
        OdontogramFinding::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'odontogram_id' => $odoChild->id,
            'tooth_code' => '75',
            'surface' => 'O',
            'condition_code' => 'caries',
            'status' => 'por_realizar',
            'recorded_by' => $admin->id,
        ]);

        // Paciente 3: Alerta médica crítica
        $patient3 = Patient::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'history_number' => 'HC-0003',
            'first_name' => 'Carlos',
            'last_name' => 'Gutiérrez Huamán',
            'document_type' => 'DNI',
            'document_number' => '09451122',
            'birth_date' => '1959-09-12',
            'gender' => 'M',
            'phone' => '975664433',
            'email' => 'cgutierrez@ejemplo.pe',
            'tags' => ['crónico'],
            'created_by' => $assistant->id,
        ]);

        MedicalHistory::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient3->id,
            'chief_complaint' => 'Movilidad en incisivos inferiores y sangrado al cepillado.',
            'allergies' => 'Látex (usar guantes de nitrilo)',
            'current_medications' => 'Losartán 50 mg/día, Warfarina 5 mg/día',
            'chronic_conditions' => ['Hipertensión arterial', 'Tratamiento anticoagulante'],
            'medical_alerts' => [
                ['type' => 'anticoagulantes', 'label' => 'Toma Warfarina (Anticoagulante)', 'severity' => 'alta'],
                ['type' => 'hipertension', 'label' => 'Hipertensión arterial controlada', 'severity' => 'alta'],
                ['type' => 'alergia', 'label' => 'Alergia al Látex', 'severity' => 'alta'],
            ],
            'vital_signs' => ['pa' => '135/85 mmHg', 'fc' => '76 lpm', 'temp' => '36.6 °C'],
            'presumptive_diagnosis' => 'Periodontitis crónica generalizada',
            'cie10_code' => 'K05.3',
            'updated_by' => $admin->id,
        ]);

        // Citas del día y semana
        $today = Carbon::today();
        Appointment::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient1->id,
            'dentist_id' => $dentist->id,
            'chair' => 'Sillón 1 - General',
            'title' => 'Restauración pieza 46 (Resina profunda)',
            'starts_at' => $today->copy()->setHour(9)->setMinute(30),
            'ends_at' => $today->copy()->setHour(10)->setMinute(15),
            'status' => AppointmentStatus::EnSala,
            'reason' => 'Continuación plan de tratamiento',
            'created_by' => $assistant->id,
        ]);

        Appointment::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient2->id,
            'dentist_id' => $admin->id,
            'chair' => 'Sillón 3 - Pediatría',
            'title' => 'Curación pieza 75 y sellantes',
            'starts_at' => $today->copy()->setHour(11)->setMinute(0),
            'ends_at' => $today->copy()->setHour(11)->setMinute(45),
            'status' => AppointmentStatus::Confirmada,
            'reason' => 'Odontopediatría',
            'created_by' => $assistant->id,
        ]);

        Appointment::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient3->id,
            'dentist_id' => $admin->id,
            'chair' => 'Sillón 1 - General',
            'title' => 'Evaluación periodontal e INR',
            'starts_at' => $today->copy()->setHour(15)->setMinute(0),
            'ends_at' => $today->copy()->setHour(15)->setMinute(45),
            'status' => AppointmentStatus::Reservada,
            'reason' => 'Control de sangrado gingival',
            'created_by' => $assistant->id,
        ]);

        Appointment::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient1->id,
            'dentist_id' => $dentist->id,
            'chair' => 'Sillón 2 - Endodoncia',
            'title' => 'Control oclusal + toma de radiografía 48',
            'starts_at' => $today->copy()->addDays(2)->setHour(10)->setMinute(0),
            'ends_at' => $today->copy()->addDays(2)->setHour(10)->setMinute(30),
            'status' => AppointmentStatus::Reservada,
            'is_waitlist' => true,
            'reason' => 'Lista de espera para adelantar cirugía',
            'created_by' => $assistant->id,
        ]);

        AuditLogger::log('create', $patient1, 'Se inicializó la historia clínica HC-0001 en Clínica Dental Sonrisa Integral', [], null, $clinic->id);

        // Laboratorio dental
        LabOrder::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient1->id,
            'dentist_id' => $admin->id,
            'order_code' => 'LAB-0001',
            'lab_name' => 'Laboratorio Protésico NovaDent',
            'work_type' => 'Corona de Zirconia Monolítica',
            'tooth_code' => '46',
            'shade' => 'A2',
            'material' => 'Zirconio multicapa',
            'sent_at' => $today->copy()->subDays(3),
            'due_date' => $today->copy()->addDays(2),
            'cost' => 380.00,
            'status' => 'en_laboratorio',
            'notes' => 'Verificar ajuste marginal en modelo troquelado.',
            'created_by' => $admin->id,
        ]);

        LabOrder::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient3->id,
            'dentist_id' => $admin->id,
            'order_code' => 'LAB-0002',
            'lab_name' => 'Estética Dental Lima Sur',
            'work_type' => 'Férula de Descarga Oclusal Rígida',
            'tooth_code' => 'ARC-SUP',
            'shade' => 'Transparente',
            'material' => 'Acrílico termocurado',
            'sent_at' => $today->copy()->subDays(5),
            'due_date' => $today->copy()->subDay(),
            'received_at' => $today->copy(),
            'cost' => 160.00,
            'status' => 'recibido',
            'notes' => 'Lista para prueba e instalación clínica.',
            'created_by' => $admin->id,
        ]);

        // Esterilización / Autoclave y Kardex de insumos críticos
        SterilizationCycle::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'operator_id' => $assistant->id,
            'autoclave_name' => 'Autoclave Clase B Euronda 24L',
            'cycle_number' => 'CICLO-2026-112',
            'sterilized_at' => $today->copy()->setHour(8)->setMinute(15),
            'temperature_c' => 134.0,
            'pressure_bar' => 2.15,
            'duration_minutes' => 38,
            'load_summary' => '4 cajas quirúrgicas exodoncia, 6 kits de diagnóstico, 2 kits endodoncia.',
            'chemical_indicator' => 'conforme',
            'biological_indicator' => 'negativo_conforme',
            'notes' => 'Test Bowie-Dick conforme.',
        ]);

        SterilizationCycle::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'operator_id' => $assistant->id,
            'autoclave_name' => 'Autoclave Clase B Euronda 24L',
            'cycle_number' => 'CICLO-2026-113',
            'sterilized_at' => $today->copy()->setHour(13)->setMinute(40),
            'temperature_c' => 134.0,
            'pressure_bar' => 2.12,
            'duration_minutes' => 35,
            'load_summary' => '3 piezas de mano alta velocidad, 5 fórceps, instrumental periodontal.',
            'chemical_indicator' => 'conforme',
            'biological_indicator' => 'pendiente',
            'notes' => 'En incubación de control biológico (24 h).',
        ]);

        $item1 = InventoryItem::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'code' => 'INS-ANES-01',
            'name' => 'Lidocaína 2% c/ Epinefrina 1:80,000 (Caja x 50 carpules)',
            'category' => 'Anestésicos',
            'unit' => 'cajas',
            'lot_number' => 'LOTE-AL928',
            'expires_at' => $today->copy()->addMonths(8),
            'current_stock' => 6,
            'min_stock' => 2,
            'unit_cost' => 85.00,
            'supplier' => 'DentaMed Perú',
        ]);

        $item2 = InventoryItem::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'code' => 'INS-RES-A2',
            'name' => 'Resina Nanohíbrida Filtek Z350 Jeringa A2',
            'category' => 'Restauración',
            'unit' => 'jeringas',
            'lot_number' => 'LOTE-RF441',
            'expires_at' => $today->copy()->addDays(18),
            'current_stock' => 2,
            'min_stock' => 3,
            'unit_cost' => 125.00,
            'supplier' => '3M Dental',
        ]);

        InventoryMovement::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'inventory_item_id' => $item1->id,
            'user_id' => $assistant->id,
            'type' => 'entrada',
            'quantity' => 6,
            'reason' => 'Reposición mensual de farmacia clínica',
            'moved_at' => $today->copy()->subDays(4),
        ]);

        InventoryMovement::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'inventory_item_id' => $item2->id,
            'user_id' => $dentist->id,
            'patient_id' => $patient1->id,
            'type' => 'salida',
            'quantity' => 1,
            'reason' => 'Restauración estética pieza 46',
            'moved_at' => $today->copy()->subDay(),
        ]);

        // Cronograma de cuotas de financiamiento para HC-0001
        PaymentInstallment::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient1->id,
            'installment_number' => 1,
            'due_date' => $today->copy()->subDays(10),
            'amount' => 450.00,
            'paid_amount' => 0,
            'status' => 'pendiente',
            'notes' => 'Cuota mensual rehabilitación + endodoncia',
        ]);

        PaymentInstallment::withoutGlobalScopes()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient1->id,
            'installment_number' => 2,
            'due_date' => $today->copy()->addDays(20),
            'amount' => 450.00,
            'paid_amount' => 0,
            'status' => 'pendiente',
            'notes' => 'Segunda cuota programada',
        ]);
    }
}
