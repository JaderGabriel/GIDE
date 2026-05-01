<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_events', function (Blueprint $table) {
            $table->id();
            $table->string('source', 32)->index(); // gestor
            $table->string('event_id', 128)->index();
            $table->json('payload');

            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamp('processed_at')->nullable()->index();
            $table->json('analysis')->nullable();

            $table->timestamps();
            $table->unique(['source', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_events');
    }
};
