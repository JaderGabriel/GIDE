<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeactivateUserRequest;
use App\Http\Requests\DemoteAdminRequest;
use App\Http\Requests\PromoteAdminRequest;
use App\Http\Requests\ReactivateUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use App\Models\UserAuditLog;
use App\Services\UserAuditLogger;
use App\Support\PostgresUsersIdSequence;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()->orderBy('name')->orderBy('username')->get();

        return view('users.index', ['users' => $users]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = $this->createUserRow([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_admin' => (bool) $request->boolean('is_admin'),
            'is_active' => true,
        ]);

        UserAuditLogger::recordAuthenticated('user.created', [
            'target_username' => $user->username,
            'target_email' => $user->email,
            'target_is_admin' => $user->is_admin,
        ], UserAuditLog::SUBJECT_USER, (int) $user->getKey());

        return redirect()->route('users.index')->with('status', 'Usuário criado.');
    }

    public function deactivate(DeactivateUserRequest $request, User $user): RedirectResponse
    {
        if (! $user->isActive()) {
            return back()->with('status', 'Usuário já estava desativado.');
        }

        $user->is_active = false;
        $user->save();

        UserAuditLogger::recordAuthenticated('user.deactivated', [
            'target_username' => $user->username,
            'target_id' => (int) $user->getKey(),
        ], UserAuditLog::SUBJECT_USER, (int) $user->getKey());

        return back()->with('status', 'Usuário desativado. O login ficará bloqueado.');
    }

    public function reactivate(ReactivateUserRequest $request, User $user): RedirectResponse
    {
        if ($user->isActive()) {
            return back()->with('status', 'Usuário já estava ativo.');
        }

        $user->is_active = true;
        $user->save();

        UserAuditLogger::recordAuthenticated('user.reactivated', [
            'target_username' => $user->username,
            'target_id' => (int) $user->getKey(),
        ], UserAuditLog::SUBJECT_USER, (int) $user->getKey());

        return back()->with('status', 'Usuário reativado.');
    }

    public function promoteAdmin(PromoteAdminRequest $request, User $user): RedirectResponse
    {
        if ($user->is_admin) {
            return back()->with('status', 'Este usuário já é administrador.');
        }

        $user->is_admin = true;
        $user->save();

        UserAuditLogger::recordAuthenticated('user.promoted_admin', [
            'target_username' => $user->username,
            'target_id' => (int) $user->getKey(),
        ], UserAuditLog::SUBJECT_USER, (int) $user->getKey());

        return back()->with('status', 'Usuário promovido a administrador.');
    }

    public function demoteAdmin(DemoteAdminRequest $request, User $user): RedirectResponse
    {
        if (! $user->is_admin) {
            return back()->with('status', 'Este usuário já não é administrador.');
        }

        $user->is_admin = false;
        $user->save();

        UserAuditLogger::recordAuthenticated('user.demoted_admin', [
            'target_username' => $user->username,
            'target_id' => (int) $user->getKey(),
        ], UserAuditLog::SUBJECT_USER, (int) $user->getKey());

        return back()->with('status', 'Administrador rebaixado para acesso só a integrações.');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createUserRow(array $attributes): User
    {
        try {
            return User::query()->create($attributes);
        } catch (QueryException $e) {
            if (! $this->isPostgresUsersPrimaryKeyDuplicate($e)) {
                throw $e;
            }
        }

        PostgresUsersIdSequence::sync();

        return User::query()->create($attributes);
    }

    private function isPostgresUsersPrimaryKeyDuplicate(QueryException $e): bool
    {
        if (PostgresUsersIdSequence::driver() !== 'pgsql') {
            return false;
        }

        return str_contains($e->getMessage(), 'users_pkey');
    }
}
