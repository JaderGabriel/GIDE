<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Enviar facial • {{ config('app.name', 'Bridge ERP') }}</title>

        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="icon" href="/favicon.svg" sizes="any">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <script>
            (function () {
                try {
                    const stored = localStorage.getItem('theme');
                    const theme =
                        stored === 'light' || stored === 'dark'
                            ? stored
                            : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                    document.documentElement.classList.toggle('dark', theme === 'dark');
                    document.documentElement.dataset.theme = theme;
                } catch (_) {}
            })();
        </script>

        <link rel="stylesheet" href="/home.css">
        <script defer src="/home.js"></script>
        <style>
            .info-card {
                border: 1px solid var(--border);
                border-radius: 18px;
                background: var(--card-strong);
                box-shadow: var(--shadow-soft);
                padding: 16px;
            }
            .info-grid {
                display: grid;
                gap: 10px;
                margin-top: 12px;
            }
            @media (min-width: 900px) {
                .info-grid { grid-template-columns: 1fr 1fr; }
            }
            .span-2 { grid-column: 1 / -1; }
            .info-row { display: flex; gap: 10px; align-items: flex-start; }
            .info-ico {
                width: 34px; height: 34px;
                border-radius: 12px;
                display: grid; place-items: center;
                border: 1px solid var(--border);
                background: var(--surface-2);
                flex: 0 0 auto;
            }
            .info-ico svg { width: 18px; height: 18px; }
            .chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
            .chip {
                display: inline-flex; align-items: center; gap: 8px;
                padding: 6px 10px;
                border-radius: 999px;
                border: 1px solid var(--border);
                background: color-mix(in srgb, var(--bg0) 55%, transparent);
                font-size: 12px;
                color: var(--muted);
            }
            .chip svg { width: 14px; height: 14px; }
            .chip--good {
                border-color: color-mix(in srgb, var(--accent-c) 45%, var(--border));
                background: color-mix(in srgb, var(--accent-c) 14%, transparent);
                color: color-mix(in srgb, var(--text) 92%, var(--accent-c));
            }
            .chip--warn {
                border-color: color-mix(in srgb, #f59e0b 55%, var(--border));
                background: color-mix(in srgb, #f59e0b 16%, transparent);
                color: color-mix(in srgb, var(--text) 92%, #f59e0b);
            }
            .chip--bad {
                border-color: color-mix(in srgb, #ef4444 55%, var(--border));
                background: color-mix(in srgb, #ef4444 14%, transparent);
                color: #ef4444;
            }
            .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="bridge-shell">
            <header class="bridge-header">
                <div class="bridge-container">
                    <div class="bridge-header__inner">
                        <a class="bridge-brand" href="{{ auth()->check() ? url('/dashboard') : url('/') }}">
                            <img src="/favicon.svg" alt="" class="bridge-brand__logo" />
                            <div class="bridge-brand__text">
                                <div class="bridge-brand__name">{{ config('app.name', 'Bridge ERP') }}</div>
                                <div class="bridge-brand__tagline">Integração iEducar → Gestor</div>
                            </div>
                        </a>

                        <div class="bridge-actions">
                            <button type="button" class="bridge-btn bridge-iconbtn" data-theme-toggle aria-pressed="false" title="Mudar tema">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 18a6 6 0 1 0 0-12 6 6 0 0 0 0 12Z" stroke="currentColor" stroke-width="2"/>
                                    <path d="M12 2v2M12 20v2M4 12H2M22 12h-2M5 5l1.5 1.5M17.5 17.5 19 19M19 5l-1.5 1.5M6.5 17.5 5 19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <main class="bridge-main">
                <div class="bridge-container">
                    <div class="bridge-auth">
                        <div class="bridge-panel">
                            <div class="bridge-panel__head">
                                <div class="bridge-panel__title">Coleta de foto para biometria facial</div>
                                <div class="bridge-panel__meta">apenas para este atendimento</div>
                            </div>

                            <p class="bridge-muted" style="margin-top: 14px;">
                                Para continuar, ative a câmera, tire a foto e confirme o envio.
                            </p>

                            <p class="bridge-muted" style="margin-top: 14px;">
                                A foto é usada somente para cadastrar/atualizar a biometria facial deste aluno e não fica salva nesta tela.
                            </p>

                            <section class="info-card" style="margin-top: 14px;">
                                <div class="bridge-panel__head" style="margin-top: 0;">
                                    <div class="bridge-panel__title">Dados do aluno (conferência)</div>
                                    <div class="bridge-panel__meta">confira os dados antes de continuar</div>
                                </div>

                                @php
                                    $s = is_array($ieducar_status ?? null) ? $ieducar_status : null;
                                    // Novo contrato (iEducar → GIDE): pessoa/fisica/matricula no topo.
                                    $situacao = $s ? (data_get($s, 'matricula.situacao_descricao') ?? data_get($s, 'status.matricula.situacao_descricao')) : null;
                                    $ano = $s ? (data_get($s, 'matricula.ano_letivo') ?? data_get($s, 'matricula.ano') ?? data_get($s, 'status.matricula.ano')) : null;
                                    $ativo = $s ? (data_get($s, 'aluno.ativo') ?? data_get($s, 'status.aluno_cadastro_ativo')) : null;

                                    $alunoNome = $s ? (data_get($s, 'pessoa.nome') ?? data_get($s, 'pessoa.nome_completo') ?? data_get($s, 'status.aluno.nome')) : null;
                                    $alunoSexo = $s ? (data_get($s, 'fisica.sexo') ?? data_get($s, 'status.aluno.sexo')) : null;
                                    $alunoDataNascimento = $s ? (data_get($s, 'fisica.data_nascimento') ?? data_get($s, 'status.aluno.data_nascimento')) : null;
                                    $alunoEmail = $s ? (data_get($s, 'pessoa.email') ?? data_get($s, 'status.aluno.email')) : null;
                                    $alunoCpf = $s ? (data_get($s, 'documentos.cpf') ?? data_get($s, 'cpf')) : null;

                                    $telefones = $s ? (data_get($s, 'telefones_contato_ordenados')
                                        ?? data_get($s, 'telefones_contato')
                                        ?? data_get($s, 'contatos.telefones')
                                        ?? data_get($s, 'telefones')
                                        ?? data_get($s, 'contato.telefones')
                                        ?? data_get($s, 'pessoa.telefones')
                                        ?? data_get($s, 'pessoa.fones')) : null;
                                    $respTelefones = $s ? (data_get($s, 'responsaveis_telefones') ?? data_get($s, 'responsaveis') ?? data_get($s, 'contatos.responsaveis')) : null;

                                    $curso = $s ? (data_get($s, 'matricula.curso')
                                        ?? data_get($s, 'status.matricula.curso')
                                        ?? data_get($s, 'status.matricula.curso_nome')
                                        ?? data_get($s, 'status.matricula.nm_curso')
                                        ?? data_get($s, 'curso')) : null;
                                    $turma = $s ? (data_get($s, 'matricula.turma')
                                        ?? data_get($s, 'status.matricula.turma')
                                        ?? data_get($s, 'status.matricula.turma_nome')
                                        ?? data_get($s, 'status.matricula.nm_turma')
                                        ?? data_get($s, 'turma')) : null;
                                    $turno = $s ? (data_get($s, 'matricula.turno')
                                        ?? data_get($s, 'status.matricula.turno')
                                        ?? data_get($s, 'status.matricula.turno_descricao')
                                        ?? data_get($s, 'status.matricula.nm_turno')) : null;
                                    $dataMatricula = $s ? (data_get($s, 'matricula.data_matricula')
                                        ?? data_get($s, 'status.matricula.data_matricula')
                                        ?? data_get($s, 'status.matricula.dt_matricula')
                                        ?? data_get($s, 'status.matricula.data')
                                        ?? data_get($s, 'status.matricula.matricula_em')) : null;

                                    $formatDateBr = static function ($raw): ?string {
                                        if (! is_string($raw) || trim($raw) === '') {
                                            return null;
                                        }
                                        $raw = trim($raw);
                                        // ISO date: YYYY-MM-DD (ignore time)
                                        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $raw, $m) === 1) {
                                            return $m[3].'/'.$m[2].'/'.$m[1];
                                        }
                                        return $raw;
                                    };

                                    $alunoDataNascimentoBr = $formatDateBr(is_string($alunoDataNascimento) ? $alunoDataNascimento : null);
                                    $dataMatriculaBr = $formatDateBr(is_string($dataMatricula) ? $dataMatricula : null);

                                    $maskCpf = static function ($cpf): ?string {
                                        if (! is_string($cpf) || trim($cpf) === '') {
                                            return null;
                                        }
                                        $d = preg_replace('/\D/', '', $cpf) ?? '';
                                        if (strlen($d) === 11) {
                                            return '***.***.***-'.substr($d, -2);
                                        }
                                        // fallback: mascara parcial mantendo só o final
                                        if (strlen($d) >= 2) {
                                            return str_repeat('*', max(0, strlen($d) - 2)).substr($d, -2);
                                        }
                                        return '***';
                                    };

                                    $alunoCpfMasked = $maskCpf(is_string($alunoCpf) ? $alunoCpf : null);

                                    $normName = static function ($v): string {
                                        $v = is_string($v) ? $v : '';
                                        $v = trim(mb_strtolower(preg_replace('/\s+/', ' ', $v) ?? $v));

                                        return $v;
                                    };

                                    $personKey = static function (?string $nome, ?string $vinculo) use ($normName): string {
                                        $n = $normName((string) $nome);
                                        $v = $normName((string) $vinculo);
                                        if ($n === '' && $v === '') {
                                            return '__sem_identificacao__';
                                        }

                                        return $n."\x1f".$v;
                                    };

                                    $prettyVinculo = static function ($v): ?string {
                                        if (! is_string($v) || trim($v) === '') {
                                            return null;
                                        }
                                        $key = trim(mb_strtolower($v));
                                        $map = [
                                            'mae' => 'Mãe',
                                            'mãe' => 'Mãe',
                                            'pai' => 'Pai',
                                            'responsavel_legal' => 'Responsável legal',
                                            'responsável_legal' => 'Responsável legal',
                                            'outro_responsavel' => 'Outro responsável',
                                            'outro_responsável' => 'Outro responsável',
                                        ];
                                        if (isset($map[$key])) {
                                            return $map[$key];
                                        }
                                        $key = str_replace('_', ' ', $key);
                                        return mb_strtoupper(mb_substr($key, 0, 1)).mb_substr($key, 1);
                                    };

                                    $digitsOnly = static function ($raw): string {
                                        if ($raw === null) {
                                            return '';
                                        }
                                        if (is_scalar($raw)) {
                                            return preg_replace('/\D/', '', (string) $raw) ?? '';
                                        }
                                        if (is_array($raw)) {
                                            // Novo contrato: telefones como { ddd, numero, tipo }
                                            $ddd = $raw['ddd'] ?? null;
                                            $num = $raw['numero'] ?? $raw['number'] ?? $raw['valor'] ?? $raw['fone'] ?? null;
                                            if (is_scalar($ddd) && is_scalar($num)) {
                                                return preg_replace('/\D/', '', (string) $ddd.(string) $num) ?? '';
                                            }
                                            $inner = $num;

                                            return is_scalar($inner) ? (preg_replace('/\D/', '', (string) $inner) ?? '') : '';
                                        }

                                        return '';
                                    };

                                    $formatPhoneBr = static function (?string $raw): ?string {
                                        if ($raw === null) {
                                            return null;
                                        }
                                        $trim = trim($raw);
                                        if ($trim === '') {
                                            return null;
                                        }
                                        $d = preg_replace('/\D/', '', $trim) ?? '';
                                        if ($d === '') {
                                            return $trim;
                                        }
                                        if (str_starts_with($d, '55') && strlen($d) >= 12) {
                                            $d = substr($d, 2);
                                        }
                                        $len = strlen($d);
                                        if ($len === 11) {
                                            return sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 5), substr($d, 7, 4));
                                        }
                                        if ($len === 10) {
                                            return sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 4), substr($d, 6, 4));
                                        }
                                        if ($len === 9 && ($d[0] ?? '') === '9') {
                                            return sprintf('%s-%s', substr($d, 0, 5), substr($d, 4, 4));
                                        }
                                        if ($len === 8) {
                                            return sprintf('%s-%s', substr($d, 0, 4), substr($d, 4, 4));
                                        }
                                        if ($len > 11) {
                                            return '+'.$d;
                                        }

                                        return $trim;
                                    };

                                    $extractPhonesFromItem = static function (array $item) use (&$extractPhonesFromItem): array {
                                        $out = [];

                                        // Novo contrato: pode vir como { ddd, numero }
                                        if (isset($item['ddd']) && array_key_exists('numero', $item)) {
                                            $ddd = $item['ddd'];
                                            $num = $item['numero'];
                                            if (is_scalar($ddd) && is_scalar($num)) {
                                                $d = preg_replace('/\D/', '', (string) $ddd) ?? '';
                                                $n = preg_replace('/\D/', '', (string) $num) ?? '';
                                                if ($d !== '' && $n !== '') {
                                                    $out[] = $d.$n;
                                                } elseif ($n !== '') {
                                                    $out[] = $n;
                                                }
                                            }
                                        }

                                        foreach (['telefone', 'fone', 'phone', 'celular', 'whatsapp', 'numero'] as $k) {
                                            if (! array_key_exists($k, $item)) {
                                                continue;
                                            }
                                            $v = $item[$k];
                                            if (is_string($v) && trim($v) !== '') {
                                                $out[] = trim($v);
                                            } elseif (is_array($v)) {
                                                // { ddd, numero } (novo contrato)
                                                if (isset($v['ddd']) && array_key_exists('numero', $v)) {
                                                    $ddd = $v['ddd'];
                                                    $num = $v['numero'];
                                                    if (is_scalar($ddd) && is_scalar($num)) {
                                                        $d = preg_replace('/\D/', '', (string) $ddd) ?? '';
                                                        $n = preg_replace('/\D/', '', (string) $num) ?? '';
                                                        if ($d !== '' && $n !== '') {
                                                            $out[] = $d.$n;
                                                        } elseif ($n !== '') {
                                                            $out[] = $n;
                                                        }
                                                    }
                                                }
                                                $n = $v['numero'] ?? $v['number'] ?? $v['valor'] ?? null;
                                                if (is_string($n) && trim($n) !== '') {
                                                    $out[] = trim($n);
                                                }
                                            }
                                        }
                                        if (isset($item['telefones']) && is_array($item['telefones'])) {
                                            foreach ($item['telefones'] as $t) {
                                                if (is_string($t) && trim($t) !== '') {
                                                    $out[] = trim($t);
                                                } elseif (is_array($t)) {
                                                    $out = array_merge($out, $extractPhonesFromItem($t));
                                                }
                                            }
                                        }

                                        return $out;
                                    };

                                    $telList = is_array($telefones) ? $telefones : [];
                                    $buckets = [];
                                    $bucketOrder = [];

                                    $ensureBucket = static function (string $key, ?string $nome, ?string $vinculo) use (&$buckets, &$bucketOrder): void {
                                        if (! isset($buckets[$key])) {
                                            $buckets[$key] = [
                                                'nome' => $nome,
                                                'vinculo' => $vinculo,
                                                'digits' => [],
                                            ];
                                            $bucketOrder[] = $key;
                                        } else {
                                            if (($buckets[$key]['nome'] ?? null) === null && is_string($nome) && trim($nome) !== '') {
                                                $buckets[$key]['nome'] = trim($nome);
                                            }
                                            if (($buckets[$key]['vinculo'] ?? null) === null && is_string($vinculo) && trim($vinculo) !== '') {
                                                $buckets[$key]['vinculo'] = trim($vinculo);
                                            }
                                        }
                                    };

                                    $addPhonesToBucket = static function (string $key, ?string $nome, ?string $vinculo, array $rawPhones) use (&$buckets, $ensureBucket, $digitsOnly): void {
                                        $ensureBucket($key, $nome, $vinculo);
                                        foreach ($rawPhones as $raw) {
                                            if (! is_string($raw) || trim($raw) === '') {
                                                continue;
                                            }
                                            $dig = $digitsOnly($raw);
                                            if ($dig === '') {
                                                continue;
                                            }
                                            $buckets[$key]['digits'][$dig] = trim($raw);
                                        }
                                    };

                                    $apiRespLists = [];
                                    if ($s) {
                                        foreach ([
                                            'responsaveis_telefones',
                                            'responsaveis',
                                            'status.responsaveis_telefones',
                                            'status.responsaveis',
                                            'aluno.responsaveis_telefones',
                                            'aluno.responsaveis',
                                        ] as $path) {
                                            $v = data_get($s, $path);
                                            if (is_array($v)) {
                                                $apiRespLists[] = $v;
                                            }
                                        }
                                    }

                                    foreach ($apiRespLists as $list) {
                                        if (array_is_list($list)) {
                                            foreach ($list as $item) {
                                                if (! is_array($item)) {
                                                    continue;
                                                }
                                                $nome = $item['nome']
                                                    ?? $item['name']
                                                    ?? $item['nm_responsavel']
                                                    ?? $item['responsavel']
                                                    ?? null;
                                                $vinculo = $prettyVinculo($item['vinculo']
                                                    ?? $item['parentesco']
                                                    ?? $item['tipo']
                                                    ?? $item['relacao']
                                                    ?? $item['nm_parentesco']
                                                    ?? null);
                                                $nomeStr = is_string($nome) && trim($nome) !== '' ? trim($nome) : null;
                                                $vinculoStr = is_string($vinculo) && trim($vinculo) !== '' ? trim($vinculo) : null;
                                                $key = $personKey($nomeStr, $vinculoStr);
                                                $addPhonesToBucket($key, $nomeStr, $vinculoStr, $extractPhonesFromItem($item));
                                            }

                                            continue;
                                        }

                                        if (isset($list['nome']) || isset($list['nm_responsavel']) || isset($list['telefone']) || isset($list['telefones'])) {
                                            $nome = $list['nome'] ?? $list['name'] ?? $list['nm_responsavel'] ?? null;
                                            $vinculo = $prettyVinculo($list['vinculo'] ?? $list['parentesco'] ?? $list['tipo'] ?? $list['relacao'] ?? $list['nm_parentesco'] ?? null);
                                            $nomeStr = is_string($nome) && trim($nome) !== '' ? trim($nome) : null;
                                            $vinculoStr = is_string($vinculo) && trim($vinculo) !== '' ? trim($vinculo) : null;
                                            $key = $personKey($nomeStr, $vinculoStr);
                                            $addPhonesToBucket($key, $nomeStr, $vinculoStr, $extractPhonesFromItem($list));
                                        }
                                    }

                                    $respPayload = $responsavel ?? null;
                                    $payloadList = [];
                                    if (is_array($respPayload)) {
                                        $payloadList = isset($respPayload[0]) ? $respPayload : [$respPayload];
                                    }

                                    foreach ($payloadList as $item) {
                                        if (! is_array($item)) {
                                            continue;
                                        }
                                        $nome = $item['nome'] ?? $item['name'] ?? null;
                                        $vinculo = $prettyVinculo($item['vinculo'] ?? $item['parentesco'] ?? $item['tipo'] ?? $item['relacao'] ?? null);
                                        $nomeStr = is_string($nome) && trim($nome) !== '' ? trim($nome) : null;
                                        $vinculoStr = is_string($vinculo) && trim($vinculo) !== '' ? trim($vinculo) : null;
                                        $key = $personKey($nomeStr, $vinculoStr);
                                        $addPhonesToBucket($key, $nomeStr, $vinculoStr, $extractPhonesFromItem($item));
                                    }

                                    $telStringsFromList = [];
                                    foreach ($telList as $t) {
                                        if (is_string($t) && trim($t) !== '') {
                                            $telStringsFromList[] = trim($t);
                                        } elseif (is_array($t)) {
                                            foreach ($extractPhonesFromItem($t) as $p) {
                                                $telStringsFromList[] = $p;
                                            }
                                        }
                                    }

                                    if (count($buckets) === 0) {
                                        if (count($telStringsFromList) > 0) {
                                            $k0 = '__sem_identificacao__';
                                            $ensureBucket($k0, 'Contato', null);
                                            foreach ($telStringsFromList as $p) {
                                                $addPhonesToBucket($k0, 'Contato', null, [$p]);
                                            }
                                        }
                                    } else {
                                        $ti = 0;
                                        foreach ($bucketOrder as $bk) {
                                            if (count($buckets[$bk]['digits']) > 0) {
                                                continue;
                                            }
                                            while ($ti < count($telStringsFromList)) {
                                                $candidate = $telStringsFromList[$ti];
                                                $ti++;
                                                $dig = $digitsOnly($candidate);
                                                if ($dig === '') {
                                                    continue;
                                                }
                                                $already = false;
                                                foreach ($buckets as $bb) {
                                                    if (isset($bb['digits'][$dig])) {
                                                        $already = true;
                                                        break;
                                                    }
                                                }
                                                if ($already) {
                                                    continue;
                                                }
                                                $addPhonesToBucket($bk, $buckets[$bk]['nome'], $buckets[$bk]['vinculo'], [$candidate]);
                                                break;
                                            }
                                        }
                                        $kFallback = '__sem_identificacao__';
                                        while ($ti < count($telStringsFromList)) {
                                            $candidate = $telStringsFromList[$ti];
                                            $ti++;
                                            $dig = $digitsOnly($candidate);
                                            if ($dig === '') {
                                                continue;
                                            }
                                            $already = false;
                                            foreach ($buckets as $bb) {
                                                if (isset($bb['digits'][$dig])) {
                                                    $already = true;
                                                    break;
                                                }
                                            }
                                            if ($already) {
                                                continue;
                                            }
                                            $ensureBucket($kFallback, 'Outros contatos', null);
                                            $addPhonesToBucket($kFallback, 'Outros contatos', null, [$candidate]);
                                        }
                                    }

                                    $responsaveisRows = [];
                                    foreach ($bucketOrder as $bk) {
                                        $b = $buckets[$bk];
                                        $formatted = [];
                                        foreach ($b['digits'] as $dig => $_raw) {
                                            $f = $formatPhoneBr($_raw);
                                            if ($f) {
                                                $formatted[] = $f;
                                            }
                                        }
                                        $formatted = array_values(array_unique($formatted));
                                        $labelNome = $b['nome'] ?? null;
                                        if ($bk === '__sem_identificacao__' && ($labelNome === null || $labelNome === '')) {
                                            $labelNome = count($formatted) ? 'Contato' : null;
                                        }
                                        $responsaveisRows[] = [
                                            'nome' => $labelNome,
                                            'vinculo' => $b['vinculo'] ?? null,
                                            'telefones' => $formatted,
                                        ];
                                    }

                                    $responsaveisRows = array_values(array_filter($responsaveisRows, static function ($r) {
                                        return ($r['nome'] ?? null) || ($r['vinculo'] ?? null) || (count($r['telefones'] ?? []) > 0);
                                    }));

                                    $chipClass = 'chip';
                                    if (! empty($ieducar_status_error)) {
                                        $chipClass = 'chip chip--bad';
                                    } elseif (is_string($situacao) && $situacao !== '') {
                                        $lower = mb_strtolower($situacao);
                                        if (str_contains($lower, 'curs')) $chipClass = 'chip chip--good';
                                        elseif (str_contains($lower, 'inativ') || str_contains($lower, 'cancel') || str_contains($lower, 'deslig')) $chipClass = 'chip chip--bad';
                                        else $chipClass = 'chip chip--warn';
                                    }
                                @endphp

                                <div class="info-grid">
                                    <div class="info-row">
                                        <div class="info-ico" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none">
                                                <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2"/>
                                                <path d="M4 20a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <div><strong>Aluno</strong></div>
                                            @if ($alunoNome || $alunoSexo || $alunoDataNascimentoBr)
                                                <div style="margin-top: 10px; display: grid; gap: 8px;">
                                                    <div style="display:flex; gap: 10px; align-items:flex-start; border: 1px solid var(--border); border-radius: 14px; padding: 10px 12px; background: color-mix(in srgb, var(--bg0) 55%, transparent);">
                                                        <div class="info-ico" aria-hidden="true" style="width: 30px; height: 30px; border-radius: 12px;">
                                                            <svg viewBox="0 0 24 24" fill="none">
                                                                <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2"/>
                                                                <path d="M4 20a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                            </svg>
                                                        </div>
                                                        <div>
                                                            <div style="font-size: 12px; color: var(--muted); font-weight: 650;">Nome completo</div>
                                                            <div style="font-weight: 800; margin-top: 3px;">{{ $alunoNome ?? 'não informado' }}</div>
                                                        </div>
                                                    </div>

                                                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                                        <div style="display:flex; gap: 10px; align-items:flex-start; border: 1px solid var(--border); border-radius: 14px; padding: 10px 12px; background: color-mix(in srgb, var(--bg0) 55%, transparent);">
                                                            <div class="info-ico" aria-hidden="true" style="width: 30px; height: 30px; border-radius: 12px;">
                                                                <svg viewBox="0 0 24 24" fill="none">
                                                                    <path d="M12 2v20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                                    <path d="M5 7h14M5 17h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                                </svg>
                                                            </div>
                                                            <div>
                                                                <div style="font-size: 12px; color: var(--muted); font-weight: 650;">Sexo</div>
                                                                <div class="mono" style="margin-top: 3px; font-size: 14px;">{{ $alunoSexo ?? 'não informado' }}</div>
                                                            </div>
                                                        </div>

                                                        <div style="display:flex; gap: 10px; align-items:flex-start; border: 1px solid var(--border); border-radius: 14px; padding: 10px 12px; background: color-mix(in srgb, var(--bg0) 55%, transparent);">
                                                            <div class="info-ico" aria-hidden="true" style="width: 30px; height: 30px; border-radius: 12px;">
                                                                <svg viewBox="0 0 24 24" fill="none">
                                                                    <path d="M7 3v3M17 3v3M4 8h16M5 6h14a2 2 0 0 1 2 2v13H3V8a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                                                    <path d="M7 12h5M7 16h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                                </svg>
                                                            </div>
                                                            <div>
                                                                <div style="font-size: 12px; color: var(--muted); font-weight: 650;">Data de nascimento</div>
                                                                <div class="mono" style="margin-top: 3px; font-size: 14px;">{{ $alunoDataNascimentoBr ?? 'não informado' }}</div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    @if ($alunoEmail || $alunoCpf)
                                                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                                            <div style="display:flex; gap: 10px; align-items:flex-start; border: 1px solid var(--border); border-radius: 14px; padding: 10px 12px; background: color-mix(in srgb, var(--bg0) 55%, transparent);">
                                                                <div class="info-ico" aria-hidden="true" style="width: 30px; height: 30px; border-radius: 12px;">
                                                                    <svg viewBox="0 0 24 24" fill="none">
                                                                        <path d="M4 6h16v12H4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                                                        <path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                                    </svg>
                                                                </div>
                                                                <div>
                                                                    <div style="font-size: 12px; color: var(--muted); font-weight: 650;">E-mail</div>
                                                                    <div class="mono" style="margin-top: 3px; font-size: 13px; overflow-wrap:anywhere;">{{ $alunoEmail ?? 'não informado' }}</div>
                                                                </div>
                                                            </div>

                                                            <div style="display:flex; gap: 10px; align-items:flex-start; border: 1px solid var(--border); border-radius: 14px; padding: 10px 12px; background: color-mix(in srgb, var(--bg0) 55%, transparent);">
                                                                <div class="info-ico" aria-hidden="true" style="width: 30px; height: 30px; border-radius: 12px;">
                                                                    <svg viewBox="0 0 24 24" fill="none">
                                                                        <path d="M6 7h12v14H6z" stroke="currentColor" stroke-width="2"/>
                                                                        <path d="M9 3h6v4H9z" stroke="currentColor" stroke-width="2"/>
                                                                        <path d="M8 12h8M8 16h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                                    </svg>
                                                                </div>
                                                                <div>
                                                                    <div style="font-size: 12px; color: var(--muted); font-weight: 650;">CPF</div>
                                                                    <div class="mono" style="margin-top: 3px; font-size: 14px;">{{ $alunoCpfMasked ?? 'não informado' }}</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        
                                        </div>
                                    </div>

                                    <div class="info-row">
                                        <div class="info-ico" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none">
                                                <path d="M4 4h16v16H4z" stroke="currentColor" stroke-width="2"/>
                                                <path d="M7 8h10M7 12h10M7 16h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <div><strong>Dados da matrícula</strong></div>
                                            @if (!empty($ieducar_status_error))
                                                <div style="margin-top: 8px; padding: 10px 12px; border-radius: 14px; border: 1px solid color-mix(in srgb, #ef4444 35%, var(--border)); background: color-mix(in srgb, #ef4444 10%, transparent);">
                                                    <div style="font-weight: 650; color: #ef4444;">Não foi possível buscar os dados da matrícula (TI)</div>
                                                    <div class="mono" style="margin-top: 6px; color: #ef4444; white-space: pre-wrap; overflow-wrap: anywhere;">{{ $ieducar_status_error }}</div>
                                                </div>
                                            @else
                                                <div style="margin-top: 10px; display: grid; gap: 8px;">
                                                    <div style="display:grid; grid-template-columns: 0.7fr 1.3fr; gap: 8px;">
                                                        <div style="display:flex; gap: 10px; align-items:flex-start; border: 1px solid var(--border); border-radius: 14px; padding: 10px 12px; background: color-mix(in srgb, var(--bg0) 55%, transparent);">
                                                            <div class="info-ico" aria-hidden="true" style="width: 30px; height: 30px; border-radius: 12px;">
                                                                <svg viewBox="0 0 24 24" fill="none">
                                                                    <path d="M7 3v3M17 3v3M4 8h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                                    <path d="M5 6h14a2 2 0 0 1 2 2v13H3V8a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                                                    <path d="M6 12h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                                </svg>
                                                            </div>
                                                            <div>
                                                                <div style="font-size: 12px; color: var(--muted); font-weight: 650;">Ano</div>
                                                                <div class="mono" style="margin-top: 3px; font-size: 14px;">{{ $ano ?? 'não encontrado' }}</div>
                                                            </div>
                                                        </div>

                                                        <div style="display:flex; gap: 10px; align-items:flex-start; border: 1px solid var(--border); border-radius: 14px; padding: 10px 12px; background: color-mix(in srgb, var(--bg0) 55%, transparent);">
                                                            <div class="info-ico" aria-hidden="true" style="width: 30px; height: 30px; border-radius: 12px;">
                                                                <svg viewBox="0 0 24 24" fill="none">
                                                                    <path d="M4 19V6a2 2 0 0 1 2-2h10l4 4v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                                                    <path d="M14 4v4h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                                    <path d="M7 12h10M7 16h7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                                </svg>
                                                            </div>
                                                            <div>
                                                                <div style="font-size: 12px; color: var(--muted); font-weight: 650;">Curso</div>
                                                                <div class="mono" style="margin-top: 3px; font-size: 13px; overflow-wrap:anywhere;">{{ $curso ?? 'não encontrado' }}</div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                                        <div style="display:flex; gap: 10px; align-items:flex-start; border: 1px solid var(--border); border-radius: 14px; padding: 10px 12px; background: color-mix(in srgb, var(--bg0) 55%, transparent);">
                                                            <div class="info-ico" aria-hidden="true" style="width: 30px; height: 30px; border-radius: 12px;">
                                                                <svg viewBox="0 0 24 24" fill="none">
                                                                    <path d="M4 4h16v16H4z" stroke="currentColor" stroke-width="2"/>
                                                                    <path d="M7 8h10M7 12h10M7 16h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                                </svg>
                                                            </div>
                                                            <div>
                                                                <div style="font-size: 12px; color: var(--muted); font-weight: 650;">Turma</div>
                                                                <div class="mono" style="margin-top: 3px; font-size: 14px;">{{ $turma ?? 'não encontrado' }}</div>
                                                            </div>
                                                        </div>

                                                        <div style="display:flex; gap: 10px; align-items:flex-start; border: 1px solid var(--border); border-radius: 14px; padding: 10px 12px; background: color-mix(in srgb, var(--bg0) 55%, transparent);">
                                                            <div class="info-ico" aria-hidden="true" style="width: 30px; height: 30px; border-radius: 12px;">
                                                                <svg viewBox="0 0 24 24" fill="none">
                                                                    <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Z" stroke="currentColor" stroke-width="2"/>
                                                                    <path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                                </svg>
                                                            </div>
                                                            <div>
                                                                <div style="font-size: 12px; color: var(--muted); font-weight: 650;">Turno</div>
                                                                <div class="mono" style="margin-top: 3px; font-size: 14px;">{{ $turno ?? 'não encontrado' }}</div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div style="display:flex; gap: 10px; align-items:flex-start; border: 1px solid var(--border); border-radius: 14px; padding: 10px 12px; background: color-mix(in srgb, var(--bg0) 55%, transparent);">
                                                        <div class="info-ico" aria-hidden="true" style="width: 30px; height: 30px; border-radius: 12px;">
                                                            <svg viewBox="0 0 24 24" fill="none">
                                                                <path d="M7 3v3M17 3v3M4 8h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                                <path d="M5 6h14a2 2 0 0 1 2 2v13H3V8a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                                                <path d="M7 12h5M7 16h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                            </svg>
                                                        </div>
                                                        <div>
                                                            <div style="font-size: 12px; color: var(--muted); font-weight: 650;">Data da matrícula</div>
                                                            <div class="mono" style="margin-top: 3px; font-size: 14px;">{{ $dataMatriculaBr ?? $dataMatricula ?? 'não encontrado' }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="span-2" style="text-align:center; color: var(--muted); font-size: 13px; margin-top: 2px;">
                                        <span class="{{ $chipClass }}">
                                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            @if (!empty($ieducar_status_error))
                                                Situação: não encontrado
                                            @else
                                                {{ $situacao ? $situacao : 'situação não informada' }}
                                            @endif
                                        </span>
                                        <span class="chip {{ !empty($ieducar_status_error) ? 'chip--bad' : '' }}">
                                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M7 3v3M17 3v3M4 8h16M6 11h4M6 15h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                <path d="M5 6h14a2 2 0 0 1 2 2v13H3V8a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                            </svg>
                                            @if (!empty($ieducar_status_error))
                                                Ano letivo: não encontrado
                                            @else
                                                {{ $ano ? "Ano letivo: {$ano}" : 'ano letivo não informado' }}
                                            @endif
                                        </span>
                                        @if (is_bool($ativo))
                                            <span class="chip {{ $ativo ? 'chip--good' : 'chip--bad' }}">
                                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                    <path d="M12 2v20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                    <path d="M5 7h14M5 17h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                </svg>
                                                Cadastro {{ $ativo ? 'ativo' : 'inativo' }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                

                                <div style="margin-top: 14px;">
                                    <label class="bridge-label">Responsáveis e telefones</label>
                                    @if (count($responsaveisRows) === 0)
                                        <div class="bridge-muted" style="margin-top: 6px;">(não informado)</div>
                                    @else
                                        <div style="margin-top: 10px; display: grid; gap: 10px;">
                                            @foreach ($responsaveisRows as $rr)
                                                <div style="border: 1px solid var(--border); border-radius: 14px; padding: 12px; background: color-mix(in srgb, var(--bg0) 55%, transparent);">
                                                    <div style="display:flex; align-items:center; justify-content: space-between; gap: 10px;">
                                                        <div style="font-weight: 750;">{{ $rr['nome'] ?? 'Responsável' }}</div>
                                                        @if (!empty($rr['vinculo']))
                                                            <span class="chip">{{ $rr['vinculo'] }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="bridge-muted" style="margin-top: 8px;">
                                                        @if (count($rr['telefones'] ?? []) > 0)
                                                            <div style="font-weight: 600; margin-bottom: 4px;">Telefones</div>
                                                            <div style="display: grid; gap: 4px;">
                                                                @foreach ($rr['telefones'] as $telFmt)
                                                                    <div class="mono">{{ $telFmt }}</div>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            Telefones: <span class="mono">não informado</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </section>

                            @if (session('status'))
                                <p class="bridge-muted" style="margin-top: 10px;">
                                    <strong>{{ session('status') }}</strong>
                                </p>
                            @endif

                            @if ($errors->any())
                                <p class="bridge-muted" style="margin-top: 10px;">
                                    <strong>Não foi possível enviar.</strong>
                                </p>
                            @endif

                            <form method="POST" action="{{ route('facial.send.store') }}" class="bridge-form" id="facial-form">
                                @csrf
                                <input type="hidden" name="request_token" value="{{ $request_token ?? '' }}" />
                                <input type="hidden" name="external_id" value="{{ old('external_id', $external_id ?? '') }}" />
                                <input type="hidden" name="aluno_id" value="{{ old('aluno_id', $aluno_id ?? '') }}" />
                                <input type="hidden" name="idpes" value="{{ old('idpes', $idpes ?? '') }}" />
                                <input type="hidden" name="matricula_id" value="{{ old('matricula_id', $matricula_id ?? '') }}" />

                                <div class="bridge-field">
                                    <label class="bridge-label">Foto na hora (câmera)</label>
                                    <div class="bridge-muted" style="margin-top: 6px;">
                                        Se aparecer uma pergunta de permissão, escolha <strong>Permitir</strong>.
                                    </div>

                                    <div style="margin-top: 10px; display: grid; gap: 10px;">
                                        <video id="camVideo" autoplay playsinline muted style="width: 100%; max-width: 520px; border-radius: 12px; border: 1px solid var(--border); background: var(--surface-2);"></video>
                                        <canvas id="camCanvas" style="display:none;"></canvas>
                                        <img id="camPreview" alt="" style="display:none; width: 100%; max-width: 520px; border-radius: 12px; border: 1px solid var(--border);" />
                                    </div>

                                    <div class="bridge-form__actions" style="margin-top: 10px;">
                                        <button type="button" class="bridge-btn" id="btnStartCam">Ativar câmera</button>
                                        <button type="button" class="bridge-btn" id="btnCapture" disabled>Capturar foto</button>
                                        <button type="button" class="bridge-btn" id="btnRetake" style="display:none;">Refazer</button>
                                    </div>

                                    <div class="bridge-muted" id="camStatus" style="margin-top: 10px;"></div>
                                </div>

                                <div class="bridge-form__actions">
                                    <button type="submit" class="bridge-btn bridge-btn--primary">Enviar foto</button>
                                    <a class="bridge-btn" href="/dashboard">Voltar</a>
                                </div>
                            </form>

                            <script>
                                (function () {
                                    const form = document.getElementById('facial-form');
                                    const video = document.getElementById('camVideo');
                                    const canvas = document.getElementById('camCanvas');
                                    const preview = document.getElementById('camPreview');
                                    const btnStart = document.getElementById('btnStartCam');
                                    const btnCapture = document.getElementById('btnCapture');
                                    const btnRetake = document.getElementById('btnRetake');
                                    const status = document.getElementById('camStatus');
                                    let stream = null;
                                    let photoBlob = null;

                                    function setStatus(text) {
                                        status.textContent = text || '';
                                    }

                                    async function startCamera() {
                                        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                                            setStatus('Este navegador não permite usar a câmera. Tente em outro navegador/dispositivo.');
                                            return;
                                        }

                                        try {
                                            stream = await navigator.mediaDevices.getUserMedia({
                                                video: { facingMode: 'user' },
                                                audio: false,
                                            });
                                            video.srcObject = stream;
                                            await video.play();
                                            btnCapture.disabled = false;
                                            setStatus('Câmera ativa. Enquadre o rosto e toque em “Capturar foto”.');
                                        } catch (e) {
                                            setStatus('Não foi possível acessar a câmera. Verifique se a permissão foi concedida.');
                                        }
                                    }

                                    function stopCamera() {
                                        if (stream) {
                                            for (const t of stream.getTracks()) t.stop();
                                        }
                                        stream = null;
                                        video.srcObject = null;
                                    }

                                    async function capture() {
                                        if (!stream) return;
                                        const w = video.videoWidth || 640;
                                        const h = video.videoHeight || 480;
                                        canvas.width = w;
                                        canvas.height = h;
                                        const ctx = canvas.getContext('2d');
                                        ctx.drawImage(video, 0, 0, w, h);

                                        photoBlob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.9));
                                        if (!photoBlob) {
                                            setStatus('Não foi possível capturar a foto. Tente novamente.');
                                            return;
                                        }

                                        preview.src = URL.createObjectURL(photoBlob);
                                        preview.style.display = 'block';
                                        video.style.display = 'none';
                                        btnRetake.style.display = 'inline-flex';
                                        btnCapture.disabled = true;
                                        setStatus('Foto capturada. Confira e toque em “Enviar foto”.');
                                        stopCamera();
                                    }

                                    function retake() {
                                        if (preview.src) URL.revokeObjectURL(preview.src);
                                        preview.src = '';
                                        preview.style.display = 'none';
                                        video.style.display = 'block';
                                        btnRetake.style.display = 'none';
                                        photoBlob = null;
                                        btnCapture.disabled = true;
                                        setStatus('');
                                    }

                                    btnStart.addEventListener('click', async () => {
                                        retake();
                                        await startCamera();
                                    });
                                    btnCapture.addEventListener('click', capture);
                                    btnRetake.addEventListener('click', async () => {
                                        retake();
                                        await startCamera();
                                    });

                                    form.addEventListener('submit', async (ev) => {
                                        if (!photoBlob) {
                                            setStatus('Antes de enviar, tire a foto.');
                                            ev.preventDefault();
                                            return;
                                        }

                                        // Envia sem upload de arquivo do dispositivo (blob capturado em memória).
                                        ev.preventDefault();
                                        const fd = new FormData(form);
                                        fd.append('photo', photoBlob, 'capture.jpg');

                                        setStatus('Enviando foto...');
                                        try {
                                            const resp = await fetch(form.action, {
                                                method: 'POST',
                                                body: fd,
                                                headers: {
                                                    'X-Requested-With': 'XMLHttpRequest',
                                                    'Accept': 'application/json',
                                                },
                                            });

                                            if (resp.redirected) {
                                                window.location.href = resp.url;
                                                return;
                                            }

                                            if (!resp.ok) {
                                                let detail = '';
                                                try {
                                                    const j = await resp.json();
                                                    if (j && (j.message || j.error)) {
                                                        detail = (j.message || j.error);
                                                    } else if (j && j.errors) {
                                                        detail = JSON.stringify(j.errors);
                                                    }
                                                } catch (_) {
                                                    try { detail = (await resp.text()).slice(0, 300); } catch (_) {}
                                                }
                                                setStatus(detail ? detail : ('Não foi possível enviar agora (HTTP ' + resp.status + ').'));
                                                return;
                                            }

                                            // Se o backend responder JSON, exibe mensagem básica.
                                            let json = null;
                                            try { json = await resp.json(); } catch (_) {}
                                            setStatus((json && json.message) ? json.message : 'Enviado com sucesso.');
                                        } catch (_) {
                                            setStatus('Falha no envio. Verifique sua conexão e tente novamente.');
                                        }
                                    });

                                    window.addEventListener('beforeunload', () => stopCamera());
                                })();
                            </script>
                        </div>
                    </div>
                </div>
            </main>

            <footer class="bridge-footer">
                <div class="bridge-container">
                    <div class="bridge-footer__inner">
                        <div>© {{ now()->year }} {{ config('app.name', 'Bridge ERP') }}</div>
                        <div class="bridge-footer__right">
                            <a href="https://github.com/jadergabriel" target="_blank" rel="noreferrer">Powered by Jader Gabriel</a>
                            <span class="bridge-sep">•</span>
                            <span>Laravel v{{ app()->version() }}</span>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>

