<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodontograms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('recorded_at');
            $table->json('entries');
            $table->decimal('bleeding_index', 5, 2)->default(0);
            $table->decimal('plaque_index', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'patient_id', 'recorded_at']);
        });

        Schema::create('orthodontic_controls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('dentist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('control_date');
            $table->string('stage', 80);
            $table->string('upper_archwire', 80)->nullable();
            $table->string('lower_archwire', 80)->nullable();
            $table->string('elastics', 120)->nullable();
            $table->string('hygiene_status', 30)->default('buena');
            $table->text('clinical_notes');
            $table->date('next_control_date')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'patient_id', 'control_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orthodontic_controls');
        Schema::dropIfExists('periodontograms');
    }
};
