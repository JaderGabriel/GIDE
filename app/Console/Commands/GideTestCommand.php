<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class GideTestCommand extends Command
{
    /**
     * @var array<string, string>
     */
    public const THEMES = [
        'telas-publico' => 'Rotas públicas (ex.: home)',
        'telas-auth' => 'Autenticação, sessão e conta inativa',
        'telas-users' => 'Gestão de utilizadores (/usuarios)',
        'telas-auditoria' => 'Auditoria de contas (/admin/auditoria-usuarios)',
        'telas-integracoes' => 'Visão geral /integracoes e JSON de estado',
        'telas-dashboard' => 'Dashboard e redirecionamento por perfil',
        'telas-sms' => 'Entregas SMS (/sms): listagem, filtros e detalhe',
        'api-ieducar' => 'API inbound iEducar (HMAC)',
        'api-gestor' => 'API inbound Gestor (HMAC)',
        'api-catraca' => 'API inbound catraca-frequência (Bearer)',
        'api-catraca-webhook' => 'Webhook catraca access-events (Bearer Gestor, /api/v1/catraca/access-events)',
        'fluxo-frequencia' => 'Job de registro de frequência → HTTP ao iEducar (falso)',
        'fluxo-enrollment' => 'Matrícula iEducar → job outbound (partilhado com api-ieducar)',
        'unit' => 'Testes unitários (pasta tests/Unit)',
    ];

    protected $signature = 'gide:test
                            {--theme=* : Grupo PHPUnit (repetir opção ou usar vírgulas num único valor)}
                            {--list : Lista temas disponíveis e termina}
                            {--testdox : Saída PHPUnit em frases legíveis (um resumo por teste)}
                            {--no-structured-outcome : Não imprimir blocos "Resumo do cenário" (TEST_STRUCTURED_OUTCOME=0)}';

    protected $description = 'Executa PHPUnit do GIDE por tema (--group) ou toda a suíte';

    public function handle(): int
    {
        if ($this->option('list')) {
            $rows = collect(self::THEMES)->map(fn (string $desc, string $key) => [$key, $desc])->values()->all();
            $this->table(['Tema (--theme)', 'Descrição'], $rows);
            $this->newLine();
            $this->line('Exemplos:');
            $this->line('  php artisan gide:test');
            $this->line('  php artisan gide:test --theme=telas-auth');
            $this->line('  php artisan gide:test --theme=telas-auth --theme=api-ieducar');
            $this->line('  php artisan gide:test --theme=telas-users,api-gestor');
            $this->line('  php artisan gide:test --theme=telas-sms --testdox');
            $this->newLine();
            $this->comment('Requer extensão PHP pdo_sqlite para testes com RefreshDatabase (phpunit.xml usa SQLite em memória).');
            $this->comment('Relatórios por cenário: phpunit.xml define TEST_STRUCTURED_OUTCOME=1 (desative com --no-structured-outcome).');

            return self::SUCCESS;
        }

        $themes = $this->normalizeThemes((array) $this->option('theme'));
        if ($themes !== []) {
            $unknown = array_diff($themes, array_keys(self::THEMES));
            if ($unknown !== []) {
                $this->error('Temas desconhecidos: '.implode(', ', $unknown));
                $this->line('Use --list para ver os nomes válidos.');

                return self::FAILURE;
            }
        }

        $phpunit = base_path('vendor/bin/phpunit');
        if (! is_file($phpunit)) {
            $this->error('PHPUnit não encontrado em vendor/bin/phpunit. Execute composer install.');

            return self::FAILURE;
        }

        $cmd = [PHP_BINARY, $phpunit, '--colors=always', '--configuration', base_path('phpunit.xml')];
        foreach ($themes as $group) {
            $cmd[] = '--group='.$group;
        }
        if ($this->option('testdox')) {
            $cmd[] = '--testdox';
        }

        $this->line('Comando: '.implode(' ', $cmd));

        $process = new Process($cmd, base_path(), null, null, null);
        $hadStructured = getenv('TEST_STRUCTURED_OUTCOME');
        if ($this->option('no-structured-outcome')) {
            putenv('TEST_STRUCTURED_OUTCOME=0');
        }
        try {
            $process->run(function (string $type, string $buffer): void {
                $this->output->write($buffer);
            });
        } finally {
            if ($this->option('no-structured-outcome')) {
                if ($hadStructured === false) {
                    putenv('TEST_STRUCTURED_OUTCOME');
                } else {
                    putenv('TEST_STRUCTURED_OUTCOME='.(string) $hadStructured);
                }
            }
        }

        return $process->getExitCode() === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  array<int, string|null>  $raw
     * @return list<string>
     */
    private function normalizeThemes(array $raw): array
    {
        $out = [];
        foreach ($raw as $part) {
            if ($part === null || $part === '') {
                continue;
            }
            foreach (preg_split('/\s*,\s*/', $part, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $t) {
                $t = trim((string) $t);
                if ($t !== '') {
                    $out[] = $t;
                }
            }
        }

        return array_values(array_unique($out));
    }
}
