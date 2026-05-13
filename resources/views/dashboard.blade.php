<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Dashboard • {{ config('app.name', 'Bridge ERP') }}</title>

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
            .bridge-container { max-width: 1240px; }

            .dash-grid { display: grid; gap: 16px; }
            @media (min-width: 1040px) { .dash-grid { grid-template-columns: 1.35fr 0.65fr; align-items: start; } }

            .dash-card { border: 1px solid var(--border); border-radius: 18px; background: var(--surface-1); padding: 16px; }
            .dash-card--accent {
                background:
                    radial-gradient(1100px 420px at 10% 0%, color-mix(in srgb, var(--accent-a) 16%, transparent), transparent 60%),
                    radial-gradient(900px 380px at 100% 20%, color-mix(in srgb, var(--accent-c) 10%, transparent), transparent 60%),
                    var(--surface-1);
            }

            .dash-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
            .dash-head__left { display: flex; align-items: center; gap: 10px; min-width: 0; }
            .dash-ico {
                width: 34px; height: 34px; border-radius: 12px;
                display: grid; place-items: center;
                border: 1px solid var(--border);
                background: var(--surface-2);
                color: var(--text);
                flex: 0 0 auto;
            }
            .dash-ico svg { width: 18px; height: 18px; }
            .dash-badge { display: inline-flex; align-items: center; gap: 8px; padding: 4px 10px; border-radius: 999px; border: 1px solid var(--border); background: var(--surface-2); color: var(--muted); font-size: 12px; white-space: nowrap; }
            .dash-dot { width: 8px; height: 8px; border-radius: 999px; background: var(--accent-a); box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent-a) 18%, transparent); }
            .dash-topbar { margin-top: 14px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
            .dash-topbar__chips { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
            .dash-chip--strong { color: color-mix(in srgb, var(--text) 88%, var(--accent-a)); border-color: color-mix(in srgb, var(--accent-a) 35%, var(--border)); background: color-mix(in srgb, var(--accent-a) 10%, var(--surface-2)); }
            .dash-chip--warn { color: color-mix(in srgb, var(--text) 86%, #d97706); border-color: color-mix(in srgb, #d97706 35%, var(--border)); background: color-mix(in srgb, #d97706 10%, var(--surface-2)); }

            .dash-flow-figure {
                margin: 12px 0 0;
                padding: 16px 14px 14px;
                border: 1px solid color-mix(in srgb, var(--border) 92%, var(--text));
                border-radius: 14px;
                background:
                    radial-gradient(520px 200px at 22% 0%, color-mix(in srgb, var(--accent-a) 12%, transparent), transparent 62%),
                    radial-gradient(420px 180px at 96% 100%, color-mix(in srgb, var(--accent-c) 10%, transparent), transparent 58%),
                    var(--surface-1);
                box-shadow: 0 1px 0 color-mix(in srgb, var(--border) 55%, transparent);
            }
            html.dark .dash-flow-figure {
                background:
                    radial-gradient(520px 220px at 20% 8%, color-mix(in srgb, var(--accent-a) 18%, transparent), transparent 60%),
                    radial-gradient(460px 200px at 88% 92%, color-mix(in srgb, #0ea5e9 14%, transparent), transparent 55%),
                    radial-gradient(380px 160px at 50% 50%, color-mix(in srgb, var(--muted) 8%, transparent), transparent 70%),
                    color-mix(in srgb, var(--surface-2) 78%, var(--surface-1));
                box-shadow: inset 0 1px 0 color-mix(in srgb, var(--border) 35%, transparent);
            }
            .dash-flow-figure__svg {
                width: 100%;
                max-width: 560px;
                height: auto;
                display: block;
                margin: 0 auto;
            }
            .dash-flow-figure__svg .dash-fg-box {
                fill: color-mix(in srgb, var(--surface-1) 96%, var(--surface-2));
                stroke: color-mix(in srgb, var(--border) 88%, var(--muted));
                stroke-width: 1;
            }
            .dash-flow-figure__svg .dash-fg-box--hub {
                stroke: color-mix(in srgb, var(--border) 72%, var(--text));
                fill: color-mix(in srgb, var(--surface-2) 88%, var(--surface-1));
            }
            .dash-flow-figure__svg .dash-fg-box--sms {
                stroke: color-mix(in srgb, var(--border) 85%, var(--muted));
                fill: color-mix(in srgb, var(--surface-1) 94%, var(--surface-2));
            }
            .dash-flow-figure__svg .dash-fg-line {
                fill: none;
                stroke: color-mix(in srgb, var(--muted) 55%, var(--border));
                stroke-width: 1.25;
                stroke-linecap: round;
                stroke-linejoin: round;
            }
            .dash-flow-figure__svg .dash-fg-line--main {
                stroke: color-mix(in srgb, var(--muted) 40%, var(--text));
                stroke-width: 1.35;
            }
            .dash-flow-figure__svg .dash-fg-line--notify {
                stroke: color-mix(in srgb, var(--muted) 35%, var(--text));
                stroke-width: 1.2;
                stroke-dasharray: 4 4;
                opacity: 0.88;
            }
            .dash-flow-figure__svg .dash-fg-line--return {
                stroke: color-mix(in srgb, var(--muted) 48%, var(--border));
                stroke-width: 1.15;
                stroke-dasharray: 5 4;
                opacity: 0.9;
            }
            .dash-flow-figure__svg .dash-fg-line--freq-return {
                stroke: color-mix(in srgb, #059669 42%, var(--muted));
                stroke-width: 1.3;
                stroke-dasharray: 5 3.5;
                opacity: 0.94;
            }
            html.dark .dash-flow-figure__svg .dash-fg-line--freq-return {
                stroke: color-mix(in srgb, #34d399 48%, var(--muted));
            }
            .dash-flow-figure__svg .dash-fg-head {
                font-size: 11.5px;
                font-weight: 700;
                fill: color-mix(in srgb, var(--text) 94%, var(--muted));
                letter-spacing: -0.015em;
            }
            .dash-flow-figure__svg .dash-fg-sub {
                font-size: 8.5px;
                font-weight: 600;
                fill: color-mix(in srgb, var(--muted) 92%, var(--text));
                letter-spacing: 0.06em;
                text-transform: uppercase;
            }
            .dash-flow-figure__svg .dash-fg-edge {
                font-size: 8px;
                font-weight: 600;
                fill: color-mix(in srgb, var(--muted) 95%, var(--text));
                letter-spacing: 0.05em;
                text-transform: uppercase;
            }
            .dash-flow-figure__svg .dash-fg-mrk {
                fill: color-mix(in srgb, var(--muted) 65%, var(--text));
            }
            html.dark .dash-flow-figure__svg .dash-fg-mrk {
                fill: color-mix(in srgb, var(--muted) 50%, var(--text));
            }
            .dash-flow-figure__cap {
                margin: 12px 0 0;
                text-align: center;
                font-size: 12px;
                line-height: 1.45;
                color: var(--muted);
            }

            .dash-flow { display: grid; gap: 12px; }
            .dash-node { border: 1px solid var(--border); border-radius: 16px; padding: 14px; background: var(--surface-2); }
            .dash-node--notify {
                border-left: 3px solid color-mix(in srgb, #0ea5e9 70%, var(--border));
                background: color-mix(in srgb, #0ea5e9 6%, var(--surface-2));
            }
            .dash-node__k { font-size: 12px; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); }
            .dash-node__title-row {
                display: flex;
                align-items: flex-start;
                gap: 10px;
                margin-top: 4px;
            }
            .dash-node__t { font-size: 14px; font-weight: 600; margin-top: 0; flex: 1; min-width: 0; line-height: 1.35; }
            .dash-node__status-hit {
                position: relative;
                flex: 0 0 auto;
                margin-top: 1px;
                padding: 5px;
                border-radius: 999px;
                border: 1px solid var(--border);
                background: color-mix(in srgb, var(--surface-1) 75%, transparent);
                cursor: help;
                line-height: 0;
            }
            .dash-node__status-hit:focus-visible {
                outline: 2px solid color-mix(in srgb, var(--accent-a) 42%, transparent);
                outline-offset: 2px;
            }
            .dash-node__status-dot {
                display: block;
                width: 10px;
                height: 10px;
                border-radius: 999px;
                box-shadow: 0 0 0 2px color-mix(in srgb, currentColor 20%, transparent);
            }
            .dash-node__status-hit--ok {
                border-color: color-mix(in srgb, #166534 38%, var(--border));
                background: color-mix(in srgb, #166534 11%, var(--surface-1));
            }
            .dash-node__status-hit--ok .dash-node__status-dot { background: #166534; color: #166534; }
            .dash-node__status-hit--warn {
                border-color: color-mix(in srgb, #d97706 42%, var(--border));
                background: color-mix(in srgb, #d97706 11%, var(--surface-1));
            }
            .dash-node__status-hit--warn .dash-node__status-dot { background: #d97706; color: #d97706; }
            .dash-node__status-hit--neutral .dash-node__status-dot {
                background: color-mix(in srgb, var(--muted) 50%, var(--border));
                color: var(--muted);
            }
            html.dark .dash-node__status-hit--ok {
                border-color: color-mix(in srgb, #22c55e 40%, var(--border));
                background: color-mix(in srgb, #22c55e 14%, var(--surface-1));
            }
            html.dark .dash-node__status-hit--ok .dash-node__status-dot { background: #22c55e; color: #22c55e; }
            html.dark .dash-node__status-hit--warn {
                border-color: color-mix(in srgb, #fbbf24 38%, var(--border));
                background: color-mix(in srgb, #fbbf24 12%, var(--surface-1));
            }
            html.dark .dash-node__status-hit--warn .dash-node__status-dot { background: #fbbf24; color: #fbbf24; }
            .dash-node__status-tip {
                position: absolute;
                left: 0;
                top: calc(100% + 10px);
                padding: 11px 13px;
                border-radius: 12px;
                border: 1px solid color-mix(in srgb, #0f172a 88%, #64748b);
                background: #0f172a;
                color: #f1f5f9;
                box-shadow:
                    0 0 0 1px color-mix(in srgb, #fff 8%, transparent),
                    0 16px 40px -8px rgba(15, 23, 42, 0.55);
                font-size: 12px;
                font-weight: 550;
                line-height: 1.5;
                letter-spacing: 0.01em;
                width: max-content;
                max-width: min(340px, 86vw);
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                transition: opacity 0.14s ease, visibility 0.14s ease;
                z-index: 40;
            }
            html.dark .dash-node__status-tip {
                border-color: #cbd5e1;
                background: #f8fafc;
                color: #0f172a;
                box-shadow:
                    0 0 0 1px color-mix(in srgb, #0f172a 12%, transparent),
                    0 16px 44px -6px rgba(0, 0, 0, 0.65);
            }
            .dash-node__status-hit:hover .dash-node__status-tip,
            .dash-node__status-hit:focus-visible .dash-node__status-tip {
                opacity: 1;
                visibility: visible;
            }
            .dash-node__d { margin-top: 8px; color: var(--muted); font-size: 13px; line-height: 1.35; }
            .dash-node__meta { margin-top: 10px; display: flex; flex-wrap: wrap; gap: 8px; }
            .dash-chip { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; border: 1px solid var(--border); background: color-mix(in oklab, var(--surface-1) 70%, transparent); font-size: 12px; color: var(--muted); }
            .dash-chip svg { width: 14px; height: 14px; }

            .dash-quick { display: flex; flex-direction: column; gap: 14px; margin-top: 4px; }
            .dash-quick__group {
                position: relative;
                border: 1px solid var(--border);
                border-radius: 16px;
                padding: 12px 12px 12px 16px;
                background: color-mix(in srgb, var(--surface-2) 92%, var(--surface-1));
            }
            .dash-quick__group::before {
                content: "";
                position: absolute;
                left: 0;
                top: 10px;
                bottom: 10px;
                width: 3px;
                border-radius: 0 4px 4px 0;
                background: var(--dash-group-line, var(--accent-a));
            }
            .dash-quick__group--hub { --dash-group-line: var(--accent-a); }
            .dash-quick__group--connect { --dash-group-line: var(--accent-c); }
            .dash-quick__group--audit { --dash-group-line: #d97706; }
            html.dark .dash-quick__group--audit { --dash-group-line: #fbbf24; }

            .dash-quick__label {
                margin: 0 0 10px 2px;
                font-size: 11px;
                font-weight: 750;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                color: var(--muted);
            }
            .dash-quick__btns { display: flex; flex-direction: column; gap: 8px; }

            .dash-qbtn.bridge-btn {
                justify-content: flex-start;
                white-space: nowrap;
                transition: background 0.14s ease, border-color 0.14s ease, box-shadow 0.14s ease, transform 0.08s ease;
            }
            .dash-qbtn:hover { text-decoration: none; }
            .dash-qbtn:active { transform: translateY(1px); }
            .dash-qbtn svg { width: 18px; height: 18px; flex-shrink: 0; }

            .dash-qbtn--hub {
                border-color: color-mix(in srgb, var(--accent-a) 42%, var(--border));
                background: color-mix(in srgb, var(--accent-a) 11%, var(--surface-1));
                color: color-mix(in srgb, var(--text) 88%, var(--accent-a));
                box-shadow: 0 0 0 1px color-mix(in srgb, var(--accent-a) 6%, transparent);
            }
            .dash-qbtn--hub:hover {
                background: color-mix(in srgb, var(--accent-a) 18%, var(--surface-1));
                border-color: color-mix(in srgb, var(--accent-a) 55%, var(--border));
            }

            .dash-qbtn--gidequeues {
                border-color: color-mix(in srgb, #7c2d12 36%, var(--border));
                background: color-mix(in srgb, #7c2d12 9%, var(--surface-1));
                color: color-mix(in srgb, var(--text) 88%, #7c2d12);
                box-shadow: 0 0 0 1px color-mix(in srgb, #7c2d12 6%, transparent);
            }
            .dash-qbtn--gidequeues:hover {
                background: color-mix(in srgb, #7c2d12 14%, var(--surface-1));
                border-color: color-mix(in srgb, #7c2d12 48%, var(--border));
            }

            .dash-qbtn--ieducar {
                border-color: color-mix(in srgb, #166534 45%, var(--border));
                background: color-mix(in srgb, #166534 10%, var(--surface-1));
                color: color-mix(in srgb, var(--text) 82%, #166534);
                box-shadow: 0 0 0 1px color-mix(in srgb, #166534 8%, transparent);
            }
            .dash-qbtn--ieducar:hover {
                background: color-mix(in srgb, #166534 16%, var(--surface-1));
                border-color: color-mix(in srgb, #166534 58%, var(--border));
            }

            .dash-qbtn--gestor {
                border-color: color-mix(in srgb, #4f46e5 42%, var(--border));
                background: color-mix(in srgb, #4f46e5 10%, var(--surface-1));
                color: color-mix(in srgb, var(--text) 85%, #4f46e5);
                box-shadow: 0 0 0 1px color-mix(in srgb, #4f46e5 7%, transparent);
            }
            .dash-qbtn--gestor:hover {
                background: color-mix(in srgb, #4f46e5 16%, var(--surface-1));
                border-color: color-mix(in srgb, #4f46e5 55%, var(--border));
            }

            .dash-qbtn--sms {
                border-color: color-mix(in srgb, #0284c7 42%, var(--border));
                background: color-mix(in srgb, #0284c7 10%, var(--surface-1));
                color: color-mix(in srgb, var(--text) 85%, #0284c7);
                box-shadow: 0 0 0 1px color-mix(in srgb, #0284c7 7%, transparent);
            }
            .dash-qbtn--sms:hover {
                background: color-mix(in srgb, #0284c7 16%, var(--surface-1));
                border-color: color-mix(in srgb, #0284c7 55%, var(--border));
            }

            .dash-qbtn--facial {
                border-color: color-mix(in srgb, #c2410c 38%, var(--border));
                background: color-mix(in srgb, #c2410c 9%, var(--surface-1));
                color: color-mix(in srgb, var(--text) 88%, #c2410c);
                box-shadow: 0 0 0 1px color-mix(in srgb, #c2410c 6%, transparent);
            }
            .dash-qbtn--facial:hover {
                background: color-mix(in srgb, #c2410c 14%, var(--surface-1));
                border-color: color-mix(in srgb, #c2410c 50%, var(--border));
            }

            .dash-qbtn--smslog {
                border-color: color-mix(in srgb, #0d9488 40%, var(--border));
                background: color-mix(in srgb, #0d9488 9%, var(--surface-1));
                color: color-mix(in srgb, var(--text) 86%, #0d9488);
                box-shadow: 0 0 0 1px color-mix(in srgb, #0d9488 6%, transparent);
            }
            .dash-qbtn--smslog:hover {
                background: color-mix(in srgb, #0d9488 15%, var(--surface-1));
                border-color: color-mix(in srgb, #0d9488 52%, var(--border));
            }

            .dash-qbtn--freq {
                border-color: color-mix(in srgb, #7c3aed 40%, var(--border));
                background: color-mix(in srgb, #7c3aed 10%, var(--surface-1));
                color: color-mix(in srgb, var(--text) 85%, #7c3aed);
                box-shadow: 0 0 0 1px color-mix(in srgb, #7c3aed 7%, transparent);
            }
            .dash-qbtn--freq:hover {
                background: color-mix(in srgb, #7c3aed 16%, var(--surface-1));
                border-color: color-mix(in srgb, #7c3aed 52%, var(--border));
            }

            .dash-qbtn--useraudit {
                border-color: color-mix(in srgb, #b45309 38%, var(--border));
                background: color-mix(in srgb, #b45309 9%, var(--surface-1));
                color: color-mix(in srgb, var(--text) 88%, #b45309);
                box-shadow: 0 0 0 1px color-mix(in srgb, #b45309 6%, transparent);
            }
            .dash-qbtn--useraudit:hover {
                background: color-mix(in srgb, #b45309 14%, var(--surface-1));
                border-color: color-mix(in srgb, #b45309 48%, var(--border));
            }
        </style>
    </head>
    <body>
        <div class="bridge-shell">
            <header class="bridge-header">
                <div class="bridge-container">
                    <div class="bridge-header__inner">
                        <a class="bridge-brand" href="{{ url('/dashboard') }}">
                            <img src="/favicon.svg" alt="" class="bridge-brand__logo" />
                            <div class="bridge-brand__text">
                                <div class="bridge-brand__name">{{ config('app.name', 'Bridge ERP') }}</div>
                                <div class="bridge-brand__tagline">Bridge de integração entre ERPs</div>
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
                                <div class="bridge-panel__title">Dashboard</div>
                                <div class="bridge-panel__meta">área autenticada</div>
                            </div>

                            <div class="bridge-dashboard__body">
                                @if (session('status'))
                                    <p class="bridge-muted" style="margin-bottom: 12px;">
                                        <strong>{{ session('status') }}</strong>
                                    </p>
                                @endif

                                <p class="bridge-muted">
                                    Você está logado como <strong>{{ auth()->user()->username ?? auth()->user()->email }}</strong>.
                                </p>
                                <p class="bridge-muted" style="margin-top: 8px;">
                                    Este dashboard resume o <strong>fluxo ponta‑a‑ponta</strong> e dá acesso rápido às configurações e auditoria.
                                </p>

                                <div class="dash-topbar">
                                    <div class="dash-topbar__chips">
                                        <span class="dash-chip dash-chip--strong" title="Ambiente da aplicação">
                                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2v4M12 18v4M4.9 4.9l2.8 2.8M16.3 16.3l2.8 2.8M2 12h4M18 12h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                            env: <span class="mono">{{ config('app.env') }}</span>
                                        </span>
                                        <span class="dash-chip" title="Timezone em uso na UI">
                                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/></svg>
                                            {{ \App\Support\DateDisplay::timezoneLabel() }}
                                        </span>
                                        @php $qc = (string) config('queue.default'); @endphp
                                        <span class="dash-chip {{ $qc !== 'sync' ? 'dash-chip--strong' : 'dash-chip--warn' }}" title="Driver de fila (impacta processamento assíncrono)">
                                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 7h10M7 12h10M7 17h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M5 3h14a2 2 0 0 1 2 2v14l-4-2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                                            fila: <span class="mono">{{ $qc }}</span>
                                        </span>
                                    </div>
                                </div>

                                <div class="dash-grid" style="margin-top: 14px;">
                                    <section class="dash-card dash-card--accent">
                                        <div class="dash-head">
                                            <div class="dash-head__left">
                                                <div class="dash-ico" aria-hidden="true">
                                                    <svg viewBox="0 0 24 24" fill="none">
                                                        <path d="M7 7h10M7 12h10M7 17h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        <path d="M5 3h14a2 2 0 0 1 2 2v14l-4-2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                                    </svg>
                                                </div>
                                                <div style="min-width: 0;">
                                                    <div class="bridge-panel__title">Fluxo de dados</div>
                                                    <div class="bridge-panel__meta">iEducar ↔ GIDE ↔ Catraca · volta frequência · GIDE → notify</div>
                                                </div>
                                            </div>
                                            <div class="dash-badge" title="Visão geral do fluxo">
                                                <span class="dash-dot" aria-hidden="true"></span>
                                                ponta‑a‑ponta
                                            </div>
                                        </div>

                                        <figure class="dash-flow-figure" aria-labelledby="dash-flow-figcap">
                                            <svg class="dash-flow-figure__svg" viewBox="0 0 520 172" xmlns="http://www.w3.org/2000/svg" role="presentation" focusable="false" aria-hidden="true">
                                                <defs>
                                                    <marker id="dash-fg-arr" markerWidth="5" markerHeight="5" refX="4" refY="2.5" orient="auto" markerUnits="strokeWidth">
                                                        <polygon class="dash-fg-mrk" points="0 0, 5 2.5, 0 5" />
                                                    </marker>
                                                </defs>

                                                <rect class="dash-fg-box" x="16" y="38" width="96" height="40" rx="8" />
                                                <text class="dash-fg-head" x="64" y="60" text-anchor="middle">iEducar</text>
                                                <text class="dash-fg-sub" x="64" y="72" text-anchor="middle">ERP</text>

                                                <rect class="dash-fg-box dash-fg-box--hub" x="136" y="34" width="88" height="48" rx="9" />
                                                <text class="dash-fg-head" x="180" y="58" text-anchor="middle">GIDE</text>
                                                <text class="dash-fg-sub" x="180" y="70" text-anchor="middle">ponte</text>

                                                <rect class="dash-fg-box" x="252" y="38" width="104" height="40" rx="8" />
                                                <text class="dash-fg-head" x="304" y="60" text-anchor="middle">Catraca</text>
                                                <text class="dash-fg-sub" x="304" y="72" text-anchor="middle">Gestor</text>

                                                <text class="dash-fg-edge" x="124" y="30" text-anchor="middle">inbound</text>
                                                <line class="dash-fg-line dash-fg-line--main" x1="112" y1="58" x2="134" y2="58" marker-end="url(#dash-fg-arr)" />

                                                <text class="dash-fg-edge" x="238" y="30" text-anchor="middle">enroll</text>
                                                <line class="dash-fg-line dash-fg-line--main" x1="224" y1="58" x2="250" y2="58" marker-end="url(#dash-fg-arr)" />

                                                <rect class="dash-fg-box dash-fg-box--sms" x="378" y="96" width="72" height="36" rx="8" />
                                                <text class="dash-fg-head" x="414" y="116" text-anchor="middle">SMS</text>
                                                <text class="dash-fg-sub" x="414" y="126" text-anchor="middle">notify</text>
                                                <text class="dash-fg-edge" x="330" y="88" text-anchor="middle">paralelo</text>
                                                <path class="dash-fg-line dash-fg-line--notify" d="M 202 82 L 202 90 L 414 90 L 414 96" fill="none" marker-end="url(#dash-fg-arr)" />

                                                <text class="dash-fg-edge" x="312" y="98" text-anchor="middle">passagens</text>
                                                <path class="dash-fg-line dash-fg-line--freq-return" d="M 304 78 L 304 108 L 200 108 L 200 82 L 158 82 L 158 126 L 64 126 L 64 78" fill="none" marker-end="url(#dash-fg-arr)" />
                                                <text class="dash-fg-edge" x="132" y="140" text-anchor="middle">frequência iEducar</text>
                                                <text class="dash-fg-edge" x="132" y="152" text-anchor="middle">· confirmação</text>
                                            </svg>
                                            <figcaption id="dash-flow-figcap" class="dash-flow-figure__cap">
                                                Tronco: iEducar → GIDE → catraca (enroll). <strong>Volta em verde tracejado</strong>: passagens na catraca → GIDE (webhook / fila) → API de <strong>frequência</strong> no iEducar; o mesmo conector serve <strong>confirmação facial</strong> e consultas. Ramo paralelo ao SMS a partir do GIDE.
                                            </figcaption>
                                        </figure>

                                        <div class="dash-flow" style="margin-top: 12px;">
                                            <div class="dash-node">
                                                @php
                                                    $s = $dashFlowLanes['ieducar_in'] ?? ['tone' => 'neutral', 'label' => '—', 'hint' => ''];
                                                    $hint = $s['hint'] ?? $s['label'];
                                                @endphp
                                                <div class="dash-node__k">iEducar → GIDE</div>
                                                <div class="dash-node__title-row">
                                                    <span class="dash-node__status-hit dash-node__status-hit--{{ $s['tone'] }}" tabindex="0" aria-label="{{ $s['label'] }}: {{ $hint }}">
                                                        <span class="dash-node__status-dot" aria-hidden="true"></span>
                                                        <span class="dash-node__status-tip" role="tooltip">{{ $hint }}</span>
                                                    </span>
                                                    <div class="dash-node__t">Solicitações de facial + eventos/matrículas</div>
                                                </div>
                                                <div class="dash-node__d">
                                                    O iEducar chama as APIs inbound do GIDE e recebe a <strong>URL com token</strong> para abrir a coleta.
                                                </div>
                                                <div class="dash-node__meta">
                                                    <span class="dash-chip">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M4 12h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                            <path d="M14 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                        inbound
                                                    </span>
                                                    <span class="dash-chip">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M12 2v4M12 18v4M4.9 4.9l2.8 2.8M16.3 16.3l2.8 2.8M2 12h4M18 12h4M4.9 19.1l2.8-2.8M16.3 7.7l2.8-2.8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        </svg>
                                                        HMAC/Bearer
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="dash-node">
                                                @php
                                                    $s = $dashFlowLanes['gestor'] ?? ['tone' => 'neutral', 'label' => '—', 'hint' => ''];
                                                    $hint = $s['hint'] ?? $s['label'];
                                                @endphp
                                                <div class="dash-node__k">GIDE → Catraca (Gestor)</div>
                                                <div class="dash-node__title-row">
                                                    <span class="dash-node__status-hit dash-node__status-hit--{{ $s['tone'] }}" tabindex="0" aria-label="{{ $s['label'] }}: {{ $hint }}">
                                                        <span class="dash-node__status-dot" aria-hidden="true"></span>
                                                        <span class="dash-node__status-tip" role="tooltip">{{ $hint }}</span>
                                                    </span>
                                                    <div class="dash-node__t">Coleta do rosto + envio para enroll</div>
                                                </div>
                                                <div class="dash-node__d">
                                                    A tela de coleta captura a foto na hora e envia ao GIDE, que encaminha ao sistema da catraca.
                                                </div>
                                                <div class="dash-node__meta">
                                                    <span class="dash-chip">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M4 7h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                            <path d="M7 4v16M17 4v16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        </svg>
                                                        câmera
                                                    </span>
                                                    <span class="dash-chip">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M4 6h16v12H4z" stroke="currentColor" stroke-width="2"/>
                                                            <path d="M7 9h10M7 12h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        </svg>
                                                        enroll
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="dash-node dash-node--notify">
                                                @php
                                                    $s = $dashFlowLanes['notify'] ?? ['tone' => 'neutral', 'label' => '—', 'hint' => ''];
                                                    $hint = $s['hint'] ?? $s['label'];
                                                @endphp
                                                <div class="dash-node__k">GIDE → notify</div>
                                                <div class="dash-node__title-row">
                                                    <span class="dash-node__status-hit dash-node__status-hit--{{ $s['tone'] }}" tabindex="0" aria-label="{{ $s['label'] }}: {{ $hint }}">
                                                        <span class="dash-node__status-dot" aria-hidden="true"></span>
                                                        <span class="dash-node__status-tip" role="tooltip">{{ $hint }}</span>
                                                    </span>
                                                    <div class="dash-node__t">SMS (e futuros canais) após presença</div>
                                                </div>
                                                <div class="dash-node__d">
                                                    Eventos de acesso vindos do <strong>Gestor</strong> chegam ao GIDE; quando a regra de presença dispara, o GIDE <strong>monta a mensagem</strong>, grava auditoria na fila e chama o provedor de SMS (ex.: Zenvia). O telefone vem do payload (responsável/aluno) ou, em modo testes, dos números configurados na integração — sem passar pela catraca de novo.
                                                </div>
                                                <div class="dash-node__meta">
                                                    <span class="dash-chip">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                                        </svg>
                                                        SMS
                                                    </span>
                                                    <span class="dash-chip">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M12 2v4M12 18v4M4.9 4.9l2.8 2.8M16.3 16.3l2.8 2.8M2 12h4M18 12h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                            <path d="M12 8v4l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        </svg>
                                                        fila assíncrona
                                                    </span>
                                                    <span class="dash-chip">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="2"/>
                                                        </svg>
                                                        ramo paralelo
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="dash-node">
                                                @php
                                                    $s = $dashFlowLanes['ieducar_out'] ?? ['tone' => 'neutral', 'label' => '—', 'hint' => ''];
                                                    $hint = $s['hint'] ?? $s['label'];
                                                @endphp
                                                <div class="dash-node__k">GIDE → iEducar</div>
                                                <div class="dash-node__title-row">
                                                    <span class="dash-node__status-hit dash-node__status-hit--{{ $s['tone'] }}" tabindex="0" aria-label="{{ $s['label'] }}: {{ $hint }}">
                                                        <span class="dash-node__status-dot" aria-hidden="true"></span>
                                                        <span class="dash-node__status-tip" role="tooltip">{{ $hint }}</span>
                                                    </span>
                                                    <div class="dash-node__t">Confirmação + consulta de status da matrícula</div>
                                                </div>
                                                <div class="dash-node__d">
                                                    Após o enroll, o GIDE confirma a coleta e permite consultar status (ano letivo/situação da matrícula).
                                                </div>
                                                <div class="dash-node__meta">
                                                    <span class="dash-chip">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                        confirmação
                                                    </span>
                                                    <span class="dash-chip">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M21 21l-4.2-4.2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                            <path d="M11 18a7 7 0 1 1 0-14 7 7 0 0 1 0 14Z" stroke="currentColor" stroke-width="2"/>
                                                        </svg>
                                                        consulta
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </section>

                                    <aside class="dash-card">
                                        <div class="dash-head">
                                            <div class="dash-head__left">
                                                <div class="dash-ico" aria-hidden="true">
                                                    <svg viewBox="0 0 24 24" fill="none">
                                                        <path d="M12 3v3M12 18v3M4.2 7.8l2.1 2.1M17.7 17.7l2.1 2.1M3 12h3M18 12h3M4.2 16.2l2.1-2.1M17.7 6.3l2.1-2.1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        <path d="M12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10Z" stroke="currentColor" stroke-width="2"/>
                                                    </svg>
                                                </div>
                                                <div style="min-width: 0;">
                                                    <div class="bridge-panel__title">Ações rápidas</div>
                                                    <div class="bridge-panel__meta">agrupado por tema</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="dash-quick">
                                            <div class="dash-quick__group dash-quick__group--hub">
                                                <div class="dash-quick__label">Ponte e monitoramento</div>
                                                <div class="dash-quick__btns">
                                                    <a class="bridge-btn dash-qbtn dash-qbtn--hub" href="{{ route('integrations.overview') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M4 19V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                            <path d="M7 14h3v5H7zM11 10h3v9h-3zM15 12h3v7h-3z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                                        </svg>
                                                        Visão geral das integrações
                                                    </a>
                                                    <a class="bridge-btn dash-qbtn dash-qbtn--gidequeues" href="{{ route('integrations.gide-queues') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M8 6h13M8 12h13M8 18h13M4 6h.01M4 12h.01M4 18h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        </svg>
                                                        Filas GIDE
                                                    </a>
                                                </div>
                                            </div>

                                            <div class="dash-quick__group dash-quick__group--connect">
                                                <div class="dash-quick__label">Conectores e canais</div>
                                                <div class="dash-quick__btns">
                                                    <a class="bridge-btn dash-qbtn dash-qbtn--ieducar" href="{{ route('integrations.ieducar') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M4 4h16v6H4z" stroke="currentColor" stroke-width="2"/>
                                                            <path d="M4 14h16v6H4z" stroke="currentColor" stroke-width="2"/>
                                                            <path d="M8 7h8M8 17h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        </svg>
                                                        iEducar (ERP)
                                                    </a>
                                                    <a class="bridge-btn dash-qbtn dash-qbtn--gestor" href="{{ route('integrations.gestor') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M7 21h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                            <path d="M12 17a5 5 0 1 0-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                            <path d="M7 12v3a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                        Biometria (ERP)
                                                    </a>
                                                    <a class="bridge-btn dash-qbtn dash-qbtn--sms" href="{{ route('integrations.sms') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M4 4h16v12H7l-3 3V4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                                            <path d="M7 8h10M7 12h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        </svg>
                                                        SMS (provedor)
                                                    </a>
                                                </div>
                                            </div>

                                            <div class="dash-quick__group" style="margin-bottom: 10px;">
                                                <div class="dash-quick__label">Operacional</div>
                                                <div class="dash-quick__btns">
                                                    <a class="bridge-btn dash-qbtn dash-chip--strong" href="{{ route('admin.operational-dashboard') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M3 3v18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                            <path d="M7 14l4-4 4 4 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                        Dashboard Operacional
                                                    </a>
                                                </div>
                                            </div>

                                            <div class="dash-quick__group dash-quick__group--audit">
                                                <div class="dash-quick__label">Auditoria e entregas</div>
                                                <div class="dash-quick__btns">
                                                    <a class="bridge-btn dash-qbtn dash-qbtn--facial" href="{{ route('admin.facial-requests.index') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M4 5h16v14H4z" stroke="currentColor" stroke-width="2"/>
                                                            <path d="M8 9h8M8 13h8M8 17h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        </svg>
                                                        Faciais
                                                    </a>
                                                    <a class="bridge-btn dash-qbtn dash-qbtn--freq" href="{{ route('admin.gestor-access-events.index') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="2"/>
                                                            <path d="M8 12h8M12 8v8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        </svg>
                                                        Eventos
                                                    </a>
                                                    <a class="bridge-btn dash-qbtn dash-qbtn--freq" href="{{ route('admin.ieducar-frequencia-deliveries.index') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M8 2v4M16 2v4M3 10h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                            <rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/>
                                                            <path d="M8 14h.01M12 14h.01M16 14h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        </svg>
                                                        Frequência
                                                    </a>
                                                    <a class="bridge-btn dash-qbtn dash-qbtn--smslog" href="{{ route('sms.index') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M6 3h12v18H6z" stroke="currentColor" stroke-width="2"/>
                                                            <path d="M9 7h6M9 11h6M9 15h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        </svg>
                                                        SMS
                                                    </a>
                                                    <a class="bridge-btn dash-qbtn dash-qbtn--useraudit" href="{{ route('admin.user-audit-logs.index') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2"/>
                                                            <polyline points="14 2 14 8 20 8" stroke="currentColor" stroke-width="2"/>
                                                            <line x1="16" y1="13" x2="8" y2="13" stroke="currentColor" stroke-width="2"/>
                                                            <line x1="16" y1="17" x2="8" y2="17" stroke="currentColor" stroke-width="2"/>
                                                        </svg>
                                                        Usuários
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                        <p class="bridge-muted" style="margin-top: 12px;">
                                            A coleta facial <strong>não</strong> é iniciada por aqui — ela deve abrir somente via URL com token gerada pelo iEducar.
                                        </p>

                                        @php
                                            $recentStudents = (new \App\Services\Timeline\StudentTimelineService)->getRecentActiveStudents(8);
                                        @endphp
                                        @if ($recentStudents->isNotEmpty())
                                            <div style="margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--border);">
                                                <div style="font-weight: 700; font-size: 14px; margin-bottom: 10px;">Últimos alunos ativos</div>
                                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                                    @foreach ($recentStudents as $student)
                                                        <a href="{{ route('admin.student-timeline', ['cod_aluno' => $student['cod_aluno']]) }}" style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface-1); text-decoration: none; color: var(--text); font-size: 13px; transition: border-color .12s;">
                                                            <span style="font-weight: 700; min-width: 54px;" class="mono">#{{ $student['cod_aluno'] }}</span>
                                                            <span style="flex: 1; color: var(--muted); font-size: 12px;">{{ $student['access_count'] }} {{ $student['access_count'] === 1 ? 'acesso' : 'acessos' }}</span>
                                                            <span style="font-size: 11px; color: var(--muted); white-space: nowrap;">{{ $student['last_event_at'] ? \Carbon\Carbon::parse($student['last_event_at'])->locale('pt_BR')->diffForHumans() : '' }}</span>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </aside>
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

