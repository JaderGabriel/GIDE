<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_deliveries', function (Blueprint $table) {
            $table->string('status', 16)->default('pending')->index();

            $table->string('aluno_id', 64)->nullable()->index();
            $table->string('matricula_id', 64)->nullable()->index();
            $table->string('window', 64)->nullable()->index();
            $table->string('event_type', 64)->nullable()->index();
            $table->timestamp('occurred_at')->nullable()->index();

            $table->json('context')->nullable();
            $table->json('provider_response')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sms_deliveries', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'aluno_id',
                'matricula_id',
                'window',
                'event_type',
                'occurred_at',
                'context',
                'provider_response',
            ]);
        });
    }
};
