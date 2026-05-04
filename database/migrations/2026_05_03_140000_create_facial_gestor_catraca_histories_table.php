<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facial_gestor_catraca_histories', function (Blueprint $table) {
            $table->id();
            $table->string('aluno_id', 32)->index();
            $table->foreignId('facial_send_request_id')->nullable()->constrained('facial_send_requests')->nullOnDelete();
            $table->string('event_type', 32)->index();
            $table->unsignedBigInteger('invite_id')->nullable()->index();
            $table->unsignedBigInteger('guest_id')->nullable()->index();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->boolean('ok')->nullable();
            $table->text('response_body')->nullable();
            $table->string('effective_url', 512)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facial_gestor_catraca_histories');
    }
};
