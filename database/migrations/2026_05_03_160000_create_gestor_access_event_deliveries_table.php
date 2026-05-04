<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gestor_access_event_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('event_id', 128)->index();
            $table->foreignId('access_event_id')->nullable()->constrained('access_events')->nullOnDelete();

            $table->json('inbound_payload');
            $table->boolean('access_event_was_created')->default(false);

            $table->string('processing_status', 24)->default('pending')->index();
            $table->string('gestor_ie_environment', 16)->default('homolog');
            $table->boolean('ieducar_preview_only')->default(true);

            $table->json('analysis_json')->nullable();
            $table->json('ieducar_marker_summary')->nullable();

            $table->json('ieducar_frequencia_request_json')->nullable();
            $table->unsignedSmallInteger('ieducar_frequencia_http_status')->nullable();
            $table->json('ieducar_frequencia_response_json')->nullable();
            $table->text('ieducar_frequencia_error')->nullable();

            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gestor_access_event_deliveries');
    }
};
