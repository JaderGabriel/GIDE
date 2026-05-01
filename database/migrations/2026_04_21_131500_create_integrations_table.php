<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->string('key', 32)->unique(); // ieducar | gestor | ...
            $table->string('name');
            $table->string('base_url')->nullable();
            $table->boolean('enabled')->default(false)->index();

            $table->string('auth_type', 32)->default('none'); // none | bearer | api_key | ...
            $table->text('auth_token')->nullable();

            $table->text('hmac_secret')->nullable();
            $table->unsignedInteger('signature_ttl_seconds')->default(300);

            $table->json('extra')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
