<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin • Auditoria{{ filled($auditUserId ?? null) ? ' — '.($auditUser?->name ?? $auditUser?->username ?? 'ID '.$auditUserId) : '' }} • {{ config('app.name', 'Bridge ERP') }}</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
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
            .fac-admin { --fac-ok: #059669; --fac-ok-bg: color-mix(in srgb, #059669 14%, transparent); --fac-bad: #dc2626; --fac-bad-bg: color-mix(in srgb, #dc2626 12%, transparent); --fac-warn: #d97706; --fac-warn-bg: color-mix(in srgb, #d97706 14%, transparent); --fac-info: #0284c7; --fac-info-bg: color-mix(in srgb, #0284c7 12%, transparent); }
            .fac-admin__hero { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 16px; margin-top: 4px; }
            .fac-admin__title { display: flex; align-items: center; gap: 14px; }
            .fac-admin__title-ico { width: 48px; height: 48px; border-radius: 14px; display: grid; place-items: center; background: linear-gradient(145deg, color-mix(in srgb, var(--accent-a) 22%, var(--surface-1)), var(--surface-1)); border: 1px solid var(--border); color: var(--accent-a); flex-shrink: 0; }
            .fac-admin__title-ico svg { width: 26px; height: 26px; }
            .fac-admin__h1 { font-weight: 850; font-size: 1.35rem; letter-spacing: -0.02em; margin: 0; line-height: 1.2; }
            .fac-admin__lead { margin: 6px 0 0; font-size: 14px; color: var(--muted); max-width: 760px; line-height: 1.5; }
            .fac-kpis { margin-top: 18px; display: grid; gap: 12px; grid-template-columns: repeat(2, 1fr); }
            @media (min-width: 720px) { .fac-kpis { grid-template-columns: repeat(3, 1fr); } }
            .fac-kpi { border: 1px solid var(--border); border-radius: 16px; padding: 14px; background: var(--card-strong); box-shadow: var(--shadow-soft); min-height: 88px; }
            .fac-kpi__k { font-size: 11px; font-weight: 650; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; }
            .fac-kpi__v { font-size: 1.65rem; font-weight: 850; margin-top: 6px; }
            .fac-kpi__v--ok { color: var(--fac-ok); }
            .fac-kpi__v--info { color: var(--fac-info); }
            .fac-table-wrap { margin-top: 0; width: 100%; max-height: min(72vh, 720px); overflow: auto; border: 1px solid var(--border); border-radius: 18px; background: var(--card-strong); box-shadow: var(--shadow-soft); }
            .fac-table { width: 100%; border-collapse: collapse; min-width: 920px; }
            .fac-table th, .fac-table td { border-bottom: 1px solid var(--border); padding: 12px; vertical-align: top; text-align: left; }
            .fac-table th { font-size: 11px; color: var(--muted); font-weight: 700; text-transform: uppercase; letter-spacing: .05em; background: var(--surface-2); position: sticky; top: 0; z-index: 4; box-shadow: var(--sticky-table-head-shadow, 0 10px 28px -8px rgba(2, 6, 23, 0.28)); }
            .fac-table tbody tr:hover { background: color-mix(in srgb, var(--accent-a) 5%, transparent); }
            .fac-table tbody tr:last-child td { border-bottom: none; }
            .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 11.5px; }
            .fac-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 650; border: 1px solid var(--border); }
            .fac-badge--neutral { background: color-mix(in srgb, var(--muted) 8%, transparent); color: var(--muted); }
            .fac-badge--success { border-color: color-mix(in srgb, var(--fac-ok) 42%, var(--border)); background: var(--fac-ok-bg); color: color-mix(in srgb, var(--text) 82%, var(--fac-ok)); }
            .fac-badge--info { border-color: color-mix(in srgb, var(--fac-info) 40%, var(--border)); background: var(--fac-info-bg); color: color-mix(in srgb, var(--text) 82%, var(--fac-info)); }
            .fac-badge--danger { border-color: color-mix(in srgb, var(--fac-bad) 45%, var(--border)); background: var(--fac-bad-bg); color: var(--fac-bad); }
            .fac-badge--warn { border-color: color-mix(in srgb, var(--fac-warn) 40%, var(--border)); background: var(--fac-warn-bg); color: color-mix(in srgb, var(--text) 80%, var(--fac-warn)); }
            .fac-actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
            .fac-btn { appearance: none; display: inline-flex; align-items: center; gap: 8px; padding: 0 14px; height: 40px; border-radius: 12px; border: 1px solid var(--border); background: var(--surface-1); color: var(--text); font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; transition: background .12s ease; font-family: inherit; }
            .fac-btn:hover { background: color-mix(in srgb, var(--bg0) 82%, transparent); text-decoration: none; }
            .fac-btn--primary { border-color: color-mix(in srgb, var(--accent-a) 35%, var(--border)); background: color-mix(in srgb, var(--accent-a) 10%, var(--surface-1)); }
            .fac-filter { margin-top: 14px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
            .fac-filter__label { font-size: 12px; font-weight: 650; color: var(--muted); }
            .fac-audit-filter-form { width: 100%; margin-top: 14px; }
            .fac-audit-filter-head { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 12px; }
            .fac-audit-filter-themes { display: grid; gap: 12px; grid-template-columns: 1fr; }
            @media (min-width: 900px) { .fac-audit-filter-themes { grid-template-columns: repeat(2, 1fr); } }
            .fac-audit-theme { margin: 0; padding: 12px 14px; border: 1px solid var(--border); border-radius: 14px; background: var(--surface-2); }
            .fac-audit-theme > legend { padding: 0 6px; font-size: 12px; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase; color: color-mix(in srgb, var(--text) 55%, var(--muted)); }
            .fac-audit-theme__tools { display: flex; flex-wrap: wrap; gap: 6px; margin: 8px 0 10px; }
            .fac-audit-theme__tools .fac-btn { height: 32px; font-size: 12px; padding: 0 10px; }
            .fac-audit-theme__checks { display: flex; flex-wrap: wrap; gap: 8px 14px; align-items: flex-start; }
            .fac-audit-check { display: inline-flex; align-items: flex-start; gap: 8px; font-size: 13px; cursor: pointer; max-width: 100%; }
            .fac-audit-check input { margin-top: 3px; flex-shrink: 0; }
            .fac-audit-check span { line-height: 1.35; color: var(--text); }
            .fac-audit-filter-actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-top: 14px; }
            .fac-list-block { margin-top: 1.5rem; }
            .fac-list-block__title { margin: 0 0 14px; padding: 11px 16px 12px; border-radius: 14px; border: 1px solid var(--border); background: var(--surface-2); font-size: 12px; font-weight: 800; letter-spacing: 0.07em; text-transform: uppercase; color: color-mix(in srgb, var(--text) 52%, var(--muted)); }
            .fac-pager { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 14px; padding: 14px 16px; border: 1px solid var(--border); border-radius: 16px; background: var(--card-strong); box-shadow: var(--shadow-soft); }
            .fac-pager--above { margin-top: 0; margin-bottom: 12px; }
            .fac-pager--below { margin-top: 16px; }
            .fac-pager__left { display: flex; flex-wrap: wrap; align-items: center; gap: 14px 18px; }
            .fac-pager__meta { font-size: 13px; color: var(--muted); line-height: 1.4; }
            .fac-pager__form { display: inline-flex; align-items: center; gap: 10px; flex-wrap: wrap; margin: 0; }
            .fac-pager__label { font-size: 13px; font-weight: 650; color: var(--text); }
            .fac-pager__select { appearance: none; padding: 8px 34px 8px 12px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface-1); color: var(--text); font-size: 13px; font-weight: 650; font-family: inherit; cursor: pointer; background-image: linear-gradient(45deg, transparent 50%, var(--muted) 50%), linear-gradient(135deg, var(--muted) 50%, transparent 50%); background-position: calc(100% - 14px) calc(50% + 2px), calc(100% - 9px) calc(50% + 2px); background-size: 5px 5px, 5px 5px; background-repeat: no-repeat; }
            .fac-pager__links { flex: 1 1 auto; min-width: 0; display: flex; justify-content: flex-end; }
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
                                <div class="bridge-brand__tagline">Admin • Auditoria de usuários</div>
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
                            <div class="fac-admin__hero">
                                <div>
                                    <div class="fac-admin__title">
                                        <div class="fac-admin__title-ico" aria-hidden="true">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                        </div>
                                        <div>
                                            <h1 class="fac-admin__h1">Auditoria de ações</h1>
                                            <p class="fac-admin__lead">
                                                @if (! empty($auditUserId))
                                                    Mostrando apenas eventos em que este usuário foi o <strong>autor</strong> da ação ou o <strong>alvo</strong> (conta <span class="mono">user #{{ $auditUserId }}</span>@if ($auditUser) — {{ $auditUser->name }}, <span class="mono">{{ $auditUser->username }}</span>@endif).
                                                @else
                                                    Login, logout, gestão de utilizadores, integrações (testes de ponte, testes da visão geral, alterações de configuração), fila de frequência iEducar e ações administrativas (por exemplo atualização de status facial).
                                                @endif
                                                Horários em <strong>{{ \App\Support\DateDisplay::timezoneLabel() }}</strong>.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="fac-actions">
                                    <a class="fac-btn" href="{{ route('users.index') }}">Gerenciar usuários</a>
                                    <a class="fac-btn fac-btn--primary" href="{{ route('admin.ieducar-frequencia-deliveries.index') }}">Fila frequência iEducar</a>
                                </div>
                            </div>

                            @php
                                $st = is_array($stats ?? null) ? $stats : ['total' => 0, 'today' => 0, 'logins_today' => 0];
                                $actionLabels = is_array($actionLabels ?? null) ? $actionLabels : [];
                                $auditUserId = $auditUserId ?? null;
                                $clearAuditParams = array_filter(['per_page' => $perPage ?? 25]);
                            @endphp
                            @if ($auditUserId)
                                <div class="fac-filter" style="margin-top:0;padding:12px 14px;border-radius:14px;border:1px solid var(--border);background:color-mix(in srgb,var(--accent-a) 6%,var(--card-strong));align-items:center;">
                                    <span class="fac-filter__label" style="margin:0;">Filtro ativo:</span>
                                    <span style="font-size:13px;font-weight:650;">Usuário #{{ $auditUserId }}@if ($auditUser) — {{ $auditUser->username }}@endif</span>
                                    <a class="fac-btn" href="{{ route('admin.user-audit-logs.index', $clearAuditParams) }}" style="margin-left:auto;">Ver toda a auditoria</a>
                                </div>
                            @endif
                            <div class="fac-kpis">
                                <div class="fac-kpi">
                                    <div class="fac-kpi__k">Total de eventos</div>
                                    <div class="fac-kpi__v">{{ (int) ($st['total'] ?? 0) }}</div>
                                </div>
                                <div class="fac-kpi">
                                    <div class="fac-kpi__k">Hoje (todos)</div>
                                    <div class="fac-kpi__v fac-kpi__v--info">{{ (int) ($st['today'] ?? 0) }}</div>
                                </div>
                                <div class="fac-kpi">
                                    <div class="fac-kpi__k">Logins hoje</div>
                                    <div class="fac-kpi__v fac-kpi__v--ok">{{ (int) ($st['logins_today'] ?? 0) }}</div>
                                </div>
                            </div>

                            @php
                                $actionFiltersList = is_array($actionFilters ?? null) ? $actionFilters : [];
                                $actionThemesList = is_array($actionThemes ?? null) ? $actionThemes : [];
                            @endphp
                            <form method="get" action="{{ route('admin.user-audit-logs.index') }}" class="fac-audit-filter-form">
                                <div class="fac-audit-filter-head">
                                    <span class="fac-filter__label" style="margin:0;">Filtrar por ação (uma ou várias):</span>
                                    <span class="bridge-muted" style="font-size:12px;">Marque os tipos de evento e clique em Aplicar. Deixe tudo desmarcado para ver todas as ações.</span>
                                </div>
                                <input type="hidden" name="per_page" value="{{ (int) request('per_page', $perPage ?? 25) }}" />
                                @if ($auditUserId)
                                    <input type="hidden" name="audit_user_id" value="{{ $auditUserId }}" />
                                @endif
                                <div class="fac-audit-filter-themes">
                                    @foreach ($actionThemesList as $theme)
                                        <fieldset class="fac-audit-theme" data-audit-theme="{{ $theme['id'] }}">
                                            <legend>{{ $theme['label'] }}</legend>
                                            <div class="fac-audit-theme__tools">
                                                <button type="button" class="fac-btn" data-audit-select-theme>Marcar todos</button>
                                                <button type="button" class="fac-btn" data-audit-clear-theme>Desmarcar todos</button>
                                            </div>
                                            <div class="fac-audit-theme__checks">
                                                @foreach ($theme['actions'] as $act)
                                                    <label class="fac-audit-check">
                                                        <input type="checkbox" name="actions[]" value="{{ $act['key'] }}" @checked(in_array($act['key'], $actionFiltersList, true)) />
                                                        <span>{{ $act['label'] }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </fieldset>
                                    @endforeach
                                </div>
                                <div class="fac-audit-filter-actions">
                                    <button type="submit" class="fac-btn fac-btn--primary">Aplicar filtro</button>
                                    @if ($actionFiltersList !== [])
                                        <a class="fac-btn" href="{{ route('admin.user-audit-logs.index', array_filter(['per_page' => $perPage, 'audit_user_id' => $auditUserId])) }}">Limpar filtro de ação</a>
                                    @endif
                                </div>
                            </form>
                            <script>
                                (function () {
                                    document.querySelectorAll('[data-audit-select-theme]').forEach(function (btn) {
                                        btn.addEventListener('click', function () {
                                            var fs = btn.closest('fieldset[data-audit-theme]');
                                            if (!fs) return;
                                            fs.querySelectorAll('input[type="checkbox"]').forEach(function (cb) { cb.checked = true; });
                                        });
                                    });
                                    document.querySelectorAll('[data-audit-clear-theme]').forEach(function (btn) {
                                        btn.addEventListener('click', function () {
                                            var fs = btn.closest('fieldset[data-audit-theme]');
                                            if (!fs) return;
                                            fs.querySelectorAll('input[type="checkbox"]').forEach(function (cb) { cb.checked = false; });
                                        });
                                    });
                                })();
                            </script>

                            <section class="fac-list-block" aria-labelledby="audit-list-title">
                                <h2 class="fac-list-block__title" id="audit-list-title">Registros</h2>
                                @include('admin.partials.list-pagination', ['paginator' => $items, 'perPage' => $perPage, 'position' => 'top'])

                                <div class="fac-table-wrap">
                                    <table class="fac-table">
                                        <thead>
                                            <tr>
                                                <th style="width:10%;">ID</th>
                                                <th style="width:16%;">Quando</th>
                                                <th style="width:14%;">Usuário</th>
                                                <th style="width:18%;">Ação</th>
                                                <th style="width:14%;">Alvo</th>
                                                <th style="width:28%;">Detalhes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($items as $row)
                                                @php
                                                    $badge = match (true) {
                                                        $row->action === 'auth.login' => ['cls' => 'fac-badge--success', 't' => 'login'],
                                                        $row->action === 'auth.logout' => ['cls' => 'fac-badge--neutral', 't' => 'logout'],
                                                        $row->action === 'user.created' => ['cls' => 'fac-badge--info', 't' => 'criação'],
                                                        in_array($row->action, ['user.deactivated', 'login_denied_inactive', 'session_terminated_inactive'], true) => ['cls' => 'fac-badge--danger', 't' => 'bloqueio / desativação'],
                                                        $row->action === 'user.reactivated' => ['cls' => 'fac-badge--success', 't' => 'reativação'],
                                                        in_array($row->action, ['user.promoted_admin', 'user.demoted_admin'], true) => ['cls' => 'fac-badge--info', 't' => 'perfil admin'],
                                                        str_starts_with((string) $row->action, 'integration.') => ['cls' => 'fac-badge--info', 't' => 'integração'],
                                                        str_starts_with((string) $row->action, 'frequencia.') => ['cls' => 'fac-badge--info', 't' => 'frequência'],
                                                        str_starts_with((string) $row->action, 'admin.') => ['cls' => 'fac-badge--warn', 't' => 'admin'],
                                                        default => ['cls' => 'fac-badge--warn', 't' => 'evento'],
                                                    };
                                                    $metaJson = '';
                                                    if (is_array($row->meta) && $row->meta !== []) {
                                                        $metaJson = json_encode($row->meta, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                                                    }
                                                @endphp
                                                <tr>
                                                    <td><span class="mono" style="font-weight:700;">#{{ $row->id }}</span></td>
                                                    <td><span class="mono">{{ $row->occurred_at?->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}</span></td>
                                                    <td>
                                                        @if ($row->actor)
                                                            <span class="mono">{{ $row->actor->username }}</span>
                                                            <div class="bridge-muted" style="font-size:11px;margin-top:4px;">{{ $row->actor->name }}</div>
                                                        @else
                                                            <span class="bridge-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="fac-badge {{ $badge['cls'] }}">{{ $actionLabels[$row->action] ?? $row->action }}</span>
                                                    </td>
                                                    <td>
                                                        @if ($row->subject_type && $row->subject_id)
                                                            <span class="mono">{{ $row->subject_type }} #{{ $row->subject_id }}</span>
                                                        @else
                                                            <span class="bridge-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="mono bridge-muted" style="font-size:11px;line-height:1.45;">{{ $row->ip_address ?? '—' }}</span>
                                                        @if ($metaJson !== '')
                                                            <details style="margin-top:8px;">
                                                                <summary style="cursor:pointer;font-size:12px;font-weight:650;color:var(--accent-a);">JSON meta</summary>
                                                                <pre class="mono" style="margin:8px 0 0;padding:10px;border-radius:12px;border:1px solid var(--border);background:color-mix(in srgb,var(--bg0) 70%,transparent);white-space:pre-wrap;word-break:break-word;max-height:200px;overflow:auto;font-size:11px;">{{ $metaJson }}</pre>
                                                            </details>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="bridge-muted" style="padding:22px;">Nenhum evento registrado ainda.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                @include('admin.partials.list-pagination', ['paginator' => $items, 'perPage' => $perPage, 'position' => 'bottom'])
                            </section>
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
