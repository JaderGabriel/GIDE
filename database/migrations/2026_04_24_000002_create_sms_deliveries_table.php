<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_deliveries', function (Blueprint $table) {
            $table->id();

            $table->string('event_id', 128)->index(); // X-Event-Id do access-event
            $table->string('template_key', 64)->index();

            $table->string('to', 32);
            $table->string('from', 64)->nullable();
            $table->text('message');

            $table->string('provider', 32)->default('zenvia')->index();
            $table->string('provider_message_id', 128)->nullable()->index();

            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedSmallInteger('last_http_status')->nullable();
            $table->text('last_error')->nullable();

            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamp('next_retry_at')->nullable()->index();

            $table->timestamps();

            $table->unique(['event_id', 'template_key', 'to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_deliveries');
    }
};
