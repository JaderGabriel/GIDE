<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbound_deliveries', function (Blueprint $table) {
            $table->id();

            $table->string('integration_key', 32)->index(); // gestor | ...
            $table->string('event_type', 64)->index(); // enrollment_ingest | ...
            $table->string('event_id', 128)->nullable()->index(); // X-Event-Id (idempotência)

            $table->string('endpoint', 255);
            $table->json('payload');

            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedSmallInteger('last_http_status')->nullable();
            $table->text('last_error')->nullable();

            $table->timestamp('delivered_at')->nullable()->index();
            $table->timestamp('next_retry_at')->nullable()->index();

            $table->timestamps();

            $table->unique(['integration_key', 'event_type', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_deliveries');
    }
};
