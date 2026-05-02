<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('Quem executou a ação');
            $table->string('action', 64)->index();
            $table->string('subject_type', 64)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['user_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_audit_logs');
    }
};
