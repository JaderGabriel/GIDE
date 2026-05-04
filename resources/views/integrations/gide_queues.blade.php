<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Filas GIDE • Integrações • {{ config('app.name', 'Bridge ERP') }}</title>

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

        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="stylesheet" href="/home.css">
        @include('partials.integr-visual-kit')
        <script defer src="/home.js"></script>
        <style>
            .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 12px; }
            .gql-filters {
                margin-top: 14px;
                padding: 14px 16px;
                border-radius: 18px;
                border: 1px solid var(--border);
                background: color-mix(in srgb, var(--card-strong) 94%, var(--bg0));
                box-shadow: var(--shadow-soft);
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
                align-items: flex-end;
            }
            .gql-filters > div:not(.gql-filters__actions) { flex: 1 1 160px; min-width: 140px; max-width: 100%; }
            .gql-filters label { display: block; font-size: 11px; font-weight: 750; letter-spacing: 0.05em; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; }
            .gql-filters input[type="text"],
            .gql-filters select {
                width: 100%;
                border-radius: 12px;
                border: 1px solid var(--border);
                background: var(--surface-2);
                color: var(--text);
                padding: 9px 11px;
                font-size: 13px;
            }
            .gql-filters__actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; flex: 1 1 100%; }
            .gql-table-wrap {
                margin-top: 14px;
                overflow: auto;
                max-height: min(70vh, 720px);
                border-radius: 18px;
                border: 1px solid var(--border);
                background: var(--card-strong);
                box-shadow: var(--shadow-soft);
            }
            .gql-table { width: 100%; border-collapse: collapse; font-size: 12px; }
            .gql-table th, .gql-table td { padding: 9px 11px; border-bottom: 1px solid var(--border); text-align: left; vertical-align: top; }
            .gql-table th {
                position: sticky;
                top: 0;
                background: var(--surface-2);
                z-index: 4;
                color: var(--muted);
                font-weight: 650;
                box-shadow: var(--sticky-table-head-shadow, 0 10px 28px -8px rgba(2, 6, 23, 0.28));
            }
            .gql-table tr:last-child td { border-bottom: none; }
            .st-ok { color: color-mix(in srgb, var(--accent-c) 70%, var(--text)); font-weight: 650; }
            .st-bad { color: #ef4444; font-weight: 650; }
            .st-warn { color: #f59e0b; font-weight: 650; }
            .gql-actions-top { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-top: 12px; }
        </style>
    </head>
    <body>
        <div class="bridge-shell integr-app">
            <header class="bridge-header">
                <div class="bridge-container">
                    <div class="bridge-header__inner">
                        <a class="bridge-brand" href="{{ url('/dashboard') }}">
                            <img src="/favicon.svg" alt="" class="bridge-brand__logo" />
                            <div class="bridge-brand__text">
                                <div class="bridge-brand__name">{{ config('app.name', 'Bridge ERP') }}</div>
                                <div class="bridge-brand__tagline">Filas GIDE • consolidado</div>
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
                            <div class="bridge-panel__head">
                                <div class="bridge-panel__title">Filas e tarefas</div>
                                <div class="bridge-panel__meta">jobs Laravel, falhas, outbound, SMS, eventos de acesso e frequência-registro</div>
                            </div>

                            @if ($integrationsOverviewAdmin ?? false)
                                <x-audit-toolbar style="margin-top: 12px;" />
                            @endif

                            <p class="bridge-muted" style="margin-top: 12px; line-height: 1.55;">
                                Consolidação das últimas linhas por origem (até {{ (int) ($filters['limite'] ?? 150) }} após filtros).
                                Horários em <strong>{{ \App\Support\DateDisplay::timezoneLabel() }}</strong>.
                                Driver de fila: <span class="mono">{{ $queueDriver }}</span>.
                            </p>

                            <div class="gql-actions-top">
                                <a class="bridge-btn" href="{{ url('/dashboard') }}">← Dashboard</a>
                                <a class="bridge-btn bridge-btn--ghost" href="{{ route('integrations.overview') }}">Visão geral das integrações</a>
                            </div>

                            @php
                                $f = is_array($filters ?? null) ? $filters : [];
                                $tipo = (string) ($f['tipo'] ?? 'todos');
                                $estado = (string) ($f['estado'] ?? 'todos');
                                $qVal = (string) ($f['q'] ?? '');
                                $lim = (int) ($f['limite'] ?? 150);
                            @endphp

                            <form class="gql-filters" method="get" action="{{ route('integrations.gide-queues') }}" aria-label="Filtros da lista">
                                <div>
                                    <label for="gql-tipo">Origem</label>
                                    <select id="gql-tipo" name="tipo">
                                        <option value="todos" @selected($tipo === 'todos')>Todas as origens</option>
                                        <option value="jobs" @selected($tipo === 'jobs')>Jobs (Laravel)</option>
                                        <option value="failed" @selected($tipo === 'failed')>Falhas (failed_jobs)</option>
                                        <option value="outbound" @selected($tipo === 'outbound')>Outbound (Gestor)</option>
                                        <option value="sms" @selected($tipo === 'sms')>SMS</option>
                                        <option value="eventos" @selected($tipo === 'eventos')>Eventos (access)</option>
                                        <option value="frequencia" @selected($tipo === 'frequencia')>Frequência (registro)</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="gql-estado">Estado resumido</label>
                                    <select id="gql-estado" name="estado">
                                        <option value="todos" @selected($estado === 'todos')>Todos</option>
                                        <option value="pendente" @selected($estado === 'pendente')>Pendente / em fila</option>
                                        <option value="concluido" @selected($estado === 'concluido')>Concluído</option>
                                        <option value="falha" @selected($estado === 'falha')>Falha</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="gql-q">Busca (texto)</label>
                                    <input id="gql-q" type="text" name="q" value="{{ $qVal }}" placeholder="ID, event_id, estado, resumo…" autocomplete="off" />
                                </div>
                                <div>
                                    <label for="gql-lim">Limite na lista</label>
                                    <select id="gql-lim" name="limite">
                                        @foreach ([50, 100, 150, 200, 300] as $n)
                                            <option value="{{ $n }}" @selected($lim === $n)>{{ $n }} linhas</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="gql-filters__actions">
                                    <button type="submit" class="bridge-btn">Aplicar filtros</button>
                                    <a class="bridge-btn bridge-btn--ghost" href="{{ route('integrations.gide-queues') }}">Limpar</a>
                                </div>
                            </form>

                            <p class="bridge-muted" style="margin-top: 10px; font-size: 12px;">
                                Mostrando <strong>{{ count($rows ?? []) }}</strong> de <strong>{{ (int) ($totalFiltrado ?? 0) }}</strong> após filtros (cortado pelo limite).
                            </p>

                            <div class="gql-table-wrap">
                                <table class="gql-table">
                                    <thead>
                                        <tr>
                                            <th>Quando</th>
                                            <th>Origem</th>
                                            <th>ID</th>
                                            <th>Ref.</th>
                                            <th>Estado</th>
                                            <th>Resumo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($rows ?? [] as $r)
                                            @php
                                                $bucket = (string) ($r['estado_bucket'] ?? '');
                                                $stCls = $bucket === 'concluido' ? 'st-ok' : ($bucket === 'falha' ? 'st-bad' : 'st-warn');
                                                $url = $r['url'] ?? null;
                                            @endphp
                                            <tr>
                                                <td style="font-size:11px;line-height:1.35;white-space:nowrap;">{{ $r['when_display'] ?? '—' }}</td>
                                                <td>{{ $r['tipo_label'] ?? '—' }}</td>
                                                <td class="mono">
                                                    @if ($url)
                                                        <a href="{{ $url }}">{{ $r['id'] ?? '—' }}</a>
                                                    @else
                                                        {{ $r['id'] ?? '—' }}
                                                    @endif
                                                </td>
                                                <td class="mono" style="max-width:140px;word-break:break-all;">{{ $r['ref'] ?? '—' }}</td>
                                                <td class="{{ $stCls }}">{{ $r['status'] ?? '—' }}</td>
                                                <td class="mono" style="max-width:280px;word-break:break-word;">{{ $r['detail'] ?? '—' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="bridge-muted">Nenhuma linha com os filtros actuais.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if ($integrationsOverviewAdmin ?? false)
                                <div class="bridge-form__actions" style="margin-top: 14px;">
                                    <a class="bridge-btn" href="{{ url('/dashboard') }}">Voltar ao dashboard</a>
                                </div>
                            @endif
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
