<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Admin • Facial #{{ $item->id }} • {{ config('app.name', 'Bridge ERP') }}</title>

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
            .bridge-container { max-width: 1400px; }
            .bridge-auth { max-width: none; }
            .bridge-panel { width: 100%; }

            .fac-admin {
                --fac-ok: #059669;
                --fac-ok-bg: color-mix(in srgb, #059669 14%, transparent);
                --fac-bad: #dc2626;
                --fac-bad-bg: color-mix(in srgb, #dc2626 12%, transparent);
                --fac-warn: #d97706;
                --fac-warn-bg: color-mix(in srgb, #d97706 14%, transparent);
                --fac-info: #0284c7;
                --fac-info-bg: color-mix(in srgb, #0284c7 12%, transparent);
            }
            .fac-show__bar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 14px; margin-top: 8px; }
            .fac-show__id { display: flex; align-items: center; gap: 12px; }
            .fac-show__id-ico { width: 44px; height: 44px; border-radius: 14px; display: grid; place-items: center; border: 1px solid var(--border); background: linear-gradient(145deg, color-mix(in srgb, var(--accent-c) 20%, var(--surface-1)), var(--surface-1)); color: var(--accent-c); }
            .fac-show__id-ico svg { width: 22px; height: 22px; }
            .fac-show__title { font-weight: 850; font-size: 1.25rem; margin: 0; letter-spacing: -0.02em; }
            .fac-show__sub { margin: 4px 0 0; font-size: 13px; color: var(--muted); }
            .fac-actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
            .fac-btn { appearance: none; display: inline-flex; align-items: center; gap: 8px; padding: 0 14px; height: 40px; border-radius: 12px; border: 1px solid var(--border); background: var(--surface-1); color: var(--text); font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; transition: background .12s ease, border-color .12s ease; font-family: inherit; }
            .fac-btn:hover { background: color-mix(in srgb, var(--bg0) 82%, transparent); border-color: color-mix(in srgb, var(--accent-a) 28%, var(--border)); text-decoration: none; }
            .fac-btn svg { width: 18px; height: 18px; flex-shrink: 0; }
            .fac-btn--primary { border-color: color-mix(in srgb, var(--accent-a) 35%, var(--border)); background: color-mix(in srgb, var(--accent-a) 10%, var(--surface-1)); color: color-mix(in srgb, var(--text) 80%, var(--accent-a)); }

            .fac-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 650; border: 1px solid var(--border); line-height: 1.2; }
            .fac-badge svg { width: 12px; height: 12px; }
            .fac-badge--neutral { background: color-mix(in srgb, var(--muted) 8%, transparent); color: var(--muted); }
            .fac-badge--success { border-color: color-mix(in srgb, var(--fac-ok) 42%, var(--border)); background: var(--fac-ok-bg); color: color-mix(in srgb, var(--text) 82%, var(--fac-ok)); }
            .fac-badge--danger { border-color: color-mix(in srgb, var(--fac-bad) 45%, var(--border)); background: var(--fac-bad-bg); color: var(--fac-bad); }
            .fac-badge--warn { border-color: color-mix(in srgb, var(--fac-warn) 40%, var(--border)); background: var(--fac-warn-bg); color: color-mix(in srgb, var(--text) 80%, var(--fac-warn)); }
            .fac-badge--info { border-color: color-mix(in srgb, var(--fac-info) 40%, var(--border)); background: var(--fac-info-bg); color: color-mix(in srgb, var(--text) 82%, var(--fac-info)); }
            .fac-badge-row { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }

            .fac-grid { margin-top: 18px; display: grid; gap: 14px; grid-template-columns: 1fr; }
            @media (min-width: 900px) { .fac-grid { grid-template-columns: 1fr 1fr; } }
            .fac-card { border: 1px solid var(--border); border-radius: 18px; padding: 16px 16px 14px; background: var(--card-strong); box-shadow: var(--shadow-soft); }
            .fac-card__head { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid var(--border); }
            .fac-card__head svg { width: 20px; height: 20px; color: var(--accent-a); flex-shrink: 0; }
            .fac-card__title { font-weight: 800; font-size: 14px; margin: 0; }
            .fac-card__hint { font-size: 12px; color: var(--muted); margin: 4px 0 0; }

            .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 12px; }
            .fac-kv { display: grid; gap: 6px; }
            .fac-kv span { color: var(--muted); font-size: 12px; }
            .fac-kv strong { color: var(--text); font-weight: 650; }

            .fac-json { margin-top: 10px; padding: 12px; border-radius: 14px; border: 1px solid var(--border); background: color-mix(in srgb, var(--bg0) 72%, transparent); white-space: pre-wrap; word-break: break-word; max-height: 320px; overflow: auto; }

            .fac-timeline { margin-top: 4px; display: flex; flex-direction: column; gap: 0; }
            .fac-tl-item { position: relative; padding: 12px 0 12px 20px; border-left: 3px solid var(--border); margin-left: 8px; }
            .fac-tl-item:last-child { border-left-color: transparent; }
            .fac-tl-item::before { content: ""; position: absolute; left: -7px; top: 16px; width: 11px; height: 11px; border-radius: 999px; background: var(--surface-1); border: 2px solid var(--border); }
            .fac-tl-item--ok { border-left-color: color-mix(in srgb, var(--fac-ok) 55%, var(--border)); }
            .fac-tl-item--ok::before { border-color: var(--fac-ok); background: var(--fac-ok-bg); }
            .fac-tl-item--bad { border-left-color: color-mix(in srgb, var(--fac-bad) 55%, var(--border)); }
            .fac-tl-item--bad::before { border-color: var(--fac-bad); background: var(--fac-bad-bg); }
            .fac-tl-item--info { border-left-color: color-mix(in srgb, var(--fac-info) 50%, var(--border)); }
            .fac-tl-item--info::before { border-color: var(--fac-info); background: var(--fac-info-bg); }

            details.fac-details summary { cursor: pointer; font-size: 12px; font-weight: 650; color: var(--accent-a); margin-top: 8px; user-select: none; }
            details.fac-details pre { margin: 8px 0 0; padding: 10px; border-radius: 12px; border: 1px solid var(--border); background: color-mix(in srgb, var(--bg0) 70%, transparent); white-space: pre-wrap; word-break: break-word; max-height: 240px; overflow: auto; }
        </style>
    </head>
    <body>
        <div class="bridge-shell fac-admin">
            <header class="bridge-header">
                <div class="bridge-container">
                    <div class="bridge-header__inner">
                        <a class="bridge-brand" href="{{ url('/dashboard') }}">
                            <img src="/favicon.svg" alt="" class="bridge-brand__logo" />
                            <div class="bridge-brand__text">
                                <div class="bridge-brand__name">{{ config('app.name', 'Bridge ERP') }}</div>
                                <div class="bridge-brand__tagline">Admin • Detalhe facial</div>
                            </div>
                        </a>
                        @include('partials.bridge-user-menu')
                    </div>
                </div>
            </header>

            <main class="bridge-main">
                <div class="bridge-container">
                    <div class="bridge-auth">
                        <div class="bridge-panel">
                            @php
                                $payload = is_array($item->payload) ? $item->payload : [];
                                $expired = $item->expires_at && $item->expires_at->isPast();
                            @endphp

                            <div class="fac-show__bar">
                                <div class="fac-show__id">
                                    <div class="fac-show__id-ico" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a4 4 0 0 1 4 4v2a4 4 0 0 1-8 0V6a4 4 0 0 1 4-4z"/><path d="M6 22v-2a6 6 0 0 1 12 0v2"/></svg>
                                    </div>
                                    <div>
                                        <h1 class="fac-show__title">Solicitação #{{ $item->id }}</h1>
                                        <p class="fac-show__sub mono clip" style="max-width: min(560px, 92vw);" title="{{ $item->event_id }}">{{ $item->event_id }}</p>
                                        <div class="fac-badge-row">
                                            @if ($item->used_at)
                                                <span class="fac-badge fac-badge--success"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Token usado</span>
                                            @else
                                                <span class="fac-badge fac-badge--neutral">Token não usado</span>
                                            @endif
                                            @if ($expired)
                                                <span class="fac-badge fac-badge--warn">Expirado</span>
                                            @else
                                                <span class="fac-badge fac-badge--info">Prazo válido</span>
                                            @endif
                                            <span class="fac-badge fac-badge--neutral">{{ $attempts->count() }} tent. catraca</span>
                                            <span class="fac-badge fac-badge--neutral">{{ $snapshots->count() }} snapshots</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="fac-actions">
                                    <a class="fac-btn" href="{{ route('admin.facial-requests.index') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                                        Lista
                                    </a>
                                    @if ($showGestorInviteVerify ?? false)
                                        <a class="fac-btn fac-btn--primary" href="{{ route('admin.facial-requests.gestor-invite', ['id' => $item->id]) }}" target="_blank" rel="noreferrer">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M11 8v6M8 11h6"/></svg>
                                            Verificar Invite (Gestor)
                                        </a>
                                    @elseif (! $item->used_at && ! $expired)
                                        <a class="fac-btn" href="{{ url('/facial/enviar?token='.urlencode($item->token)) }}" target="_blank" rel="noreferrer">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                            Abrir envio
                                        </a>
                                    @endif
                                    <form method="POST" action="{{ route('admin.facial-requests.refresh-status', ['id' => $item->id]) }}" style="margin:0;">
                                        @csrf
                                        <button type="submit" class="fac-btn fac-btn--primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                                            Atualizar iEducar
                                        </button>
                                    </form>
                                </div>
                            </div>

                            @if (session('status'))
                                <p class="bridge-muted" style="margin-top: 14px;"><strong>{{ session('status') }}</strong></p>
                            @endif

                            <div class="fac-grid">
                                <div class="fac-card">
                                    <div class="fac-card__head">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                        <div>
                                            <h2 class="fac-card__title">Token e prazos</h2>
                                            <p class="fac-card__hint">Valor sensível — não compartilhar.</p>
                                        </div>
                                    </div>
                                    <div class="fac-kv mono">
                                        <div><span>token</span><br><strong class="wrap">{{ $item->token }}</strong></div>
                                        <div><span>expira</span><br><strong>{{ $item->expires_at ? \App\Support\DateDisplay::formatHuman($item->expires_at, true) : '(?)' }}</strong></div>
                                        <div><span>usado em</span><br><strong>{{ $item->used_at ? \App\Support\DateDisplay::formatHuman($item->used_at, true) : '—' }}</strong></div>
                                    </div>
                                </div>

                                <div class="fac-card">
                                    <div class="fac-card__head">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        <div>
                                            <h2 class="fac-card__title">Aluno (payload)</h2>
                                            <p class="fac-card__hint">Identificadores recebidos do fluxo iEducar.</p>
                                        </div>
                                    </div>
                                    <div class="fac-kv mono">
                                        <div><span>cod_aluno</span><br><strong>{{ data_get($payload, 'aluno_id') ?? '—' }}</strong></div>
                                        <div><span>idpes</span><br><strong>{{ data_get($payload, 'idpes') ?? '—' }}</strong></div>
                                        <div><span>matricula_id</span><br><strong>{{ data_get($payload, 'matricula_id') ?? '—' }}</strong></div>
                                        <div><span>external_id</span><br><strong class="wrap">{{ data_get($payload, 'external_id') ?? '—' }}</strong></div>
                                    </div>
                                </div>
                            </div>

                            <div class="fac-card" style="margin-top: 14px;">
                                <div class="fac-card__head">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                                    <div>
                                        <h2 class="fac-card__title">Payload completo</h2>
                                        <p class="fac-card__hint">JSON armazenado na solicitação.</p>
                                    </div>
                                </div>
                                <pre class="mono fac-json">{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>

                            @if (($gestorHistories ?? collect())->isNotEmpty())
                                <div class="fac-card" style="margin-top: 14px;">
                                    <div class="fac-card__head">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                        <div>
                                            <h2 class="fac-card__title">Histórico Gestor (solicitação + catraca)</h2>
                                            <p class="fac-card__hint">Uma linha por pedido de link ou resposta do POST de facial no SDK.</p>
                                        </div>
                                    </div>
                                    <div class="fac-timeline">
                                        @foreach ($gestorHistories as $gh)
                                            @php
                                                $isSol = $gh->event_type === \App\Models\FacialGestorCatracaHistory::EVENT_SOLICITACAO;
                                                $tl = $isSol ? 'fac-tl-item--info' : ($gh->ok ? 'fac-tl-item--ok' : 'fac-tl-item--bad');
                                            @endphp
                                            <div class="fac-tl-item {{ $tl }}">
                                                <div class="fac-badge-row" style="margin-top:0;">
                                                    @if ($isSol)
                                                        <span class="fac-badge fac-badge--info">Solicitação facial</span>
                                                    @elseif ($gh->ok)
                                                        <span class="fac-badge fac-badge--success">Enroll catraca OK</span>
                                                    @else
                                                        <span class="fac-badge fac-badge--danger">Enroll catraca</span>
                                                    @endif
                                                    @if (! $isSol)
                                                        <span class="fac-badge fac-badge--neutral">HTTP {{ $gh->http_status ?? '—' }}</span>
                                                    @endif
                                                    @if ($gh->invite_id)
                                                        <span class="fac-badge fac-badge--neutral">invite {{ $gh->invite_id }}</span>
                                                    @endif
                                                    @if ($gh->guest_id)
                                                        <span class="fac-badge fac-badge--neutral">guest {{ $gh->guest_id }}</span>
                                                    @endif
                                                    <span class="fac-badge fac-badge--neutral">{{ $gh->created_at ? \App\Support\DateDisplay::formatHuman($gh->created_at, true) : '' }}</span>
                                                </div>
                                                @if ($gh->effective_url)
                                                    <div class="mono fac-muted" style="margin-top:6px;word-break:break-all;">{{ $gh->effective_url }}</div>
                                                @endif
                                                @if ($gh->response_body)
                                                    <details class="fac-details">
                                                        <summary>Resposta armazenada</summary>
                                                        <pre class="mono">{{ mb_substr($gh->response_body, 0, 16000) }}</pre>
                                                    </details>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="fac-grid" style="margin-top: 14px;">
                                <div class="fac-card">
                                    <div class="fac-card__head">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                        <div>
                                            <h2 class="fac-card__title">Tentativas — Gestor / catraca</h2>
                                            <p class="fac-card__hint">Ordem decrescente por ID.</p>
                                        </div>
                                    </div>
                                    @if ($attempts->isEmpty())
                                        <p class="bridge-muted" style="margin:0;">Nenhuma tentativa registrada.</p>
                                    @endif
                                    <div class="fac-timeline">
                                        @foreach ($attempts as $a)
                                            <div class="fac-tl-item {{ $a->ok ? 'fac-tl-item--ok' : 'fac-tl-item--bad' }}">
                                                <div class="fac-badge-row" style="margin-top:0;">
                                                    @if ($a->ok)
                                                        <span class="fac-badge fac-badge--success">OK</span>
                                                    @else
                                                        <span class="fac-badge fac-badge--danger">Falha</span>
                                                    @endif
                                                    <span class="fac-badge fac-badge--neutral">HTTP {{ $a->http_status ?? '—' }}</span>
                                                    <span class="fac-badge fac-badge--neutral">{{ $a->created_at ? \App\Support\DateDisplay::formatHuman($a->created_at, true) : '—' }}</span>
                                                </div>
                                                @if ($a->error_message)
                                                    <div class="mono" style="margin-top:8px;color:color-mix(in srgb,var(--fac-bad) 88%,var(--text));">{{ $a->error_message }}</div>
                                                @endif
                                                @if ($a->response_body)
                                                    <details class="fac-details">
                                                        <summary>Resposta bruta</summary>
                                                        <pre class="mono">{{ $a->response_body }}</pre>
                                                    </details>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="fac-card">
                                    <div class="fac-card__head">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="20" height="20"><rect width="32" height="32" rx="7" fill="#1b6b3a"/><path fill="#fff" d="M8 9h2.4v14H8V9zm4.8 0h3.8c2.9 0 4.7 1.6 4.7 4.3 0 2.6-1.7 4.2-4.7 4.2h-1.4V23h-2.4V9zm2.4 2v4.8h1.1c1.6 0 2.5-.7 2.5-2.4 0-1.6-.9-2.4-2.5-2.4h-1.1z"/></svg>
                                        <div>
                                            <h2 class="fac-card__title">Snapshots — iEducar</h2>
                                            <p class="fac-card__hint">Consultas de matrícula / status.</p>
                                        </div>
                                    </div>
                                    @if ($snapshots->isEmpty())
                                        <p class="bridge-muted" style="margin:0;">Nenhum snapshot.</p>
                                    @endif
                                    <div class="fac-timeline">
                                        @foreach ($snapshots as $s)
                                            @php
                                                $situacao = is_array($s->response_json) ? data_get($s->response_json, 'status.matricula.situacao_descricao') : null;
                                                $ano = is_array($s->response_json) ? data_get($s->response_json, 'status.matricula.ano') : null;
                                                $httpOk = is_numeric($s->http_status) && (int) $s->http_status >= 200 && (int) $s->http_status < 300;
                                                $tlClass = $s->error_message ? 'fac-tl-item--bad' : ($httpOk ? 'fac-tl-item--ok' : 'fac-tl-item--info');
                                            @endphp
                                            <div class="fac-tl-item {{ $tlClass }}">
                                                <div class="fac-badge-row" style="margin-top:0;">
                                                    @if ($s->error_message)
                                                        <span class="fac-badge fac-badge--danger">Erro</span>
                                                    @elseif ($httpOk)
                                                        <span class="fac-badge fac-badge--success">HTTP {{ $s->http_status }}</span>
                                                    @else
                                                        <span class="fac-badge fac-badge--warn">HTTP {{ $s->http_status ?? '—' }}</span>
                                                    @endif
                                                    <span class="fac-badge fac-badge--neutral">{{ $s->fetched_at ? \App\Support\DateDisplay::formatHuman($s->fetched_at, true) : '' }}</span>
                                                </div>
                                                @if ($situacao || $ano)
                                                    <div class="mono" style="margin-top:8px;font-size:12px;color:var(--muted);">{{ $situacao }}{{ $ano ? ' • ano '.$ano : '' }}</div>
                                                @endif
                                                @if ($s->error_message)
                                                    <div class="mono" style="margin-top:6px;color:color-mix(in srgb,var(--fac-bad) 88%,var(--text));">{{ $s->error_message }}</div>
                                                @endif
                                                @if (is_array($s->response_json))
                                                    <details class="fac-details">
                                                        <summary>JSON</summary>
                                                        <pre class="mono">{{ json_encode($s->response_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                                    </details>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
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
