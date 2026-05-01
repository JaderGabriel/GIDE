<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        // `integrations.extra` era JSON; para suportar criptografia (string), precisa ser TEXT.
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE integrations ALTER COLUMN extra TYPE text USING extra::text');

            return;
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE integrations MODIFY extra LONGTEXT NULL');

            return;
        }

        if ($driver === 'sqlite') {
            // SQLite não suporta ALTER COLUMN TYPE nativamente sem recriar tabela.
            // Em dev/test, o schema já funciona com TEXT; em produção, use pgsql/mysql.
            return;
        }
    }

    public function down(): void
    {
        // Não revertido (evita perda/invalidade do conteúdo criptografado).
    }
};
