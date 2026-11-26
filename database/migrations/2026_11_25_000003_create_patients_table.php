<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->string('history_number', 30);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('document_type', 20)->default('DNI');
            $table->string('document_number', 30)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('occupation', 120)->nullable();
            $table->string('insurance_provider', 120)->nullable();
            $table->string('insurance_number', 60)->nullable();
            $table->string('guardian_name', 150)->nullable();
            $table->string('guardian_phone', 40)->nullable();
            $table->string('guardian_relationship', 60)->nullable();
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['clinic_id', 'history_number']);
            $table->index(['clinic_id', 'document_number']);
            $table->index(['clinic_id', 'last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
