<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Admin • Frequência #{{ $delivery->id }} • {{ config('app.name', 'Bridge ERP') }}</title>

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
        @include('partials.integr-visual-kit')
        <script defer src="/home.js"></script>
        <style>
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
            .fac-show__id { display: flex; align-items: center; gap: 12px; }
            .fac-show__id-ico { width: 44px; height: 44px; border-radius: 14px; display: grid; place-items: center; border: 1px solid var(--border); background: linear-gradient(145deg, color-mix(in srgb, var(--accent-c) 20%, var(--surface-1)), var(--surface-1)); color: var(--accent-c); }
            .fac-show__id-ico svg { width: 22px; height: 22px; }
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
            .fac-json { margin-top: 10px; padding: 12px; border-radius: 14px; border: 1px solid var(--border); background: color-mix(in srgb, var(--bg0) 72%, transparent); white-space: pre-wrap; word-break: break-word; max-height: 420px; overflow: auto; }
        </style>
    </head>
    <body>
        <div class="bridge-shell fac-admin integr-app">
            <header class="bridge-header">
                <div class="bridge-container">
                    <div class="bridge-header__inner">
                        <a class="bridge-brand" href="{{ url('/dashboard') }}">
                            <img src="/favicon.svg" alt="" class="bridge-brand__logo" />
                            <div class="bridge-brand__text">
                                <div class="bridge-brand__name">{{ config('app.name', 'Bridge ERP') }}</div>
                                <div class="bridge-brand__tagline">Admin • Detalhe frequência</div>
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
                            <div class="integr-page-hero">
                                <div class="integr-page-hero__main">
                                <div class="fac-show__id">
                                    <div class="fac-show__id-ico" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/></svg>
                                    </div>
                                    <div>
                                        <h1 class="integr-section__title">Entrega #{{ $delivery->id }}</h1>
                                        <p class="integr-section__lead">Frequência GIDE → iEducar · tentativas: {{ (int) $delivery->attempts }}</p>
                                        <div class="fac-badge-row">
                                            @if ($delivery->mode === \App\Models\IeducarFrequenciaRegistroDelivery::MODE_PREVIEW)
                                                <span class="fac-badge fac-badge--info">preview</span>
                                            @else
                                                <span class="fac-badge fac-badge--neutral">apply</span>
                                            @endif
                                            @if ($delivery->status === \App\Models\IeducarFrequenciaRegistroDelivery::STATUS_COMPLETED)
                                                <span class="fac-badge fac-badge--success">concluído</span>
                                            @elseif ($delivery->status === \App\Models\IeducarFrequenciaRegistroDelivery::STATUS_FAILED)
                                                <span class="fac-badge fac-badge--danger">falhou</span>
                                            @elseif ($delivery->status === \App\Models\IeducarFrequenciaRegistroDelivery::STATUS_PROCESSING)
                                                <span class="fac-badge fac-badge--info">processando</span>
                                            @else
                                                <span class="fac-badge fac-badge--warn">pendente</span>
                                            @endif
                                            <span class="fac-badge fac-badge--neutral">HTTP {{ $delivery->http_status ?? '—' }}</span>
                                        </div>
                                    </div>
                                </div>
                                </div>
                            </div>
                            <x-audit-toolbar style="margin-top: 12px;">
                                <x-slot:left>
                                    <a class="fac-btn" href="{{ route('admin.ieducar-frequencia-deliveries.index') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                                        Fila
                                    </a>
                                    @if ($delivery->status === \App\Models\IeducarFrequenciaRegistroDelivery::STATUS_PENDING)
                                        <form method="POST" action="{{ route('integrations.ieducar.frequencia-registro.force-send', ['id' => $delivery->id]) }}" style="margin: 0;">
                                            @csrf
                                            <button type="submit" class="fac-btn fac-btn--primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                                                Forçar envio
                                            </button>
                                        </form>
                                    @endif
                                </x-slot:left>
                            </x-audit-toolbar>

                            @if (session('status'))
                                <p class="bridge-muted" style="margin-top: 14px;"><strong>{{ session('status') }}</strong></p>
                            @endif

                            @if (in_array($delivery->mode, [\App\Models\IeducarFrequenciaRegistroDelivery::MODE_PREVIEW, \App\Models\IeducarFrequenciaRegistroDelivery::MODE_APPLY], true)
                                && in_array($delivery->status, [\App\Models\IeducarFrequenciaRegistroDelivery::STATUS_PENDING, \App\Models\IeducarFrequenciaRegistroDelivery::STATUS_PROCESSING], true))
                                <p class="bridge-muted" style="margin-top: 12px;">Aguardando worker da fila. Atualize a página após alguns segundos.</p>
                            @endif

                            @if ($delivery->error_message)
                                <p class="mono" style="margin-top: 12px; padding: 12px; border-radius: 14px; border: 1px solid color-mix(in srgb, var(--fac-bad) 40%, var(--border)); background: color-mix(in srgb, var(--fac-bad) 8%, var(--surface-1)); color: var(--fac-bad);">{{ $delivery->error_message }}</p>
                            @endif

                            <div class="fac-grid">
                                <div class="fac-card">
                                    <div class="fac-card__head">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        <div>
                                            <h2 class="fac-card__title">Metadados</h2>
                                            <p class="fac-card__hint">Auditoria no GIDE.</p>
                                        </div>
                                    </div>
                                    <div class="fac-kv mono">
                                        <div><span>Criado</span><br><strong>{{ $delivery->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}</strong></div>
                                        <div><span>Enviado (worker)</span><br><strong>{{ $delivery->sent_at ? $delivery->sent_at->timezone(config('app.timezone'))->format('d/m/Y H:i:s') : '—' }}</strong></div>
                                    </div>
                                </div>
                                <div class="fac-card">
                                    <div class="fac-card__head">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                        <div>
                                            <h2 class="fac-card__title">Resposta iEducar</h2>
                                            <p class="fac-card__hint">JSON retornado pelo POST de registro.</p>
                                        </div>
                                    </div>
                                    @if (is_array($delivery->response_json))
                                        <pre class="fac-json mono">{{ json_encode($delivery->response_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                    @else
                                        <p class="bridge-muted">(ainda sem resposta)</p>
                                    @endif
                                </div>
                            </div>

                            <div class="fac-card" style="margin-top: 14px;">
                                <div class="fac-card__head">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    <div>
                                        <h2 class="fac-card__title">Payload enviado</h2>
                                        <p class="fac-card__hint">Corpo persistido ao enfileirar (plano B).</p>
                                    </div>
                                </div>
                                <pre class="fac-json mono">{{ json_encode($delivery->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <footer class="bridge-footer">
                <div class="bridge-container">
                    <div class="bridge-footer__inner">
                        <div>© {{ now()->year }} {{ config('app.name', 'Bridge ERP') }}</div>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
