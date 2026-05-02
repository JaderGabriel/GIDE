<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Admin • Fila frequência iEducar • {{ config('app.name', 'Bridge ERP') }}</title>

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

            .fac-admin { --fac-ok: #059669; --fac-ok-bg: color-mix(in srgb, #059669 14%, transparent); --fac-bad: #dc2626; --fac-bad-bg: color-mix(in srgb, #dc2626 12%, transparent); --fac-warn: #d97706; --fac-warn-bg: color-mix(in srgb, #d97706 14%, transparent); --fac-info: #0284c7; --fac-info-bg: color-mix(in srgb, #0284c7 12%, transparent); --fac-muted: #64748b; }
            .fac-admin__hero { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 16px; margin-top: 4px; }
            .fac-admin__title { display: flex; align-items: center; gap: 14px; }
            .fac-admin__title-ico { width: 48px; height: 48px; border-radius: 14px; display: grid; place-items: center; background: linear-gradient(145deg, color-mix(in srgb, var(--accent-c) 22%, var(--surface-1)), var(--surface-1)); border: 1px solid var(--border); color: var(--accent-c); flex-shrink: 0; }
            .fac-admin__title-ico svg { width: 26px; height: 26px; }
            .fac-admin__h1 { font-weight: 850; font-size: 1.35rem; letter-spacing: -0.02em; margin: 0; line-height: 1.2; }
            .fac-admin__lead { margin: 6px 0 0; font-size: 14px; color: var(--muted); max-width: 720px; line-height: 1.5; }

            .fac-alert { margin-top: 14px; padding: 12px 14px; border-radius: 14px; display: flex; align-items: flex-start; gap: 10px; border: 1px solid color-mix(in srgb, var(--accent-c) 35%, var(--border)); background: color-mix(in srgb, var(--accent-c) 8%, var(--surface-1)); font-size: 14px; }
            .fac-alert svg { flex: 0 0 20px; margin-top: 1px; color: var(--accent-c); }

            .fac-health { margin-top: 14px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
            .fac-health__label { font-size: 12px; font-weight: 650; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; width: 100%; }
            .fac-chip { display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; font-size: 13px; font-weight: 600; border: 1px solid var(--border); background: var(--surface-1); }
            .fac-chip svg { width: 18px; height: 18px; flex-shrink: 0; }
            .fac-chip--ok { border-color: color-mix(in srgb, var(--fac-ok) 40%, var(--border)); background: var(--fac-ok-bg); color: color-mix(in srgb, var(--text) 88%, var(--fac-ok)); }
            .fac-chip--warn { border-color: color-mix(in srgb, var(--fac-warn) 40%, var(--border)); background: var(--fac-warn-bg); color: color-mix(in srgb, var(--text) 85%, var(--fac-warn)); }

            .fac-kpis { margin-top: 18px; display: grid; gap: 12px; grid-template-columns: repeat(2, 1fr); }
            @media (min-width: 720px) { .fac-kpis { grid-template-columns: repeat(3, 1fr); } }
            @media (min-width: 1100px) { .fac-kpis { grid-template-columns: repeat(6, 1fr); } }
            .fac-kpi { border: 1px solid var(--border); border-radius: 16px; padding: 14px 14px 12px; background: var(--card-strong); box-shadow: var(--shadow-soft); display: flex; flex-direction: column; gap: 4px; min-height: 92px; }
            .fac-kpi__k { font-size: 11px; font-weight: 650; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; display: flex; align-items: center; gap: 6px; }
            .fac-kpi__k svg { width: 14px; height: 14px; opacity: .85; }
            .fac-kpi__v { font-size: 1.65rem; font-weight: 850; letter-spacing: -0.03em; line-height: 1.1; }
            .fac-kpi__v--ok { color: var(--fac-ok); }
            .fac-kpi__v--bad { color: var(--fac-bad); }
            .fac-kpi__v--warn { color: var(--fac-warn); }
            .fac-kpi__v--info { color: var(--fac-info); }

            .fac-table-wrap { margin-top: 0; width: 100%; max-height: min(72vh, 720px); overflow: auto; -webkit-overflow-scrolling: touch; border: 1px solid var(--border); border-radius: 18px; background: var(--card-strong); box-shadow: var(--shadow-soft); }
            .fac-table { width: 100%; border-collapse: collapse; min-width: 980px; table-layout: fixed; }
            .fac-table th, .fac-table td { border-bottom: 1px solid var(--border); padding: 12px 12px; vertical-align: top; text-align: left; }
            .fac-table__dataref { display: block; margin-top: 6px; font-size: 11px; font-weight: 600; line-height: 1.35; color: color-mix(in srgb, #7c3aed 72%, var(--text)); word-break: break-word; }
            .fac-table__aluno { display: block; margin-top: 6px; font-size: 11px; font-weight: 600; line-height: 1.4; color: color-mix(in srgb, #0d9488 70%, var(--text)); word-break: break-word; }
            .fac-resumo-primary { font-size: 12px; font-weight: 650; color: var(--muted); line-height: 1.35; }
            .fac-table th { font-size: 11px; color: var(--muted); font-weight: 700; text-transform: uppercase; letter-spacing: .05em; background: var(--surface-2); position: sticky; top: 0; z-index: 4; box-shadow: var(--sticky-table-head-shadow, 0 10px 28px -8px rgba(2, 6, 23, 0.28)); }
            .fac-table tbody tr { transition: background .12s ease; }
            .fac-table tbody tr:hover { background: color-mix(in srgb, var(--accent-a) 5%, transparent); }
            .fac-table tbody tr:last-child td { border-bottom: none; }
            .fac-table tbody tr:nth-child(even) { background: color-mix(in srgb, var(--bg0) 35%, transparent); }
            .fac-table tbody tr:nth-child(even):hover { background: color-mix(in srgb, var(--accent-a) 7%, transparent); }

            .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 11.5px; }
            .fac-idcell { display: flex; align-items: flex-start; gap: 10px; }
            .fac-idcell__badge { flex: 0 0 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: linear-gradient(160deg, color-mix(in srgb, var(--accent-a) 18%, var(--surface-1)), var(--surface-1)); border: 1px solid var(--border); color: color-mix(in srgb, var(--text) 90%, var(--accent-a)); }
            .fac-idcell__badge svg { width: 18px; height: 18px; }
            .clip { max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .wrap { white-space: normal; overflow-wrap: anywhere; word-break: break-word; }

            .fac-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 650; border: 1px solid var(--border); line-height: 1.2; }
            .fac-badge svg { width: 12px; height: 12px; flex-shrink: 0; }
            .fac-badge--neutral { background: color-mix(in srgb, var(--muted) 8%, transparent); color: var(--muted); }
            .fac-badge--success { border-color: color-mix(in srgb, var(--fac-ok) 42%, var(--border)); background: var(--fac-ok-bg); color: color-mix(in srgb, var(--text) 82%, var(--fac-ok)); }
            .fac-badge--danger { border-color: color-mix(in srgb, var(--fac-bad) 45%, var(--border)); background: var(--fac-bad-bg); color: var(--fac-bad); }
            .fac-badge--warn { border-color: color-mix(in srgb, var(--fac-warn) 40%, var(--border)); background: var(--fac-warn-bg); color: color-mix(in srgb, var(--text) 80%, var(--fac-warn)); }
            .fac-badge--info { border-color: color-mix(in srgb, var(--fac-info) 40%, var(--border)); background: var(--fac-info-bg); color: color-mix(in srgb, var(--text) 82%, var(--fac-info)); }
            .fac-badge-row { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }

            .fac-actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: flex-end; }
            .fac-btn-ico { appearance: none; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; padding: 0; border-radius: 12px; border: 1px solid var(--border); background: var(--surface-1); color: var(--text); cursor: pointer; text-decoration: none; transition: background .12s ease, border-color .12s ease, transform .08s ease; }
            .fac-btn-ico:hover { background: color-mix(in srgb, var(--bg0) 80%, transparent); border-color: color-mix(in srgb, var(--accent-a) 28%, var(--border)); text-decoration: none; }
            .fac-btn-ico:active { transform: translateY(1px); }
            .fac-btn-ico svg { width: 18px; height: 18px; }
            .fac-btn-ico--primary { border-color: color-mix(in srgb, var(--accent-a) 35%, var(--border)); background: color-mix(in srgb, var(--accent-a) 10%, var(--surface-1)); color: color-mix(in srgb, var(--text) 75%, var(--accent-a)); }
            .fac-muted { font-size: 12px; color: var(--muted); margin-top: 4px; line-height: 1.4; }
            .fac-stack { display: flex; flex-direction: column; gap: 4px; }

            .fac-btn { appearance: none; display: inline-flex; align-items: center; gap: 8px; padding: 0 14px; height: 40px; border-radius: 12px; border: 1px solid var(--border); background: var(--surface-1); color: var(--text); font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; transition: background .12s ease, border-color .12s ease; font-family: inherit; }
            .fac-btn:hover { background: color-mix(in srgb, var(--bg0) 82%, transparent); border-color: color-mix(in srgb, var(--accent-a) 28%, var(--border)); text-decoration: none; }
            .fac-btn svg { width: 18px; height: 18px; flex-shrink: 0; }
            .fac-btn--primary { border-color: color-mix(in srgb, var(--accent-a) 35%, var(--border)); background: color-mix(in srgb, var(--accent-a) 10%, var(--surface-1)); color: color-mix(in srgb, var(--text) 80%, var(--accent-a)); }

            .fac-pager { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 14px; padding: 14px 16px; border: 1px solid var(--border); border-radius: 16px; background: var(--card-strong); box-shadow: var(--shadow-soft); }
            .fac-pager--above { margin-top: 0; margin-bottom: 12px; }
            .fac-pager--below { margin-top: 16px; }
            .fac-pager__left { display: flex; flex-wrap: wrap; align-items: center; gap: 14px 18px; }
            .fac-pager__meta { font-size: 13px; color: var(--muted); line-height: 1.4; }
            .fac-pager__form { display: inline-flex; align-items: center; gap: 10px; flex-wrap: wrap; margin: 0; }
            .fac-pager__label { font-size: 13px; font-weight: 650; color: var(--text); }
            .fac-pager__select { appearance: none; padding: 8px 34px 8px 12px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface-1); color: var(--text); font-size: 13px; font-weight: 650; font-family: inherit; cursor: pointer; background-image: linear-gradient(45deg, transparent 50%, var(--muted) 50%), linear-gradient(135deg, var(--muted) 50%, transparent 50%); background-position: calc(100% - 14px) calc(50% + 2px), calc(100% - 9px) calc(50% + 2px); background-size: 5px 5px, 5px 5px; background-repeat: no-repeat; }
            .fac-pager__select:hover { border-color: color-mix(in srgb, var(--accent-a) 28%, var(--border)); }
            .fac-pager__links { flex: 1 1 auto; min-width: 0; display: flex; justify-content: flex-end; }
            .fac-pagination { display: flex; justify-content: flex-end; width: 100%; }
            .fac-pagination__list { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 6px; margin: 0; padding: 0; list-style: none; }
            .fac-pagination__list li { margin: 0; padding: 0; list-style: none; }
            .fac-pagination__link { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 36px; padding: 0 10px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface-1); color: var(--text); font-size: 13px; font-weight: 650; text-decoration: none; transition: background .12s ease, border-color .12s ease; }
            .fac-pagination__link:hover { background: color-mix(in srgb, var(--bg0) 82%, transparent); border-color: color-mix(in srgb, var(--accent-a) 28%, var(--border)); text-decoration: none; }
            .fac-pagination__link--active { border-color: color-mix(in srgb, var(--accent-a) 38%, var(--border)); background: color-mix(in srgb, var(--accent-a) 12%, var(--surface-1)); color: color-mix(in srgb, var(--text) 82%, var(--accent-a)); font-weight: 750; }
            .fac-pagination__gap { display: inline-flex; align-items: center; justify-content: center; min-width: 28px; height: 36px; padding: 0 4px; font-size: 13px; font-weight: 700; color: var(--muted); }
            .fac-list-block { margin-top: 3rem; }
            .fac-list-block__title { margin: 0 0 14px; padding: 11px 16px 12px; border-radius: 14px; border: 1px solid var(--border); background: var(--surface-2); font-size: 12px; font-weight: 800; letter-spacing: 0.07em; text-transform: uppercase; color: color-mix(in srgb, var(--text) 52%, var(--muted)); box-shadow: 0 8px 22px -6px color-mix(in srgb, var(--bg0) 88%, transparent), 0 3px 10px -3px color-mix(in srgb, var(--bg0) 70%, transparent); position: relative; z-index: 4; }
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
                                <div class="bridge-brand__tagline">Admin • Fila frequência → iEducar</div>
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
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>
                                        </div>
                                        <div>
                                            <h1 class="fac-admin__h1">Fila de frequência (GIDE → iEducar)</h1>
                                            <p class="fac-admin__lead">Itens enfileirados pela ferramenta de integração (preview ou gravação). O worker atualiza status, HTTP e JSON de resposta. Formato por aluno (<span class="mono">cod_aluno</span> + <span class="mono">data_ref</span>).</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="fac-actions">
                                    <a class="fac-btn" href="{{ route('integrations.ieducar.frequencia-registro') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                                        Novo envio
                                    </a>
                                    <a class="fac-btn fac-btn--primary" href="{{ route('admin.facial-requests.index') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a4 4 0 0 1 4 4v2a4 4 0 0 1-8 0V6a4 4 0 0 1 4-4z"/><path d="M6 22v-2a6 6 0 0 1 12 0v2"/></svg>
                                        Solicitações faciais
                                    </a>
                                    <a class="fac-btn" href="{{ route('admin.user-audit-logs.index') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        Auditoria usuários
                                    </a>
                                </div>
                            </div>

                            @if (session('status'))
                                <div class="fac-alert" role="status">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                    <div><strong>{{ session('status') }}</strong></div>
                                </div>
                            @endif

                            <div class="fac-health">
                                <span class="fac-health__label">Integração</span>
                                @if ($ieducarReady)
                                    <span class="fac-chip fac-chip--ok">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                        iEducar com base URL e token
                                    </span>
                                @else
                                    <span class="fac-chip fac-chip--warn">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                        Configure iEducar (token / base URL)
                                    </span>
                                @endif
                            </div>

                            @php
                                $st = is_array($stats ?? null) ? $stats : [
                                    'total' => 0, 'pending' => 0, 'processing' => 0, 'completed' => 0, 'failed' => 0, 'preview' => 0, 'apply' => 0,
                                ];
                            @endphp
                            <div class="fac-kpis">
                                <div class="fac-kpi">
                                    <div class="fac-kpi__k">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        Total geral
                                    </div>
                                    <div class="fac-kpi__v">{{ (int) $st['total'] }}</div>
                                </div>
                                <div class="fac-kpi">
                                    <div class="fac-kpi__k">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        Pendente
                                    </div>
                                    <div class="fac-kpi__v fac-kpi__v--warn">{{ (int) $st['pending'] }}</div>
                                </div>
                                <div class="fac-kpi">
                                    <div class="fac-kpi__k">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                                        Processando
                                    </div>
                                    <div class="fac-kpi__v fac-kpi__v--info">{{ (int) $st['processing'] }}</div>
                                </div>
                                <div class="fac-kpi">
                                    <div class="fac-kpi__k">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                        Concluído
                                    </div>
                                    <div class="fac-kpi__v fac-kpi__v--ok">{{ (int) $st['completed'] }}</div>
                                </div>
                                <div class="fac-kpi">
                                    <div class="fac-kpi__k">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                        Falhou
                                    </div>
                                    <div class="fac-kpi__v fac-kpi__v--bad">{{ (int) $st['failed'] }}</div>
                                </div>
                                <div class="fac-kpi">
                                    <div class="fac-kpi__k">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 19 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        Preview / apply
                                    </div>
                                    <div class="fac-kpi__v" style="font-size:1.1rem;font-weight:750;line-height:1.25;">{{ (int) $st['preview'] }} <span class="fac-muted" style="font-weight:600;">/</span> {{ (int) $st['apply'] }}</div>
                                </div>
                            </div>

                            <section class="fac-list-block" aria-labelledby="fac-list-freq-title">
                                <h2 class="fac-list-block__title" id="fac-list-freq-title">Lista de entregas na fila</h2>
                                @include('admin.partials.list-pagination', ['paginator' => $items, 'perPage' => $perPage, 'position' => 'top'])

                                <div class="fac-table-wrap">
                                <table class="fac-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 12%;">ID</th>
                                            <th style="width: 12%;">Modo</th>
                                            <th style="width: 14%;">Status</th>
                                            <th style="width: 8%;">HTTP</th>
                                            <th style="width: 30%;">Resumo</th>
                                            <th style="width: 14%;">DATAS</th>
                                            <th style="width: 14%; text-align: right;">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($items as $it)
                                            @php
                                                $p = is_array($it->payload) ? $it->payload : [];
                                                $fmtDr = fn ($v) => \App\Support\DateDisplay::formatDataRefTable($v);
                                                if (isset($p['identificacao']['cod_aluno'])) {
                                                    $resumoPrimary = 'Envio unitário';
                                                    $id = is_array($p['identificacao'] ?? null) ? $p['identificacao'] : [];
                                                    $alunoParts = ['cod_aluno '.data_get($id, 'cod_aluno', '—')];
                                                    if (data_get($id, 'idpes')) {
                                                        $alunoParts[] = 'idpes '.data_get($id, 'idpes');
                                                    }
                                                    if (array_key_exists('presente', $p)) {
                                                        $alunoParts[] = ($p['presente'] ? 'Presente' : 'Ausente');
                                                    }
                                                    if ($f = data_get($p, 'fonte')) {
                                                        $alunoParts[] = 'fonte '.$f;
                                                    }
                                                    $alunoDetail = implode(' · ', $alunoParts);
                                                } else {
                                                    $regs = data_get($p, 'registros', []) ?: [];
                                                    $n = count($regs);
                                                    $resumoPrimary = 'Lote: '.$n.' registro(s)';
                                                    $uniqCods = collect($regs)->pluck('cod_aluno')->filter()->unique()->values();
                                                    $show = $uniqCods->take(8);
                                                    $tail = $uniqCods->count() - $show->count();
                                                    $alunoDetail = $uniqCods->isEmpty()
                                                        ? '—'
                                                        : ('cod_aluno: '.$show->implode(', ').($tail > 0 ? ' (+ '.$tail.' distintos)' : ''));
                                                    $presOk = collect($regs)->filter(fn ($row) => is_array($row) && (data_get($row, 'presente') === true || data_get($row, 'presente') === 1))->count();
                                                    $presNo = collect($regs)->filter(fn ($row) => is_array($row) && (data_get($row, 'presente') === false || data_get($row, 'presente') === 0))->count();
                                                    if ($presOk + $presNo > 0) {
                                                        $alunoDetail .= ' · marcações: '.$presOk.' pres. / '.$presNo.' aus.';
                                                    }
                                                }
                                                $drRoot = data_get($p, 'data_ref');
                                                if ($drRoot !== null && $drRoot !== '') {
                                                    $dataRefLine = $fmtDr($drRoot);
                                                } else {
                                                    $refs = collect(data_get($p, 'registros', []) ?: [])
                                                        ->pluck('data_ref')
                                                        ->filter(fn ($x) => $x !== null && $x !== '')
                                                        ->unique()
                                                        ->values();
                                                    if ($refs->isEmpty()) {
                                                        $dataRefLine = '—';
                                                    } else {
                                                        $dataRefLine = $refs->take(4)->map(fn ($r) => $fmtDr($r))->implode(' · ');
                                                        if ($refs->count() > 4) {
                                                            $dataRefLine .= ' · +'.($refs->count() - 4).' datas';
                                                        }
                                                    }
                                                }
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="fac-idcell">
                                                        <span class="fac-idcell__badge" aria-hidden="true">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/></svg>
                                                        </span>
                                                        <div class="fac-stack" style="min-width:0;">
                                                            <span class="mono" style="font-weight:700;">#{{ $it->id }}</span>
                                                            <span class="mono fac-muted">tent. {{ (int) $it->attempts }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if ($it->mode === \App\Models\IeducarFrequenciaRegistroDelivery::MODE_PREVIEW)
                                                        <span class="fac-badge fac-badge--info">preview</span>
                                                    @else
                                                        <span class="fac-badge fac-badge--neutral">apply</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($it->status === \App\Models\IeducarFrequenciaRegistroDelivery::STATUS_COMPLETED)
                                                        <span class="fac-badge fac-badge--success">concluído</span>
                                                    @elseif ($it->status === \App\Models\IeducarFrequenciaRegistroDelivery::STATUS_FAILED)
                                                        <span class="fac-badge fac-badge--danger">falhou</span>
                                                    @elseif ($it->status === \App\Models\IeducarFrequenciaRegistroDelivery::STATUS_PROCESSING)
                                                        <span class="fac-badge fac-badge--info">processando</span>
                                                    @else
                                                        <span class="fac-badge fac-badge--warn">pendente</span>
                                                    @endif
                                                </td>
                                                <td><span class="mono">{{ $it->http_status ?? '—' }}</span></td>
                                                <td>
                                                    <div class="fac-stack">
                                                        <span class="mono fac-resumo-primary">{{ $resumoPrimary }}</span>
                                                        <span class="mono fac-table__aluno" title="Dados do aluno / lote (payload)">{{ $alunoDetail }}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fac-stack">
                                                        <span class="mono" style="font-size:11px;">{{ $it->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span>
                                                        <span class="mono fac-table__dataref" title="data_ref (payload), fuso {{ \App\Support\DateDisplay::timezoneLabel() }}">{{ $dataRefLine }}</span>
                                                    </div>
                                                </td>
                                                <td style="text-align: right;">
                                                    <div class="fac-actions" style="justify-content: flex-end;">
                                                        @if ($it->status === \App\Models\IeducarFrequenciaRegistroDelivery::STATUS_PENDING)
                                                            <form method="POST" action="{{ route('integrations.ieducar.frequencia-registro.force-send', ['id' => $it->id]) }}" style="margin: 0;">
                                                                @csrf
                                                                <button type="submit" class="fac-btn-ico" style="border-color: color-mix(in srgb, var(--fac-ok) 40%, var(--border)); background: color-mix(in srgb, var(--fac-ok) 10%, var(--surface-1)); color: color-mix(in srgb, var(--text) 82%, var(--fac-ok));" title="Forçar envio agora" aria-label="Forçar envio entrega {{ $it->id }}">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                                                                </button>
                                                            </form>
                                                        @endif
                                                        <a class="fac-btn-ico fac-btn-ico--primary" href="{{ route('admin.ieducar-frequencia-deliveries.show', ['id' => $it->id]) }}" title="Detalhe" aria-label="Detalhe entrega {{ $it->id }}">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="fac-muted" style="padding: 22px;">Nenhum item na fila ainda. Use <a href="{{ route('integrations.ieducar.frequencia-registro') }}">Integrações → frequência</a> para enfileirar.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                                </div>
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
