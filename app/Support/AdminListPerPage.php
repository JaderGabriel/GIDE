<?php

namespace App\Support;

use Illuminate\Http\Request;

final class AdminListPerPage
{
    /** @var list<int> */
    public const ALLOWED = [5, 10, 25, 50, 100, 200];

    public static function resolve(Request $request, int $default = 25): int
    {
        $v = (int) $request->query('per_page', $default);

        return in_array($v, self::ALLOWED, true) ? $v : $default;
    }
}
