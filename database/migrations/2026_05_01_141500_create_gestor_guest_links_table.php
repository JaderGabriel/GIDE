<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gestor_guest_links', function (Blueprint $table) {
            $table->id();

            // Identificador do aluno no iEducar (usado como name do Invite/Guest)
            $table->string('cod_aluno', 32)->unique();

            $table->unsignedBigInteger('invite_id')->nullable()->index();
            $table->unsignedBigInteger('guest_id')->nullable()->index();

            // Auditoria do último create (invite)
            $table->unsignedSmallInteger('last_invite_http_status')->nullable();
            $table->json('last_invite_response_json')->nullable();

            // Auditoria do último face create
            $table->unsignedSmallInteger('last_face_http_status')->nullable();
            $table->text('last_face_response_body')->nullable();

            $table->text('last_error')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gestor_guest_links');
    }
};
