<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_ingests', function (Blueprint $table) {
            $table->id();
            $table->string('source', 32)->index(); // ieducar
            $table->string('event_id', 128)->index();
            $table->json('payload');
            $table->timestamp('received_at')->useCurrent()->index();
            $table->timestamps();

            $table->unique(['source', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_ingests');
    }
};
