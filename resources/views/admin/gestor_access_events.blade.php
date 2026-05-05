<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin • Gestor access-events • {{ config('app.name', 'Bridge ERP') }}</title>
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
        @include('partials.integr-visual-kit')
        <script defer src="/home.js"></script>
        <style>
            .gae-admin { --gae-ok: #059669; --gae-bad: #dc2626; --gae-warn: #d97706; --gae-info: #0284c7; }
            .gae-alert { margin-top: 14px; padding: 12px 14px; border-radius: 14px; border: 1px solid color-mix(in srgb, var(--gae-warn) 40%, var(--border)); background: color-mix(in srgb, var(--gae-warn) 10%, var(--surface-1)); font-size: 14px; }
            .gae-filters { margin-top: 16px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
            .gae-filters select { padding: 8px 12px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface-1); font-family: inherit; }
            .fac-table-wrap { margin-top: 14px; border: 1px solid var(--border); border-radius: 16px; overflow: auto; max-height: min(70vh, 680px); background: var(--card-strong); }
            .fac-table { width: 100%; border-collapse: collapse; min-width: 1020px; }
            .fac-table th, .fac-table td { border-bottom: 1px solid var(--border); padding: 10px 12px; text-align: left; vertical-align: top; }
            .fac-table th { font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); background: var(--surface-2); position: sticky; top: 0; z-index: 2; }
            .mono { font-family: ui-monospace, monospace; font-size: 11.5px; }
            .fac-badge { display: inline-flex; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 650; border: 1px solid var(--border); }
            .fac-badge--ok { border-color: color-mix(in srgb, var(--gae-ok) 40%, var(--border)); color: var(--gae-ok); }
            .fac-badge--bad { border-color: color-mix(in srgb, var(--gae-bad) 40%, var(--border)); color: var(--gae-bad); }
            .fac-badge--warn { border-color: color-mix(in srgb, var(--gae-warn) 40%, var(--border)); color: var(--gae-warn); }
            .fac-badge--info { border-color: color-mix(in srgb, var(--gae-info) 40%, var(--border)); color: var(--gae-info); }
            .fac-btn-ico { display: inline-flex; width: 38px; height: 38px; align-items: center; justify-content: center; border-radius: 10px; border: 1px solid var(--border); background: var(--surface-1); text-decoration: none; color: var(--text); }
            .fac-btn-ico:hover { background: color-mix(in srgb, var(--bg0) 80%, transparent); }
            .fac-btn-ico--ok { border-color: color-mix(in srgb, var(--gae-ok) 40%, var(--border)); background: color-mix(in srgb, var(--gae-ok) 10%, var(--surface-1)); color: color-mix(in srgb, var(--text) 86%, var(--gae-ok)); }
            .fac-btn-ico--ok:hover { border-color: color-mix(in srgb, var(--gae-ok) 55%, var(--border)); background: color-mix(in srgb, var(--gae-ok) 16%, var(--surface-1)); }
            .fac-btn-ico--warn { border-color: color-mix(in srgb, var(--gae-warn) 40%, var(--border)); background: color-mix(in srgb, var(--gae-warn) 10%, var(--surface-1)); color: color-mix(in srgb, var(--text) 86%, var(--gae-warn)); }
            .fac-btn-ico--warn:hover { border-color: color-mix(in srgb, var(--gae-warn) 55%, var(--border)); background: color-mix(in srgb, var(--gae-warn) 16%, var(--surface-1)); }
            .fac-btn-ico--info { border-color: color-mix(in srgb, var(--gae-info) 40%, var(--border)); background: color-mix(in srgb, var(--gae-info) 10%, var(--surface-1)); color: color-mix(in srgb, var(--text) 86%, var(--gae-info)); }
            .fac-btn-ico--info:hover { border-color: color-mix(in srgb, var(--gae-info) 55%, var(--border)); background: color-mix(in srgb, var(--gae-info) 16%, var(--surface-1)); }
        </style>
    </head>
    <body>
        <div class="bridge-shell gae-admin integr-app">
            <header class="bridge-header">
                <div class="bridge-container">
                    <div class="bridge-header__inner">
                        <a class="bridge-brand" href="{{ url('/dashboard') }}">
                            <img src="/favicon.svg" alt="" class="bridge-brand__logo" />
                            <div class="bridge-brand__text">
                                <div class="bridge-brand__name">{{ config('app.name', 'Bridge ERP') }}</div>
                                <div class="bridge-brand__tagline">Admin • access-events (Gestor HMAC + catraca token)</div>
                            </div>
                        </a>
                        @include('partials.bridge-user-menu')
                    </div>
                </div>
            </header>
            <main class="bridge-main">
                <div class="bridge-container">
                        <div class="bridge-panel" style="padding: 20px;">
                        <div class="integr-page-hero">
                            <div class="integr-page-hero__main">
                                <h1 class="integr-section__title">Fila / auditoria — access-events</h1>
                                <p class="integr-section__lead">Cada linha é um POST: canal <span class="mono">gestor_hmac</span> (<span class="mono">/api/v1/gestor/access-events</span>) ou <span class="mono">catraca_bearer</span> (<span class="mono">/api/v1/catraca/access-events</span>). O JSON bruto fica em <span class="mono">inbound_payload</span>. O iEducar é chamado <strong>somente</strong> em preview (<span class="mono">meta.preview=true</span>).</p>
                            </div>
                        </div>
                        <x-audit-toolbar style="margin-top: 12px;">
                            <x-slot:left>
                                <form method="get" action="{{ route('admin.gestor-access-events.index') }}" class="gae-filters" style="margin-top: 0;">
                                    <input type="hidden" name="per_page" value="{{ (int) $perPage }}">
                                    <label for="f-status">Estado</label>
                                    <select id="f-status" name="status" onchange="this.form.submit()">
                                        <option value="" @selected($statusFilter === '')>Todos</option>
                                        <option value="completed" @selected($statusFilter === 'completed')>completed</option>
                                        <option value="failed" @selected($statusFilter === 'failed')>failed</option>
                                        <option value="skipped" @selected($statusFilter === 'skipped')>skipped</option>
                                        <option value="pending" @selected($statusFilter === 'pending')>pending</option>
                                        <option value="processing" @selected($statusFilter === 'processing')>processing</option>
                                    </select>
                                </form>
                            </x-slot:left>
                        </x-audit-toolbar>

                        <div class="gae-alert" role="status">
                            <strong>Aviso de modo:</strong> o rótulo <span class="mono">preview</span> / <span class="mono">homolog</span> vem da integração Gestor (<span class="mono">extra.ieducar_processing.environment</span>). O envio ao iEducar neste fluxo está <strong>sempre em preview</strong> (simulação), independentemente desse rótulo.
                        </div>

                        @include('admin.partials.list-pagination', ['paginator' => $items, 'perPage' => $perPage, 'position' => 'top'])

                        <div class="fac-table-wrap">
                            <table class="fac-table">
                                <thead>
                                    <tr>
                                        <th style="width:8%;">ID</th>
                                        <th style="width:14%;">ID do evento</th>
                                        <th style="width:12%;">Canal</th>
                                        <th style="width:12%;">Estado</th>
                                        <th style="width:10%;">Gestor (rótulo)</th>
                                        <th style="width:8%;">HTTP iEd.</th>
                                        <th style="width:14%;">Quando</th>
                                        <th style="width:6%;"></th>
                                        <th style="width:8%;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($items as $row)
                                        <tr>
                                            <td class="mono"><strong>#{{ $row->id }}</strong></td>
                                            <td class="mono clip" title="{{ $row->event_id }}">{{ $row->event_id }}</td>
                                            <td class="mono">{{ $row->inbound_channel ?? 'gestor_hmac' }}</td>
                                            <td>
                                                @if ($row->processing_status === 'completed')
                                                    <span class="fac-badge fac-badge--ok">{{ $row->processing_status }}</span>
                                                @elseif ($row->processing_status === 'failed')
                                                    <span class="fac-badge fac-badge--bad">{{ $row->processing_status }}</span>
                                                @elseif ($row->processing_status === 'skipped')
                                                    <span class="fac-badge fac-badge--warn">{{ $row->processing_status }}</span>
                                                @elseif ($row->processing_status === 'processing')
                                                    <span class="fac-badge fac-badge--info">{{ $row->processing_status }}</span>
                                                @else
                                                    <span class="fac-badge fac-badge--info">{{ $row->processing_status }}</span>
                                                @endif
                                            </td>
                                            <td class="mono">{{ $row->gestor_ie_environment }}</td>
                                            <td class="mono">{{ $row->ieducar_frequencia_http_status ?? '—' }}</td>
                                            <td class="mono">{{ $row->created_at ? \App\Support\DateDisplay::formatHuman($row->created_at, true) : '' }}</td>
                                            <td style="text-align:right;">
                                                <a class="fac-btn-ico fac-btn-ico--info" href="{{ route('admin.gestor-access-events.show', ['id' => $row->id]) }}" title="Detalhe">→</a>
                                            </td>
                                            <td style="text-align:right;">
                                                @php $act = data_get($row->analysis_json, 'action'); @endphp
                                                @if ($ieducarEnabled ?? true)
                                                    @if ($act !== 'mark_presence')
                                                        <form method="post" action="{{ route('admin.gestor-access-events.force-mark-presence', ['id' => $row->id]) }}" style="display:inline;" onsubmit="return confirm('Forçar mark_presence=true e reenviar ao iEducar?');">
                                                            @csrf
                                                            <button type="submit" class="fac-btn-ico fac-btn-ico--warn" title="Forçar presença e reenviar ao iEducar">✎</button>
                                                        </form>
                                                    @elseif ($row->processing_status === 'pending')
                                                        <form method="post" action="{{ route('admin.gestor-access-events.requeue', ['id' => $row->id]) }}" style="display:inline;">
                                                            @csrf
                                                            <button type="submit" class="fac-btn-ico fac-btn-ico--info" title="Reenfileirar pendente">⟲</button>
                                                        </form>
                                                        <form method="post" action="{{ route('admin.gestor-access-events.force-process', ['id' => $row->id]) }}" style="display:inline;" onsubmit="return confirm('Executar o processamento agora (sync)?');">
                                                            @csrf
                                                            <button type="submit" class="fac-btn-ico fac-btn-ico--ok" title="Forçar processamento (sync)">▶</button>
                                                        </form>
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="9" style="padding:20px;color:var(--muted);">Nenhum registro.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @include('admin.partials.list-pagination', ['paginator' => $items, 'perPage' => $perPage, 'position' => 'bottom'])
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
