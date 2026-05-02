<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAuditLog;
use App\Services\UserAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $remember = (bool) $request->boolean('remember');

        $candidate = User::query()->where('username', $credentials['username'])->first();
        if ($candidate && ! $candidate->isActive()) {
            UserAuditLogger::record((int) $candidate->getKey(), 'login_denied_inactive', [
                'username' => $candidate->username,
            ], UserAuditLog::SUBJECT_USER, (int) $candidate->getKey(), $request);

            return back()
                ->withInput($request->only('username', 'remember'))
                ->withErrors(['username' => 'Esta conta está desativada.']);
        }

        if (! Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password']], $remember)) {
            return back()
                ->withInput($request->only('username', 'remember'))
                ->withErrors(['username' => 'Credenciais inválidas.']);
        }

        $request->session()->regenerate();

        $default = auth()->user()->is_admin
            ? url('/dashboard')
            : route('integrations.overview');

        return redirect()->intended($default);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
