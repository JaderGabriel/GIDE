<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gestor_access_event_deliveries', function (Blueprint $table) {
            $table->string('inbound_channel', 32)
                ->default('gestor_hmac')
                ->after('event_id')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('gestor_access_event_deliveries', function (Blueprint $table) {
            $table->dropColumn('inbound_channel');
        });
    }
};
