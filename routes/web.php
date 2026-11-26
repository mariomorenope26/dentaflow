<?php

declare(strict_types=1);

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ClinicalDocumentController;
use App\Http\Controllers\ClinicalEvolutionController;
use App\Http\Controllers\ClinicSettingsController;
use App\Http\Controllers\ContralorController;
use App\Http\Controllers\CrmController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DentalAiController;
use App\Http\Controllers\DniLookupController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\LabOrderController;
use App\Http\Controllers\MedicalHistoryController;
use App\Http\Controllers\OdontogramController;
use App\Http\Controllers\OfflineSyncController;
use App\Http\Controllers\OrthodonticControlController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientImageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentInstallmentController;
use App\Http\Controllers\PdfExportController;
use App\Http\Controllers\PeriodontogramController;
use App\Http\Controllers\PublicAppointmentController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RucLookupController;
use App\Http\Controllers\SterilizationInventoryController;
use App\Http\Controllers\SystemBackupController;
use App\Http\Controllers\TreatmentCatalogController;
use App\Http\Controllers\TreatmentPlanController;
use App\Http\Controllers\UserManualController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');
Route::get('reservar-cita', [PublicBookingController::class, 'show'])->name('public.booking.show');
Route::get('reservar-cita/disponibilidad', [PublicBookingController::class, 'slots'])
    ->middleware('throttle:60,1')
    ->name('public.booking.slots');
Route::post('reservar-cita', [PublicBookingController::class, 'store'])
    ->middleware('throttle:15,1')
    ->name('public.booking.store');
Route::get('confirmar-cita/{token}', [PublicAppointmentController::class, 'show'])->name('public.appointment.show');
Route::post('confirmar-cita/{token}', [PublicAppointmentController::class, 'respond'])
    ->middleware('throttle:20,1')
    ->name('public.appointment.respond');

