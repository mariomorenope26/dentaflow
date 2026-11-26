<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odontograms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20)->default('inicial');
            $table->string('dentition', 20)->default('adulto');
            $table->string('notation', 20)->default('FDI');
            $table->dateTime('recorded_at');
            $table->text('notes')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['clinic_id', 'patient_id', 'recorded_at']);
        });

        Schema::create('treatment_catalogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 160);
            $table->string('category', 60);
            $table->decimal('default_price', 10, 2)->default(0);
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->string('odontogram_condition', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['clinic_id', 'code']);
        });

        Schema::create('treatment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 160);
            $table->string('status', 30)->default('borrador');
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'patient_id', 'status']);
        });

        Schema::create('treatment_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignId('treatment_plan_id')->constrained('treatment_plans')->cascadeOnDelete();
            $table->foreignId('treatment_catalog_id')->nullable()->constrained('treatment_catalogs')->nullOnDelete();
            $table->string('tooth_code', 10)->nullable();
            $table->string('surface', 10)->nullable();
            $table->string('description', 255);
            $table->string('phase', 60)->default('Fase 2: Restauradora');
            $table->string('priority', 20)->default('media');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->string('status', 30)->default('pendiente');
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['clinic_id', 'treatment_plan_id', 'status']);
        });

        Schema::create('odontogram_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignId('odontogram_id')->constrained('odontograms')->cascadeOnDelete();
            $table->string('tooth_code', 10);
            $table->string('surface', 10)->default('FULL');
            $table->string('condition_code', 50);
            $table->string('status', 30)->default('por_realizar');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('treatment_plan_item_id')->nullable()->constrained('treatment_plan_items')->nullOnDelete();
            $table->timestamps();

            $table->index(['clinic_id', 'odontogram_id', 'tooth_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odontogram_findings');
        Schema::dropIfExists('treatment_plan_items');
        Schema::dropIfExists('treatment_plans');
        Schema::dropIfExists('treatment_catalogs');
        Schema::dropIfExists('odontograms');
    }
};
