<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('treatment_plan_id')->nullable()->constrained('treatment_plans')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('receipt_number', 30);
            $table->string('document_type', 20)->default('recibo');
            $table->string('payment_method', 30)->default('efectivo');
            $table->decimal('amount', 10, 2);
            $table->dateTime('paid_at');
            $table->string('concept', 255);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('registrado');
            $table->timestamps();

            $table->unique(['clinic_id', 'receipt_number']);
            $table->index(['clinic_id', 'paid_at']);
            $table->index(['clinic_id', 'patient_id']);
        });

        Schema::create('cash_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('closing_date');
            $table->decimal('opening_amount', 10, 2)->default(0);
            $table->decimal('cash_total', 10, 2)->default(0);
            $table->decimal('card_total', 10, 2)->default(0);
            $table->decimal('digital_total', 10, 2)->default(0);
            $table->decimal('transfer_total', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->dateTime('closed_at');
            $table->timestamps();

            $table->unique(['clinic_id', 'closing_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_closings');
        Schema::dropIfExists('payments');
    }
};
