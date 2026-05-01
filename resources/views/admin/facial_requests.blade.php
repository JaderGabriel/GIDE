<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Admin • Solicitações de facial • {{ config('app.name', 'Bridge ERP') }}</title>

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
            .fac-admin__lead { margin: 6px 0 0; font-size: 14px; color: var(--muted); max-width: 640px; line-height: 1.5; }

            .fac-alert { margin-top: 14px; padding: 12px 14px; border-radius: 14px; display: flex; align-items: flex-start; gap: 10px; border: 1px solid color-mix(in srgb, var(--accent-c) 35%, var(--border)); background: color-mix(in srgb, var(--accent-c) 8%, var(--surface-1)); font-size: 14px; }
            .fac-alert svg { flex: 0 0 20px; margin-top: 1px; color: var(--accent-c); }

            .fac-health { margin-top: 14px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
            .fac-health__label { font-size: 12px; font-weight: 650; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; width: 100%; }
            .fac-chip { display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; font-size: 13px; font-weight: 600; border: 1px solid var(--border); background: var(--surface-1); }
            .fac-chip svg { width: 18px; height: 18px; flex-shrink: 0; }
            .fac-chip--ok { border-color: color-mix(in srgb, var(--fac-ok) 40%, var(--border)); background: var(--fac-ok-bg); color: color-mix(in srgb, var(--text) 88%, var(--fac-ok)); }
            .fac-chip--bad { border-color: color-mix(in srgb, var(--fac-bad) 45%, var(--border)); background: var(--fac-bad-bg); color: var(--fac-bad); }
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

            .fac-table-wrap { margin-top: 18px; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; border: 1px solid var(--border); border-radius: 18px; background: var(--card-strong); box-shadow: var(--shadow-soft); }
            .fac-table { width: 100%; border-collapse: collapse; min-width: 1020px; table-layout: fixed; }
            .fac-table th, .fac-table td { border-bottom: 1px solid var(--border); padding: 12px 12px; vertical-align: top; text-align: left; }
            .fac-table th { font-size: 11px; color: var(--muted); font-weight: 700; text-transform: uppercase; letter-spacing: .05em; background: color-mix(in srgb, var(--surface-1) 88%, transparent); position: sticky; top: 0; z-index: 1; }
            .fac-table th .th-inner { display: inline-flex; align-items: center; gap: 6px; }
            .fac-table th svg { width: 14px; height: 14px; opacity: .9; }
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

            .fac-actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
            .fac-btn-ico { appearance: none; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; padding: 0; border-radius: 12px; border: 1px solid var(--border); background: var(--surface-1); color: var(--text); cursor: pointer; text-decoration: none; transition: background .12s ease, border-color .12s ease, transform .08s ease; }
            .fac-btn-ico:hover { background: color-mix(in srgb, var(--bg0) 80%, transparent); border-color: color-mix(in srgb, var(--accent-a) 28%, var(--border)); text-decoration: none; }
            .fac-btn-ico:active { transform: translateY(1px); }
            .fac-btn-ico svg { width: 18px; height: 18px; }
            .fac-btn-ico--primary { border-color: color-mix(in srgb, var(--accent-a) 35%, var(--border)); background: color-mix(in srgb, var(--accent-a) 10%, var(--surface-1)); color: color-mix(in srgb, var(--text) 75%, var(--accent-a)); }
            .fac-muted { font-size: 12px; color: var(--muted); margin-top: 4px; line-height: 1.4; }
            .fac-stack { display: flex; flex-direction: column; gap: 4px; }
            details.fac-details { margin-top: 8px; }
            details.fac-details summary { cursor: pointer; font-size: 11px; font-weight: 650; color: var(--accent-a); user-select: none; }
            details.fac-details pre { margin: 8px 0 0; padding: 10px; border-radius: 12px; border: 1px solid var(--border); background: color-mix(in srgb, var(--bg0) 70%, transparent); max-height: 200px; overflow: auto; }
        </style>
    </head>
    <body>
        <div class="bridge-shell fac-admin">
            <header class="bridge-header">
                <div class="bridge-container">
                    <div class="bridge-header__inner">
                        <a class="bridge-brand" href="/dashboard">
                            <img src="/favicon.svg" alt="" class="bridge-brand__logo" />
                            <div class="bridge-brand__text">
                                <div class="bridge-brand__name">{{ config('app.name', 'Bridge ERP') }}</div>
                                <div class="bridge-brand__tagline">Admin • Solicitações de facial</div>
                            </div>
                        </a>
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
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a4 4 0 0 1 4 4v2a4 4 0 0 1-8 0V6a4 4 0 0 1 4-4z"/><path d="M6 22v-2a6 6 0 0 1 12 0v2"/></svg>
                                        </div>
                                        <div>
                                            <h1 class="fac-admin__h1">Solicitações faciais</h1>
                                            <p class="fac-admin__lead">Tokens do iEducar, tentativas de envio ao Gestor (catraca) e consultas de matrícula. Indicadores e cores refletem o estado operacional de cada etapa.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if (session('status'))
                                <div class="fac-alert" role="status">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                    <div><strong>{{ session('status') }}</strong></div>
                                </div>
                            @endif

                            <div class="fac-health">
                                <span class="fac-health__label">Integrações</span>
                                @if ($ieducarReady)
                                    <span class="fac-chip fac-chip--ok">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect width="32" height="32" rx="8" fill="#1b6b3a"/><path fill="#fff" d="M9 10h2.2v12H9V10zm4.5 0h3.5c3.2 0 5.1 1.8 5.1 4.7 0 2.9-1.9 4.6-5.1 4.6h-1.3V22h-2.2V10zm2.2 2v5.3h1.2c1.8 0 2.8-.8 2.8-2.6 0-1.8-1-2.7-2.8-2.7h-1.2z"/></svg>
                                        iEducar pronto
                                    </span>
                                @else
                                    <span class="fac-chip fac-chip--warn">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                        iEducar incompleto
                                    </span>
                                @endif
                                @if ($hasGestor)
                                    <span class="fac-chip fac-chip--ok">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                        Gestor registrado
                                    </span>
                                @else
                                    <span class="fac-chip fac-chip--bad">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                        Gestor ausente
                                    </span>
                                @endif
                            </div>

                            @php
                                $st = is_array($stats ?? null) ? $stats : [
                                    'total' => 0, 'tokens_usados' => 0, 'tokens_pendentes' => 0, 'catraca_ok' => 0, 'catraca_falha' => 0, 'catraca_sem_registro' => 0, 'com_snapshot_ieducar' => 0,
                                ];
                            @endphp
                            <div class="fac-kpis">
                                <div class="fac-kpi">
                                    <div class="fac-kpi__k">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        Total (lista)
                                    </div>
                                    <div class="fac-kpi__v">{{ (int) $st['total'] }}</div>
                                </div>
                                <div class="fac-kpi">
                                    <div class="fac-kpi__k">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                        Token usado
                                    </div>
                                    <div class="fac-kpi__v fac-kpi__v--ok">{{ (int) $st['tokens_usados'] }}</div>
                                </div>
                                <div class="fac-kpi">
                                    <div class="fac-kpi__k">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        Pendente uso
                                    </div>
                                    <div class="fac-kpi__v fac-kpi__v--warn">{{ (int) $st['tokens_pendentes'] }}</div>
                                </div>
                                <div class="fac-kpi">
                                    <div class="fac-kpi__k">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                        Catraca OK
                                    </div>
                                    <div class="fac-kpi__v fac-kpi__v--ok">{{ (int) $st['catraca_ok'] }}</div>
                                </div>
                                <div class="fac-kpi">
                                    <div class="fac-kpi__k">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                        Catraca falha
                                    </div>
                                    <div class="fac-kpi__v fac-kpi__v--bad">{{ (int) $st['catraca_falha'] }}</div>
                                </div>
                                <div class="fac-kpi">
                                    <div class="fac-kpi__k">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                                        Snapshot iEducar
                                    </div>
                                    <div class="fac-kpi__v fac-kpi__v--info">{{ (int) $st['com_snapshot_ieducar'] }}</div>
                                </div>
                            </div>

                            <div class="fac-table-wrap">
                                <table class="fac-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 14%;">
                                                <span class="th-inner"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg> ID / evento</span>
                                            </th>
                                            <th style="width: 17%;">
                                                <span class="th-inner"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> Aluno</span>
                                            </th>
                                            <th style="width: 16%;">
                                                <span class="th-inner"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Token</span>
                                            </th>
                                            <th style="width: 11%;">
                                                <span class="th-inner"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Uso</span>
                                            </th>
                                            <th style="width: 18%;">
                                                <span class="th-inner"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Catraca (Gestor)</span>
                                            </th>
                                            <th style="width: 16%;">
                                                <span class="th-inner"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect width="32" height="32" rx="6" fill="currentColor" opacity=".25"/><path fill="currentColor" d="M9 10h2.2v12H9V10zm4.5 0h3.5c3.2 0 5.1 1.8 5.1 4.7 0 2.9-1.9 4.6-5.1 4.6h-1.3V22h-2.2V10z"/></svg> Matrícula (iEducar)</span>
                                            </th>
                                            <th style="width: 8%; text-align: right;">
                                                <span class="th-inner" style="justify-content: flex-end; width: 100%;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg></span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($items as $it)
                                            @php
                                                $payload = is_array($it->payload) ? $it->payload : [];
                                                $codAluno = data_get($payload, 'aluno_id');
                                                $idpes = data_get($payload, 'idpes');
                                                $matricula = data_get($payload, 'matricula_id');
                                                $externalId = data_get($payload, 'external_id');
                                                $attempts = ($attemptsByRequest[$it->id] ?? collect());
                                                $attempt = $attempts->first();
                                                $attemptCount = $attempts->count();
                                                $snap = ($statusByRequest[$it->id] ?? collect())->first();
                                                $expired = $it->expires_at && $it->expires_at->isPast();
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="fac-idcell">
                                                        <span class="fac-idcell__badge" aria-hidden="true">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                                        </span>
                                                        <div class="fac-stack" style="min-width:0;">
                                                            <span class="mono" style="font-weight:700;">#{{ $it->id }}</span>
                                                            <span class="mono clip" title="{{ $it->event_id }}">{{ $it->event_id }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fac-stack">
                                                        <span class="mono">cod_aluno: <strong>{{ $codAluno ?? '—' }}</strong></span>
                                                        <span class="mono">idpes: <strong>{{ $idpes ?? '—' }}</strong></span>
                                                        <span class="mono clip" title="{{ $matricula }}">mat.: {{ $matricula ?? '—' }}</span>
                                                        <span class="mono clip" title="{{ $externalId }}">ext.: {{ $externalId ?? '—' }}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="mono clip" title="{{ $it->token }}">{{ $it->token }}</div>
                                                    <div class="fac-badge-row" style="margin-top:8px;">
                                                        @if ($expired)
                                                            <span class="fac-badge fac-badge--warn">
                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                                                Expirado
                                                            </span>
                                                        @else
                                                            <span class="fac-badge fac-badge--info">
                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                                                Válido
                                                            </span>
                                                        @endif
                                                        <span class="fac-badge fac-badge--neutral">{{ $it->expires_at ? \App\Support\DateDisplay::formatHuman($it->expires_at, true) : '?' }}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if ($it->used_at)
                                                        <span class="fac-badge fac-badge--success">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                                            Usado
                                                        </span>
                                                        <div class="mono fac-muted">{{ \App\Support\DateDisplay::formatHuman($it->used_at, true) }}</div>
                                                    @else
                                                        <span class="fac-badge fac-badge--neutral">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                                                            Não usado
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($attempt)
                                                        <div class="fac-badge-row">
                                                            @if ($attempt->ok)
                                                                <span class="fac-badge fac-badge--success">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                                                    OK
                                                                </span>
                                                            @else
                                                                <span class="fac-badge fac-badge--danger">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                                                    Falha
                                                                </span>
                                                            @endif
                                                            <span class="fac-badge fac-badge--neutral">HTTP {{ $attempt->http_status ?? '—' }}</span>
                                                            <span class="fac-badge fac-badge--neutral">{{ $attemptCount }} tent.</span>
                                                        </div>
                                                        @if ($attempt->error_message)
                                                            <div class="mono wrap fac-muted" style="color: color-mix(in srgb, var(--fac-bad) 85%, var(--text));">{{ $attempt->error_message }}</div>
                                                        @endif
                                                        @if ($attempt->response_body)
                                                            <details class="fac-details">
                                                                <summary>Resposta bruta</summary>
                                                                <pre class="mono wrap">{{ $attempt->response_body }}</pre>
                                                            </details>
                                                        @endif
                                                    @else
                                                        <span class="fac-badge fac-badge--warn">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                                            Sem registro
                                                        </span>
                                                        <div class="fac-muted">Nenhuma tentativa na catraca.</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($snap)
                                                        <div class="fac-badge-row">
                                                            @if ($snap->error_message)
                                                                <span class="fac-badge fac-badge--danger">Erro</span>
                                                            @elseif (is_numeric($snap->http_status) && (int) $snap->http_status >= 200 && (int) $snap->http_status < 300)
                                                                <span class="fac-badge fac-badge--success">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                                                    HTTP {{ $snap->http_status }}
                                                                </span>
                                                            @else
                                                                <span class="fac-badge fac-badge--warn">HTTP {{ $snap->http_status ?? '—' }}</span>
                                                            @endif
                                                            <span class="fac-badge fac-badge--neutral">{{ $snap->fetched_at ? \App\Support\DateDisplay::formatHuman($snap->fetched_at, true) : '' }}</span>
                                                        </div>
                                                        @if ($snap->error_message)
                                                            <div class="mono wrap fac-muted" style="color: color-mix(in srgb, var(--fac-bad) 85%, var(--text));">{{ $snap->error_message }}</div>
                                                        @endif
                                                        @if (is_array($snap->response_json))
                                                            @php
                                                                $situacao = data_get($snap->response_json, 'status.matricula.situacao_descricao');
                                                                $ano = data_get($snap->response_json, 'status.matricula.ano');
                                                            @endphp
                                                            @if ($situacao || $ano)
                                                                <div class="mono fac-muted" style="margin-top:6px;">{{ $situacao }}{{ $ano ? ' • ano '.$ano : '' }}</div>
                                                            @endif
                                                            <details class="fac-details">
                                                                <summary>JSON iEducar</summary>
                                                                <pre class="mono wrap">{{ json_encode($snap->response_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                                            </details>
                                                        @endif
                                                    @else
                                                        <span class="fac-badge fac-badge--neutral">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                                            Sem snapshot
                                                        </span>
                                                    @endif
                                                </td>
                                                <td style="text-align: right;">
                                                    <div class="fac-actions" style="justify-content: flex-end;">
                                                        <form method="POST" action="{{ route('admin.facial-requests.refresh-status', ['id' => $it->id]) }}" style="margin:0;">
                                                            @csrf
                                                            <button type="submit" class="fac-btn-ico fac-btn-ico--primary" title="Atualizar status no iEducar" aria-label="Atualizar status no iEducar">
                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                                                            </button>
                                                        </form>
                                                        <a class="fac-btn-ico" href="{{ route('admin.facial-requests.show', ['id' => $it->id]) }}" title="Ver detalhes" aria-label="Ver detalhes da solicitação">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                                        </a>
                                                        <a class="fac-btn-ico" href="{{ url('/facial/enviar?token='.urlencode($it->token)) }}" target="_blank" rel="noreferrer" title="Abrir tela de envio" aria-label="Abrir tela de envio facial">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
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
