<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    private function isAdministrator(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    public function deactivate(User $actor, User $target): Response|bool
    {
        if (! $this->isAdministrator($actor)) {
            return false;
        }

        if ((int) $actor->getKey() === (int) $target->getKey()) {
            return Response::deny('Você não pode desativar a própria conta.');
        }

        return true;
    }

    public function reactivate(User $actor, User $target): bool
    {
        return $this->isAdministrator($actor);
    }

    public function promoteAdmin(User $actor, User $target): bool
    {
        return $this->isAdministrator($actor);
    }

    public function demoteAdmin(User $actor, User $target): Response|bool
    {
        if (! $this->isAdministrator($actor)) {
            return false;
        }

        if ((int) $actor->getKey() === (int) $target->getKey()) {
            return Response::deny('Você não pode remover seu próprio perfil de administrador. Peça outro administrador ou use outra conta.');
        }

        if ($target->is_admin && $target->isActive() && User::activeAdministratorsCount() <= 1) {
            return Response::deny('Deve existir pelo menos um administrador ativo.');
        }

        return true;
    }
}
