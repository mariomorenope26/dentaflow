<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30);
            $table->string('title', 180);
            $table->longText('content');
            $table->json('metadata')->nullable();
            $table->longText('signature_data')->nullable();
            $table->string('signed_by_name', 150)->nullable();
            $table->dateTime('signed_at')->nullable();
            $table->dateTime('issued_at');
            $table->timestamps();

            $table->index(['clinic_id', 'patient_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_documents');
    }
};
