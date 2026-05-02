<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Corrige sequência de users.id no PostgreSQL quando fica atrás do MAX(id)
 * (ex.: restore/import com INSERT explícito), evitando erro duplicate key em users_pkey.
 */
final class PostgresUsersIdSequence
{
    public static function driver(): string
    {
        return (string) DB::connection()->getDriverName();
    }

    public static function sync(): void
    {
        if (self::driver() !== 'pgsql') {
            return;
        }

        $seqRow = DB::selectOne('SELECT pg_get_serial_sequence(\'users\', \'id\') AS seq');
        $seqName = is_object($seqRow) && isset($seqRow->seq) ? (string) $seqRow->seq : '';
        if ($seqName === '') {
            $seqName = 'public.users_id_seq';
        }

        $max = DB::table('users')->max('id');
        if ($max === null) {
            DB::statement('SELECT setval(?, 1, false)', [$seqName]);

            return;
        }

        DB::statement('SELECT setval(?, ?::bigint, true)', [$seqName, (int) $max]);
    }
}
