<?php

namespace App\Listeners;

use App\Models\AuthLog;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Carbon;

class LogUserLogout
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
    public function handle(Logout $event): void
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

        AuthLog::create([
            'user_id' => $event->user?->getAuthIdentifier(),
            'event' => 'logout',
            'guard' => $event->guard,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'occurred_at' => Carbon::now(),
        ]);
    }
}
