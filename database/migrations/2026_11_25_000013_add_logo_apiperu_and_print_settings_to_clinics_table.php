<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->string('logo_path', 255)->nullable()->after('address');
            $table->text('apiperu_token')->nullable()->after('settings');
            $table->json('print_settings')->nullable()->after('apiperu_token');
        });
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'apiperu_token', 'print_settings']);
        });
    }
};
