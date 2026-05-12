<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gestor_access_event_deliveries', function (Blueprint $table) {
            $table->json('reprocessing_log')->nullable()->after('ieducar_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('gestor_access_event_deliveries', function (Blueprint $table) {
            $table->dropColumn('reprocessing_log');
        });
    }
};
