<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_evolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->dateTime('attended_at');
            $table->string('procedure_summary', 255);
            $table->text('clinical_notes');
            $table->text('prescription_notes')->nullable();
            $table->dateTime('signed_at')->nullable();
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_evolution_id')->nullable()->constrained('clinical_evolutions')->nullOnDelete();
            $table->text('correction_reason')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['clinic_id', 'patient_id', 'attended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_evolutions');
    }
};
