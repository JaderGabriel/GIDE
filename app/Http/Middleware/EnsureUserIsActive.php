<?php

namespace App\Http\Middleware;

use App\Models\UserAuditLog;
use App\Services\UserAuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ! (bool) $user->is_active) {
            $uid = (int) $user->getAuthIdentifier();
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            UserAuditLogger::record($uid, 'session_terminated_inactive', [
                'reason' => 'Conta desativada durante sessão ativa.',
            ], UserAuditLog::SUBJECT_USER, $uid, $request);

            return redirect()
                ->route('login')
                ->withErrors(['username' => 'Sua conta foi desativada. Entre em contato com um administrador.']);
        }

        return $next($request);
    }
}