Route::middleware(['auth', 'verified', 'clinic'])->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('search', GlobalSearchController::class)->name('global-search');

    // Pacientes y ficha clínica integral
    Route::get('patients', [PatientController::class, 'index'])->name('patients.index');
    Route::post('patients', [PatientController::class, 'store'])->name('patients.store');
    Route::get('patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
    Route::patch('patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
    Route::delete('patients/{patient}', [PatientController::class, 'destroy'])->name('patients.destroy');

    // Consulta RENIEC / DNI y SUNAT / RUC vía apiperu.dev
    Route::post('api-integrations/dni-lookup', DniLookupController::class)
        ->middleware('throttle:30,1')
        ->name('integrations.dni-lookup');
    Route::post('api-integrations/ruc-lookup', RucLookupController::class)
        ->middleware('throttle:30,1')
        ->name('integrations.ruc-lookup');

    // Historia clínica
    Route::put('patients/{patient}/medical-history', [MedicalHistoryController::class, 'update'])
        ->name('patients.medical-history.update');

    // Evoluciones clínicas con firma y bloqueo
    Route::post('patients/{patient}/evolutions', [ClinicalEvolutionController::class, 'store'])
        ->name('patients.evolutions.store');
    Route::patch('patients/{patient}/evolutions/{evolution}', [ClinicalEvolutionController::class, 'update'])
        ->name('patients.evolutions.update');
    Route::post('patients/{patient}/evolutions/{evolution}/sign', [ClinicalEvolutionController::class, 'sign'])
        ->name('patients.evolutions.sign');

    // Odontograma interactivo e historial
    Route::post('patients/{patient}/odontograms', [OdontogramController::class, 'store'])
        ->name('patients.odontograms.store');
    Route::put('patients/{patient}/odontograms/{odontogram}', [OdontogramController::class, 'update'])
        ->name('patients.odontograms.update');
    Route::post('patients/{patient}/odontograms/{odontogram}/findings/{finding}/convert', [OdontogramController::class, 'convertToTreatment'])
        ->name('patients.odontograms.findings.convert');

    // Periodontograma y Ortodoncia
    Route::post('patients/{patient}/periodontograms', [PeriodontogramController::class, 'store'])
        ->name('patients.periodontograms.store');
    Route::delete('patients/{patient}/periodontograms/{periodontogram}', [PeriodontogramController::class, 'destroy'])
        ->name('patients.periodontograms.destroy');
    Route::post('patients/{patient}/orthodontic-controls', [OrthodonticControlController::class, 'store'])
        ->name('patients.orthodontic-controls.store');
    Route::delete('patients/{patient}/orthodontic-controls/{control}', [OrthodonticControlController::class, 'destroy'])
        ->name('patients.orthodontic-controls.destroy');

    // Planes de tratamiento y presupuestos
    Route::post('patients/{patient}/treatment-plans', [TreatmentPlanController::class, 'store'])
        ->name('patients.treatment-plans.store');
    Route::patch('patients/{patient}/treatment-plans/{plan}', [TreatmentPlanController::class, 'update'])
        ->name('patients.treatment-plans.update');
    Route::delete('patients/{patient}/treatment-plans/{plan}', [TreatmentPlanController::class, 'destroy'])
        ->name('patients.treatment-plans.destroy');
    Route::post('patients/{patient}/treatment-plans/{plan}/items', [TreatmentPlanController::class, 'storeItem'])
        ->name('patients.treatment-plans.items.store');
    Route::patch('patients/{patient}/treatment-plan-items/{item}', [TreatmentPlanController::class, 'updateItem'])
        ->name('patients.treatment-plans.items.update');
    Route::delete('patients/{patient}/treatment-plan-items/{item}', [TreatmentPlanController::class, 'destroyItem'])
        ->name('patients.treatment-plans.items.destroy');

    // Documentos clínicos (Consentimientos firmados, Recetas, Certificados)
    Route::post('patients/{patient}/documents', [ClinicalDocumentController::class, 'store'])
        ->name('patients.documents.store');
    Route::patch('patients/{patient}/documents/{document}', [ClinicalDocumentController::class, 'update'])
        ->name('patients.documents.update');
    Route::delete('patients/{patient}/documents/{document}', [ClinicalDocumentController::class, 'destroy'])
        ->name('patients.documents.destroy');
    Route::post('patients/{patient}/documents/{document}/sign', [ClinicalDocumentController::class, 'sign'])
        ->name('patients.documents.sign');
    Route::get('patients/{patient}/documents/{document}/pdf', [ClinicalDocumentController::class, 'pdf'])
        ->name('patients.documents.pdf');

    // Galería clínica y radiografías con visor medible (almacenamiento privado autenticado)
    Route::get('patients/{patient}/images/{image}/file', [PatientImageController::class, 'show'])
        ->name('patients.images.show');
    Route::post('patients/{patient}/images', [PatientImageController::class, 'store'])
        ->name('patients.images.store');
    Route::patch('patients/{patient}/images/{image}/annotations', [PatientImageController::class, 'updateAnnotations'])
        ->name('patients.images.annotations');
    Route::delete('patients/{patient}/images/{image}', [PatientImageController::class, 'destroy'])
        ->name('patients.images.destroy');

    // Pagos, Financiamiento por cuotas, UBL SUNAT y Caja diaria
    Route::post('patients/{patient}/payments', [PaymentController::class, 'store'])
        ->name('patients.payments.store');
    Route::post('patients/{patient}/installments/generate', [PaymentInstallmentController::class, 'generate'])
        ->name('patients.installments.generate');
    Route::post('patients/{patient}/installments/{installment}/pay', [PaymentInstallmentController::class, 'pay'])
        ->name('patients.installments.pay');
    Route::delete('patients/{patient}/installments/{installment}', [PaymentInstallmentController::class, 'destroy'])
        ->name('patients.installments.destroy');
    Route::get('caja', [PaymentController::class, 'index'])->name('cash.index');
    Route::post('caja/close', [PaymentController::class, 'closeDay'])->name('cash.close');
    Route::get('caja/thermal-summary', [PaymentController::class, 'thermalDailySummary'])->name('cash.thermal-summary');
    Route::get('caja/closings/{closing}/thermal', [PaymentController::class, 'thermalClosing'])->name('cash.closings.thermal');
    Route::patch('payments/{payment}/void', [PaymentController::class, 'void'])->name('payments.void');
    Route::get('payments/{payment}/pdf', [PaymentController::class, 'receiptPdf'])->name('payments.pdf');
    Route::get('payments/{payment}/thermal', [PaymentController::class, 'thermalReceipt'])->name('payments.thermal');
    Route::get('payments/{payment}/ubl-xml', [PaymentController::class, 'ublXml'])->name('payments.ubl-xml');

    // Exportación PDF Historia Clínica, Odontograma y HL7 FHIR R4
    Route::get('patients/{patient}/pdf/history', [PdfExportController::class, 'clinicalHistory'])
        ->name('patients.pdf.history');
    Route::get('patients/{patient}/pdf/odontogram/{odontogram?}', [PdfExportController::class, 'odontogram'])
        ->name('patients.pdf.odontogram');
    Route::get('patients/{patient}/fhir-bundle', [PdfExportController::class, 'fhirBundle'])
        ->name('patients.fhir-bundle');

    // Agenda, citas y recordatorio WhatsApp 1-clic
    Route::get('agenda', [AppointmentController::class, 'index'])->name('agenda.index');
    Route::post('agenda', [AppointmentController::class, 'store'])->name('agenda.store');
    Route::patch('agenda/{appointment}', [AppointmentController::class, 'update'])->name('agenda.update');
    Route::delete('agenda/{appointment}', [AppointmentController::class, 'destroy'])->name('agenda.destroy');
    Route::post('agenda/{appointment}/whatsapp-reminder', [AppointmentController::class, 'sendWhatsappReminder'])
        ->name('agenda.whatsapp-reminder');

    // Laboratorio Dental (Prótesis, Coronas, Alineadores)
    Route::get('laboratorio', [LabOrderController::class, 'index'])->name('lab.index');
    Route::post('laboratorio', [LabOrderController::class, 'store'])->name('lab.store');
    Route::patch('laboratorio/{labOrder}', [LabOrderController::class, 'update'])->name('lab.update');
    Route::delete('laboratorio/{labOrder}', [LabOrderController::class, 'destroy'])->name('lab.destroy');

    // Esterilización (Autoclave) y Kardex de Insumos Críticos
    Route::get('esterilizacion-kardex', [SterilizationInventoryController::class, 'index'])->name('sterilization.index');
    Route::post('esterilizacion-kardex/cycles', [SterilizationInventoryController::class, 'storeCycle'])->name('sterilization.cycles.store');
    Route::patch('esterilizacion-kardex/cycles/{cycle}', [SterilizationInventoryController::class, 'updateCycle'])->name('sterilization.cycles.update');
    Route::delete('esterilizacion-kardex/cycles/{cycle}', [SterilizationInventoryController::class, 'destroyCycle'])->name('sterilization.cycles.destroy');
    Route::post('esterilizacion-kardex/items', [SterilizationInventoryController::class, 'storeItem'])->name('sterilization.items.store');
    Route::patch('esterilizacion-kardex/items/{item}', [SterilizationInventoryController::class, 'updateItem'])->name('sterilization.items.update');
    Route::delete('esterilizacion-kardex/items/{item}', [SterilizationInventoryController::class, 'destroyItem'])->name('sterilization.items.destroy');
    Route::post('esterilizacion-kardex/items/{item}/movements', [SterilizationInventoryController::class, 'recordMovement'])->name('sterilization.items.movements.store');

    // Catálogo de tratamientos
    Route::get('treatments', [TreatmentCatalogController::class, 'index'])->name('treatments.index');
    Route::post('treatments', [TreatmentCatalogController::class, 'store'])->name('treatments.store');
    Route::patch('treatments/{treatment}', [TreatmentCatalogController::class, 'update'])->name('treatments.update');
    Route::delete('treatments/{treatment}', [TreatmentCatalogController::class, 'destroy'])->name('treatments.destroy');

    // Asistencia Clínica con Inteligencia Artificial (Dentalink / Doctocliq style)
    Route::post('patients/{patient}/ai-clinical-summary', [DentalAiController::class, 'patientSummary'])
        ->middleware('throttle:30,1')
        ->name('patients.ai-summary');
    Route::post('patients/{patient}/ai-parse-soap', [DentalAiController::class, 'parseSoap'])
        ->middleware('throttle:30,1')
        ->name('patients.ai-soap');
    Route::post('patients/{patient}/images/{image}/ai-analyze', [DentalAiController::class, 'analyzeRadiograph'])
        ->middleware('throttle:20,1')
        ->name('patients.images.ai-analyze');

    // Reportes clínicos y financieros + Analítica Conversacional IA
    Route::get('reportes', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reportes/export-csv', [ReportController::class, 'exportCsv'])->name('reports.export-csv');
    Route::post('reportes/ai-ask', [DentalAiController::class, 'askReports'])
        ->middleware('throttle:30,1')
        ->name('reports.ai-ask');

    // Contralor IA y CRM de retención WhatsApp
    Route::get('contralor-ia', [ContralorController::class, 'index'])->name('contralor.index');
    Route::get('crm', [CrmController::class, 'index'])->name('crm.index');
    Route::patch('crm/patients/{patient}', [CrmController::class, 'updatePatientStage'])->name('crm.patients.update');
    Route::post('crm/patients/{patient}/whatsapp-draft', [CrmController::class, 'draftWhatsapp'])
        ->middleware('throttle:30,1')
        ->name('crm.whatsapp-draft');

    // Manual de usuario en línea y PDF oficial (solo personal autenticado)
    Route::get('manual', [UserManualController::class, 'index'])->name('manual.index');
    Route::get('manual/pdf', [UserManualController::class, 'pdf'])->name('manual.pdf');

    // Configuración del consultorio, impresiones, integraciones, equipo, respaldo y auditoría
    Route::get('clinic/settings', [ClinicSettingsController::class, 'edit'])->name('clinic.settings');
    Route::match(['post', 'patch'], 'clinic/settings', [ClinicSettingsController::class, 'update'])->name('clinic.settings.update');
    Route::post('clinic/users', [ClinicSettingsController::class, 'storeUser'])->name('clinic.users.store');
    Route::patch('clinic/users/{member}', [ClinicSettingsController::class, 'updateUser'])->name('clinic.users.update');
    Route::delete('clinic/users/{member}', [ClinicSettingsController::class, 'destroyUser'])->name('clinic.users.destroy');
    Route::get('clinic/audit', [ClinicSettingsController::class, 'auditIndex'])->name('clinic.audit');

    // Copias de seguridad, restauración y reinicio del sistema
    Route::get('clinic/backup/export-json', [SystemBackupController::class, 'exportJson'])->name('clinic.backup.export-json');
    Route::get('clinic/backup/export-sql', [SystemBackupController::class, 'exportSql'])->name('clinic.backup.export-sql');
    Route::post('clinic/backup/restore', [SystemBackupController::class, 'restore'])->name('clinic.backup.restore');
    Route::post('clinic/backup/reset', [SystemBackupController::class, 'reset'])->name('clinic.backup.reset');

    // Sincronización PWA Offline
    Route::get('offline-sync/bootstrap', [OfflineSyncController::class, 'bootstrap'])->name('offline-sync.bootstrap');
    Route::post('offline-sync/push', [OfflineSyncController::class, 'push'])->name('offline-sync.push');
});

require __DIR__.'/settings.php';
