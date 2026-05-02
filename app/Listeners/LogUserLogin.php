<?php

namespace App\Listeners;

use App\Models\AuthLog;
use App\Models\UserAuditLog;
use App\Services\UserAuditLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Carbon;

class LogUserLogin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $ip = null;
        $userAgent = null;

        try {
            $request = request();
            $ip = method_exists($request, 'ip') ? $request->ip() : null;
            $userAgent = method_exists($request, 'userAgent') ? $request->userAgent() : null;
        } catch (\Throwable) {
            // Ignore when request() isn't available (e.g. console context)
        }

        $userId = $event->user?->getAuthIdentifier();

        AuthLog::create([
            'user_id' => $userId,
            'event' => 'login',
            'guard' => $event->guard,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'occurred_at' => Carbon::now(),
        ]);

        if (is_int($userId) || (is_string($userId) && ctype_digit($userId))) {
            UserAuditLogger::record((int) $userId, 'auth.login', [
                'guard' => $event->guard,
            ], UserAuditLog::SUBJECT_USER, (int) $userId);
        }
    }
}
