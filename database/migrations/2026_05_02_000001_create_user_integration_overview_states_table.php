<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_integration_overview_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('lane_tests')->nullable()->comment('Resultados por chave ex. ieducar:out, gestor:in');
            $table->json('last_test')->nullable()->comment('Último painel de passos (visão geral)');
            $table->string('last_test_key', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_integration_overview_states');
    }
};
