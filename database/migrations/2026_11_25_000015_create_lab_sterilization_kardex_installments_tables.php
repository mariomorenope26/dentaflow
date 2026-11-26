<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dentist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->string('order_code', 30);
            $table->string('lab_name', 140);
            $table->string('work_type', 140);
            $table->string('tooth_code', 20)->nullable();
            $table->string('shade', 30)->nullable();
            $table->string('material', 100)->nullable();
            $table->date('sent_at')->nullable();
            $table->date('due_date')->nullable();
            $table->date('received_at')->nullable();
            $table->date('delivered_at')->nullable();
            $table->decimal('cost', 10, 2)->default(0);
            $table->string('status', 30)->default('por_enviar');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['clinic_id', 'status', 'due_date']);
        });

        Schema::create('sterilization_cycles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('autoclave_name', 120);
            $table->string('cycle_number', 40);
            $table->dateTime('sterilized_at');
            $table->decimal('temperature_c', 5, 1)->default(134.0);
            $table->decimal('pressure_bar', 4, 2)->default(2.10);
            $table->unsignedSmallInteger('duration_minutes')->default(35);
            $table->text('load_summary');
            $table->string('chemical_indicator', 30)->default('conforme');
            $table->string('biological_indicator', 30)->default('pendiente');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'sterilized_at']);
        });

        Schema::create('inventory_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name', 160);
            $table->string('category', 80);
            $table->string('unit', 40)->default('unidad');
            $table->string('lot_number', 60)->nullable();
            $table->date('expires_at')->nullable();
            $table->decimal('current_stock', 10, 2)->default(0);
            $table->decimal('min_stock', 10, 2)->default(5);
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->string('supplier', 140)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['clinic_id', 'category', 'expires_at']);
        });

        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->string('type', 20);
            $table->decimal('quantity', 10, 2);
            $table->string('reason', 255);
            $table->dateTime('moved_at');
            $table->timestamps();

            $table->index(['clinic_id', 'inventory_item_id', 'moved_at']);
        });

        Schema::create('payment_installments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->unsignedSmallInteger('installment_number');
            $table->date('due_date');
            $table->decimal('amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->dateTime('paid_at')->nullable();
            $table->string('status', 20)->default('pendiente');
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'patient_id', 'status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_installments');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('sterilization_cycles');
        Schema::dropIfExists('lab_orders');
    }
};
