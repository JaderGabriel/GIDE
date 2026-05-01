<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facial_send_requests', function (Blueprint $table) {
            $table->id();

            $table->string('event_id', 128)->unique(); // X-Event-Id do iEducar
            $table->json('payload');

            $table->string('token', 64)->unique(); // token de abertura da tela
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable()->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facial_send_requests');
    }
};
