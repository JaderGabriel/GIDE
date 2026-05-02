<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ieducar_frequencia_registro_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('mode', 16); // preview | apply
            $table->string('status', 24)->default('pending'); // pending | processing | completed | failed
            $table->json('payload');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->json('response_json')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('mode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ieducar_frequencia_registro_deliveries');
    }
};
