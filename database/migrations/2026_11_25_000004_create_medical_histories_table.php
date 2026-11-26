<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->text('chief_complaint')->nullable();
            $table->text('current_illness')->nullable();
            $table->text('medical_history_notes')->nullable();
            $table->text('dental_history_notes')->nullable();
            $table->text('allergies')->nullable();
            $table->text('current_medications')->nullable();
            $table->json('chronic_conditions')->nullable();
            $table->json('habits')->nullable();
            $table->boolean('is_pregnant')->default(false);
            $table->json('medical_alerts')->nullable();
            $table->json('vital_signs')->nullable();
            $table->text('extraoral_exam')->nullable();
            $table->text('intraoral_exam')->nullable();
            $table->text('tmj_exam')->nullable();
            $table->text('soft_tissues_exam')->nullable();
            $table->text('periodontal_exam')->nullable();
            $table->text('occlusion_exam')->nullable();
            $table->text('presumptive_diagnosis')->nullable();
            $table->text('definitive_diagnosis')->nullable();
            $table->string('cie10_code', 20)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['clinic_id', 'patient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_histories');
    }
};
