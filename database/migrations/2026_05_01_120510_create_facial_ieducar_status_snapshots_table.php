<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facial_ieducar_status_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facial_send_request_id')->constrained('facial_send_requests')->cascadeOnDelete();

            $table->string('cod_aluno', 32)->nullable()->index();
            $table->string('idpes', 32)->nullable()->index();

            $table->unsignedSmallInteger('http_status')->nullable()->index();
            $table->json('response_json')->nullable();
            $table->timestamp('fetched_at')->index();
            $table->text('error_message')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facial_ieducar_status_snapshots');
    }
};
