<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('clinic_id')->nullable()->after('id')->constrained('clinics')->nullOnDelete();
            $table->string('role', 40)->default('admin_consultorio')->after('email');
            $table->string('specialty', 120)->nullable()->after('role');
            $table->string('license_number', 50)->nullable()->after('specialty');
            $table->string('phone', 40)->nullable()->after('license_number');
            $table->boolean('is_active')->default(true)->after('phone');
            $table->index(['clinic_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['clinic_id', 'role']);
            $table->dropConstrainedForeignId('clinic_id');
            $table->dropColumn(['role', 'specialty', 'license_number', 'phone', 'is_active']);
        });
    }
};
