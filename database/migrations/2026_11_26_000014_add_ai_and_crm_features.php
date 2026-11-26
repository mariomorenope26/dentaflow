<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table): void {
            $table->string('ai_provider', 30)->default('local')->after('apiperu_token');
            $table->text('ai_api_key')->nullable()->after('ai_provider');
            $table->boolean('online_booking_enabled')->default(true)->after('ai_api_key');
        });

        Schema::table('patients', function (Blueprint $table): void {
            $table->string('crm_stage', 40)->default('en_tratamiento')->after('tags');
            $table->timestamp('last_contacted_at')->nullable()->after('crm_stage');
            $table->text('crm_notes')->nullable()->after('last_contacted_at');
        });

        Schema::table('patient_images', function (Blueprint $table): void {
            $table->json('ai_analysis')->nullable()->after('annotations');
        });
    }

    public function down(): void
    {
        Schema::table('patient_images', function (Blueprint $table): void {
            $table->dropColumn('ai_analysis');
        });

        Schema::table('patients', function (Blueprint $table): void {
            $table->dropColumn(['crm_stage', 'last_contacted_at', 'crm_notes']);
        });

        Schema::table('clinics', function (Blueprint $table): void {
            $table->dropColumn(['ai_provider', 'ai_api_key', 'online_booking_enabled']);
        });
    }
};
