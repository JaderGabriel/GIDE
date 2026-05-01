<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gide_facial_inbounds', function (Blueprint $table) {
            $table->id();

            $table->string('operation', 16)->index(); // nova|excluir
            $table->string('cod_aluno', 32)->nullable()->index();
            $table->string('idpes', 32)->nullable()->index();

            $table->string('dedupe_key', 96)->unique();
            $table->json('payload');

            $table->timestamp('received_at')->index();
            $table->timestamp('processed_at')->nullable()->index();
            $table->string('status', 32)->default('received')->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gide_facial_inbounds');
    }
};
