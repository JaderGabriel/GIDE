<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_enrichment_cache', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cod_aluno')->unique();
            $table->json('data');
            $table->timestamp('fetched_at');
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrichment_cache');
    }
};
