<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Documentação no catálogo do PostgreSQL: `extra` é JSON lógico criptografado na aplicação,
 * com formato definido por `integrations.key` (gestor, iEducar, SMS, …).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("COMMENT ON COLUMN integrations.extra IS 'JSON criptografado na app; estrutura por key (ex.: gestor: SDK, convite, ieducar_processing.environment; ieducar: API Diario, presenca).'");
    }
};
