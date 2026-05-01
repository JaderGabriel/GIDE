<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facial_enroll_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facial_send_request_id')->constrained('facial_send_requests')->cascadeOnDelete();

            $table->string('external_id', 128)->nullable()->index();

            $table->boolean('ok')->default(false)->index();
            $table->unsignedSmallInteger('http_status')->nullable()->index();
            $table->text('response_body')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facial_enroll_attempts');
    }
};
