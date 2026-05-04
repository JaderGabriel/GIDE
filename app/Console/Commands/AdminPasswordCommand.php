<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AdminPasswordCommand extends Command
{
    protected $signature = 'admin:password
                            {password? : Nova senha (omitir para pedido interactivo oculto)}
                            {--username= : Username do utilizador administrador a alterar (omissão: primeiro admin por id)}';

    protected $description = 'Define a senha de um utilizador com is_admin=true.';

    public function handle(): int
    {
        $username = trim((string) $this->option('username'));
        $query = User::query()->where('is_admin', true);
        if ($username !== '') {
            $query->where('username', $username);
        }
        /** @var User|null $admin */
        $admin = $query->orderBy('id')->first();

        if (! $admin instanceof User) {
            $this->error($username !== ''
                ? 'Nenhum administrador encontrado com esse username.'
                : 'Nenhum utilizador com is_admin=true na base.');

            return self::FAILURE;
        }

        $plain = (string) ($this->argument('password') ?? '');
        if ($plain === '') {
            $plain = (string) $this->secret('Nova senha');
            $confirm = (string) $this->secret('Confirmar senha');
            if ($plain === '' || $plain !== $confirm) {
                $this->error('Senha vazia ou confirmação não coincide.');

                return self::FAILURE;
            }
        }

        try {
            Validator::make(
                ['password' => $plain],
                ['password' => ['required', 'string', Password::min(8)]],
            )->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($e->errors() as $msgs) {
                foreach ($msgs as $m) {
                    $this->error($m);
                }
            }

            return self::FAILURE;
        }

        $admin->password = $plain;
        $admin->save();

        $this->info('Senha actualizada para o administrador: '.$admin->username.' (id '.$admin->id.').');

        return self::SUCCESS;
    }
}
