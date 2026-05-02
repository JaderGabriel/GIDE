<?php

namespace App\Services;

use App\Models\UserAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

final class UserAuditLogger
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public static function record(
        ?int $actorUserId,
        string $action,
        array $meta = [],
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?Request $request = null,
    ): void {
        $req = $request ?? request();
        $ip = null;
        $ua = null;
        if ($req) {
            try {
                $ip = method_exists($req, 'ip') ? $req->ip() : null;
                $ua = method_exists($req, 'userAgent') ? $req->userAgent() : null;
            } catch (\Throwable) {
            }
        }

        UserAuditLog::query()->create([
            'user_id' => $actorUserId,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'meta' => $meta === [] ? null : $meta,
            'ip_address' => $ip,
            'user_agent' => $ua,
            'occurred_at' => Carbon::now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function recordAuthenticated(string $action, array $meta = [], ?string $subjectType = null, ?int $subjectId = null): void
    {
        $id = auth()->id();
        self::record(is_int($id) ? $id : null, $action, $meta, $subjectType, $subjectId);
    }
}
