<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table): void {
            $table->uuid('fhir_uuid')->nullable()->unique()->after('history_number');
            $table->string('blood_type', 10)->nullable()->after('gender');
            $table->string('emergency_contact_name', 150)->nullable()->after('guardian_relationship');
            $table->string('emergency_contact_phone', 40)->nullable()->after('emergency_contact_name');
        });

        Schema::table('medical_histories', function (Blueprint $table): void {
            $table->string('asa_classification', 20)->nullable()->after('is_pregnant');
            $table->text('family_history')->nullable()->after('dental_history_notes');
            $table->json('review_of_systems')->nullable()->after('chronic_conditions');
            $table->json('stomatognathic_exam')->nullable()->after('occlusion_exam');
        });

        Schema::table('clinical_evolutions', function (Blueprint $table): void {
            $table->text('soap_subjective')->nullable()->after('clinical_notes');
            $table->text('soap_objective')->nullable()->after('soap_subjective');
            $table->text('soap_assessment')->nullable()->after('soap_objective');
            $table->text('soap_plan')->nullable()->after('soap_assessment');
            $table->string('cie10_code', 20)->nullable()->after('soap_plan');
            $table->string('signature_hash', 64)->nullable()->after('signed_by');
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->string('confirmation_token', 64)->nullable()->unique()->after('version');
            $table->timestamp('reminder_sent_at')->nullable()->after('confirmation_token');
            $table->timestamp('patient_confirmed_at')->nullable()->after('reminder_sent_at');
            $table->timestamp('reschedule_requested_at')->nullable()->after('patient_confirmed_at');
            $table->text('reschedule_note')->nullable()->after('reschedule_requested_at');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->string('customer_doc_type', 10)->nullable()->after('document_type');
            $table->string('customer_doc_number', 20)->nullable()->after('customer_doc_type');
            $table->string('customer_name', 180)->nullable()->after('customer_doc_number');
            $table->string('customer_address', 255)->nullable()->after('customer_name');
            $table->string('sunat_series', 10)->nullable()->after('receipt_number');
            $table->string('sunat_correlative', 20)->nullable()->after('sunat_series');
            $table->decimal('tax_subtotal', 12, 2)->default(0)->after('amount');
            $table->decimal('tax_igv', 12, 2)->default(0)->after('tax_subtotal');
            $table->string('sunat_status', 30)->default('emitido_interno')->after('status');
            $table->string('sunat_hash', 64)->nullable()->after('sunat_status');
        });

        Schema::table('patient_images', function (Blueprint $table): void {
            $table->json('annotations')->nullable()->after('notes');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->decimal('commission_rate', 5, 2)->default(40.00)->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('commission_rate');
        });

        Schema::table('patient_images', function (Blueprint $table): void {
            $table->dropColumn('annotations');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn([
                'customer_doc_type',
                'customer_doc_number',
                'customer_name',
                'customer_address',
                'sunat_series',
                'sunat_correlative',
                'tax_subtotal',
                'tax_igv',
                'sunat_status',
                'sunat_hash',
            ]);
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn([
                'confirmation_token',
                'reminder_sent_at',
                'patient_confirmed_at',
                'reschedule_requested_at',
                'reschedule_note',
            ]);
        });

        Schema::table('clinical_evolutions', function (Blueprint $table): void {
            $table->dropColumn([
                'soap_subjective',
                'soap_objective',
                'soap_assessment',
                'soap_plan',
                'cie10_code',
                'signature_hash',
            ]);
        });

        Schema::table('medical_histories', function (Blueprint $table): void {
            $table->dropColumn([
                'asa_classification',
                'family_history',
                'review_of_systems',
                'stomatognathic_exam',
            ]);
        });

        Schema::table('patients', function (Blueprint $table): void {
            $table->dropColumn([
                'fhir_uuid',
                'blood_type',
                'emergency_contact_name',
                'emergency_contact_phone',
            ]);
        });
    }
};
