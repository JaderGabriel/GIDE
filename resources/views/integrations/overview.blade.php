<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Integrações • Visão geral • {{ config('app.name', 'Bridge ERP') }}</title>

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
        <script defer src="/home.js"></script>
        <style>
            .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 12px; }
            .integr-grid { display: grid; gap: 14px; margin-top: 14px; grid-template-columns: 1fr; }
            @media (min-width: 900px) { .integr-grid { grid-template-columns: repeat(3, 1fr); } }
            @media (min-width: 640px) and (max-width: 899px) { .integr-grid { grid-template-columns: 1fr 1fr; } }
            .int-lanes { display: grid; gap: 10px; margin-top: 12px; }
            @media (min-width: 520px) { .int-lanes { grid-template-columns: 1fr 1fr; } }
            .int-lane { border: 1px solid var(--border); border-radius: 14px; padding: 10px 10px 8px; background: color-mix(in srgb, var(--surface-2) 88%, transparent); min-height: 100%; }
            .int-lane--in { border-top: 3px solid color-mix(in srgb, var(--accent-a) 75%, var(--border)); }
            .int-lane--out { border-top: 3px solid color-mix(in srgb, var(--accent-c) 75%, var(--border)); }
            .int-lane--in.int-lane--gestor { border-top-color: color-mix(in srgb, #6366f1 70%, var(--border)); }
            .int-lane--out.int-lane--gestor { border-top-color: color-mix(in srgb, #0d9488 70%, var(--border)); }
            .int-lane--in.int-lane--sms { border-top-color: color-mix(in srgb, var(--muted) 55%, var(--border)); }
            .int-lane--out.int-lane--sms { border-top-color: color-mix(in srgb, #0284c7 70%, var(--border)); }
            .int-lane__k { font-size: 10px; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; }
            .int-lane__p { margin: 0; font-size: 12px; color: var(--muted); line-height: 1.45; }
            .int-lane__actions { margin-top: 10px; display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
            .int-foot { margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--border); font-size: 12px; color: var(--muted); line-height: 1.45; }
            .int-global-actions { margin-top: 10px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
            .card { border: 1px solid var(--border); border-radius: 18px; background: var(--card-strong); box-shadow: var(--shadow-soft); padding: 14px; }
            .row { display:flex; align-items:flex-start; justify-content: space-between; gap: 10px; }
            .pill { display:inline-flex; padding: 4px 10px; border-radius: 999px; font-size: 12px; border: 1px solid var(--border); color: var(--muted); }
            .pill--ok { border-color: color-mix(in srgb, var(--accent-c) 45%, var(--border)); background: color-mix(in srgb, var(--accent-c) 12%, transparent); color: color-mix(in srgb, var(--text) 92%, var(--accent-c)); }
            .pill--bad { border-color: color-mix(in srgb, #ef4444 55%, var(--border)); background: color-mix(in srgb, #ef4444 10%, transparent); color: #ef4444; }
            .steps { margin-top: 10px; display: grid; gap: 6px; }
            .step { padding: 8px 10px; border-radius: 14px; border: 1px solid var(--border); background: color-mix(in srgb, var(--bg0) 55%, transparent); }
            .kpi-grid { display: grid; gap: 12px; margin-top: 12px; }
            @media (min-width: 900px) { .kpi-grid { grid-template-columns: 1fr 1fr 1fr 1fr; } }
            .kpi { border: 1px solid var(--border); border-radius: 18px; padding: 12px 14px; background: var(--surface-2); }
            .kpi__k { font-size: 12px; color: var(--muted); letter-spacing: .04em; text-transform: uppercase; }
            .kpi__v { font-size: 22px; font-weight: 850; margin-top: 6px; }
            .bar { height: 10px; border-radius: 999px; border: 1px solid var(--border); background: color-mix(in srgb, var(--bg0) 55%, transparent); overflow: hidden; }
            .bar > span { display:block; height: 100%; background: color-mix(in srgb, var(--accent-c) 42%, transparent); }
            .bridge-map { margin-top: 14px; padding: 16px 14px; border-radius: 18px; border: 1px solid var(--border); background: color-mix(in srgb, var(--card-strong) 88%, var(--bg0)); }
            .bridge-map__title { font-weight: 850; font-size: 15px; }
            .bridge-map__sub { margin-top: 4px; color: var(--muted); font-size: 13px; line-height: 1.45; }
            .bridge-map__row { margin-top: 14px; display: flex; align-items: stretch; gap: 10px; flex-wrap: wrap; justify-content: center; }
            .bridge-node { flex: 0 1 140px; min-height: 72px; padding: 10px 12px; border-radius: 16px; border: 1px solid var(--border); background: var(--surface-2); display: flex; flex-direction: column; justify-content: center; text-align: center; font-weight: 750; font-size: 13px; }
            .bridge-node--gide { border-color: color-mix(in srgb, var(--accent-c) 40%, var(--border)); box-shadow: 0 0 0 1px color-mix(in srgb, var(--accent-c) 18%, transparent); }
            .bridge-node small { display: block; margin-top: 4px; font-weight: 500; color: var(--muted); font-size: 11px; line-height: 1.35; }
            .bridge-link { flex: 1 1 72px; min-width: 56px; min-height: 36px; position: relative; border-radius: 12px; align-self: center; overflow: hidden; border: 1px solid color-mix(in srgb, var(--accent-c) 25%, var(--border)); background: color-mix(in srgb, var(--bg0) 40%, transparent); }
            .bridge-link--data::after { content: ""; position: absolute; inset: 0; background: linear-gradient(90deg, transparent, color-mix(in srgb, var(--accent-c) 55%, transparent), transparent); transform: translateX(-60%); animation: bridgeFlow 2.2s ease-in-out infinite; opacity: .5; }
            .bridge-link--data.bridge-link--rev::after { animation-name: bridgeFlowRev; }
            .bridge-map.is-probing .bridge-link--data::after,
            .bridge-map.is-probing .bridge-link--data.bridge-link--rev::after { opacity: .92; animation-duration: 1.1s; }
            .bridge-link--stub { border-style: dashed; border-color: color-mix(in srgb, var(--muted) 35%, var(--border)); background: color-mix(in srgb, var(--bg0) 65%, transparent); opacity: .9; }
            .bridge-link--stub::after { display: none; }
            .bridge-link--stub.is-live { border-style: solid; border-color: color-mix(in srgb, var(--accent-c) 28%, var(--border)); opacity: 1; }
            .bridge-link--stub.is-live::after { display: block; content: ""; position: absolute; inset: 0; background: linear-gradient(90deg, transparent, color-mix(in srgb, var(--accent-c) 40%, transparent), transparent); transform: translateX(-60%); animation: bridgeFlow 2.8s ease-in-out infinite; opacity: .35; }
            .bridge-spine-grid { margin-top: 14px; display: grid; grid-template-columns: minmax(108px, 1.1fr) minmax(40px, 0.85fr) minmax(112px, 1.15fr) minmax(40px, 0.85fr) minmax(108px, 1.1fr); gap: 8px 10px; align-items: center; justify-items: stretch; }
            .bridge-spine-grid .bridge-node { width: 100%; min-height: 76px; }
            .bridge-spine-grid .bridge-link { width: 100%; min-width: 0; flex: unset; }
            .bridge-vstub-wrap { display: flex; justify-content: center; align-items: stretch; padding: 2px 0 0; }
            .bridge-vstub { width: 3px; border-radius: 999px; background: color-mix(in srgb, var(--muted) 50%, var(--border)); min-height: 18px; position: relative; overflow: hidden; }
            .bridge-sms-branch.is-sms-chain-ready .bridge-vstub { background: color-mix(in srgb, var(--accent-c) 38%, var(--border)); }
            .bridge-sms-branch { margin-top: 2px; display: flex; flex-direction: column; align-items: center; gap: 6px; }
            .bridge-vstub.is-live::after { content: ""; position: absolute; left: 0; right: 0; top: -50%; height: 55%; background: linear-gradient(180deg, transparent, color-mix(in srgb, var(--accent-c) 55%, transparent), transparent); animation: bridgeFlowY 2.6s ease-in-out infinite; opacity: .55; }
            .bridge-node__ico { display: flex; justify-content: center; margin-bottom: 6px; }
            .bridge-node__ico svg { display: block; width: 28px; height: 28px; border-radius: 8px; }
            .bridge-node__label { font-weight: 750; font-size: 13px; }
            .integration-card__head { display: flex; align-items: flex-start; gap: 12px; }
            .integration-card__ico { flex: 0 0 46px; width: 46px; height: 46px; border-radius: 14px; border: 1px solid var(--border); background: var(--surface-2); display: grid; place-items: center; }
            .integration-card__ico svg { width: 28px; height: 28px; display: block; }
            .integration-card__actions { margin-top: 12px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
            .bridge-btn--icononly { padding: 0; width: 42px; height: 42px; min-width: 42px; display: inline-flex; align-items: center; justify-content: center; gap: 0; border-radius: 12px; }
            .bridge-btn--icononly svg { width: 20px; height: 20px; display: block; }
            @keyframes bridgeFlow { 0% { transform: translateX(-70%); } 50% { transform: translateX(40%); } 100% { transform: translateX(-70%); } }
            @keyframes bridgeFlowRev { 0% { transform: translateX(70%); } 50% { transform: translateX(-40%); } 100% { transform: translateX(70%); } }
            @keyframes bridgeFlowY { 0% { transform: translateY(-100%); opacity: 0; } 15% { opacity: .55; } 85% { opacity: .55; } 100% { transform: translateY(220%); opacity: 0; } }
            .bridge-legend { margin-top: 12px; display: grid; gap: 6px; font-size: 12px; color: var(--muted); }
            .bridge-legend span { color: color-mix(in srgb, var(--text) 88%, var(--muted)); }
            .bridge-actions { margin-top: 12px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
            .bridge-actions .bridge-btn--ghost { background: transparent; border: 1px dashed var(--border); }
            .bridge-result { margin-top: 10px; padding: 10px 12px; border-radius: 14px; border: 1px solid var(--border); background: var(--surface-2); max-height: 220px; overflow: auto; display: none; }
            .bridge-result.is-open { display: block; }
            .bridge-result pre { margin: 0; white-space: pre-wrap; word-break: break-word; font-size: 12px; }
            .queue-panel { margin-top: 14px; padding: 14px; border-radius: 18px; border: 1px solid var(--border); background: var(--card-strong); }
            .queue-panel__head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; }
            .queue-tabs { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
            .queue-tab { padding: 6px 12px; border-radius: 999px; border: 1px solid var(--border); background: transparent; font-size: 12px; cursor: pointer; color: var(--muted); }
            .queue-tab.is-on { border-color: color-mix(in srgb, var(--accent-c) 45%, var(--border)); color: color-mix(in srgb, var(--text) 90%, var(--accent-c)); background: color-mix(in srgb, var(--accent-c) 10%, transparent); }
            .queue-table-wrap { margin-top: 10px; overflow: auto; max-height: 280px; border-radius: 14px; border: 1px solid var(--border); }
            .queue-table { width: 100%; border-collapse: collapse; font-size: 12px; }
            .queue-table th, .queue-table td { padding: 8px 10px; border-bottom: 1px solid var(--border); text-align: left; vertical-align: top; }
            .queue-table th { position: sticky; top: 0; background: var(--surface-2); z-index: 1; color: var(--muted); font-weight: 650; }
            .queue-table tr:last-child td { border-bottom: none; }
            .st-ok { color: color-mix(in srgb, var(--accent-c) 70%, var(--text)); font-weight: 650; }
            .st-bad { color: #ef4444; font-weight: 650; }
            .st-warn { color: #f59e0b; font-weight: 650; }
        </style>
    </head>
    <body>
        <div class="bridge-shell">
            <header class="bridge-header">
                <div class="bridge-container">
                    <div class="bridge-header__inner">
                        <a class="bridge-brand" href="/dashboard">
                            <img src="/favicon.svg" alt="" class="bridge-brand__logo" />
                            <div class="bridge-brand__text">
                                <div class="bridge-brand__name">{{ config('app.name', 'Bridge ERP') }}</div>
                                <div class="bridge-brand__tagline">Visão geral • Integrações</div>
                            </div>
                        </a>
                    </div>
                </div>
            </header>

            <main class="bridge-main">
                <div class="bridge-container">
                    <div class="bridge-auth">
                        <div class="bridge-panel">
                            <div class="bridge-panel__head">
                                <div class="bridge-panel__title">Integrações</div>
                                <div class="bridge-panel__meta">pontes ida e volta • fila e entregas • testes rápidos</div>
                            </div>

                            @php
                                $qs = is_array($queueSnapshot ?? null) ? $queueSnapshot : ['jobs' => [], 'failed_jobs' => [], 'outbound' => [], 'sms' => []];
                            @endphp

                            @php
                                $smsChainReady = (bool) ($smsChainReady ?? false);
                                $smsConfigured = (bool) ($smsConfigured ?? false);
                                $gestorConfigured = (bool) ($gestorConfigured ?? false);
                                $gestorEnabled = (bool) ($gestorEnabled ?? false);
                                $smsEnabled = (bool) ($smsEnabled ?? false);
                                $smsWaitHint = null;
                                if (! $smsChainReady) {
                                    if (! $gestorConfigured) {
                                        $smsWaitHint = 'configure o Gestor';
                                    } elseif (! $gestorEnabled) {
                                        $smsWaitHint = 'habilite o Gestor';
                                    } elseif (! $smsConfigured) {
                                        $smsWaitHint = 'configure o SMS';
                                    } elseif (! $smsEnabled) {
                                        $smsWaitHint = 'habilite o SMS';
                                    }
                                }
                            @endphp
                            <section class="bridge-map" id="bridge-map" aria-labelledby="bridge-map-title">
                                <div class="bridge-map__title" id="bridge-map-title">Mapa da ponte</div>
                                <div class="bridge-map__sub">
                                    Os dados entram pelo <strong>iEducar</strong>, passam pelo <strong>GIDE</strong> (fila e regras) e seguem para o <strong>Gestor</strong> na catraca (SDK Kiper).
                                    O <strong>SMS</strong> fica ligado ao GIDE (envio após presença), mas só faz sentido operacionalmente com o <strong>Gestor</strong> entregue e a integração SMS configurada.
                                    Faixas com movimento = tronco iEducar ↔ GIDE ↔ Gestor; ramo ao SMS usa traço estático até o encadeamento estar pronto.
                                </div>
                                <div class="bridge-spine-grid" role="img" aria-label="Fluxo iEducar, GIDE, Gestor e ramo SMS">
                                    <div class="bridge-node">
                                        <div class="bridge-node__ico" aria-hidden="true">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="28" height="28"><rect width="32" height="32" rx="8" fill="#1b6b3a"/><path fill="#fff" d="M8 9h2.6v14H8V9zm5.3 0h4.2c3.8 0 6.1 2.1 6.1 5.6 0 3.4-2.3 5.5-6.1 5.5h-1.6V23h-2.6V9zm2.6 2.4v6.3h1.4c2.1 0 3.3-1 3.3-3.1 0-2.2-1.2-3.2-3.3-3.2h-1.4z"/></svg>
                                        </div>
                                        <div class="bridge-node__label">iEducar</div>
                                        <small>Origem: matrícula, facial, frequência (HMAC). Volta: API catraca-frequência (Bearer).</small>
                                    </div>
                                    <div class="bridge-link bridge-link--data bridge-link--lr" title="Tráfego típico GIDE ↔ iEducar"></div>
                                    <div class="bridge-node bridge-node--gide">
                                        <div class="bridge-node__ico" aria-hidden="true"><img src="/favicon.svg" width="28" height="28" alt="" /></div>
                                        <div class="bridge-node__label">GIDE</div>
                                        <small>Meio: normaliza eventos, fila assíncrona, outbound Gestor e SMS.</small>
                                    </div>
                                    <div class="bridge-link bridge-link--data bridge-link--lr" title="Tráfego típico GIDE ↔ Gestor"></div>
                                    <div class="bridge-node">
                                        <div class="bridge-node__ico" aria-hidden="true">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="28" height="28"><rect width="32" height="32" rx="8" fill="#4338ca"/><path fill="none" stroke="#fff" stroke-width="2" d="M10 11h12M10 16h12M10 21h12M16 9v14"/></svg>
                                        </div>
                                        <div class="bridge-node__label">Gestor (catraca)</div>
                                        <small>Ponta: eventos de acesso (HMAC). Volta: Signin, Invite, Face…</small>
                                    </div>
                                </div>
                                <div class="bridge-sms-branch {{ $smsChainReady ? 'is-sms-chain-ready' : '' }}">
                                    <div class="bridge-vstub-wrap">
                                        <div class="bridge-vstub {{ $smsChainReady ? 'is-live' : '' }}" title="Ramo SMS a partir do GIDE"></div>
                                    </div>
                                    <div class="bridge-node bridge-node--sms" style="max-width: 220px; width: 100%;">
                                        <div class="bridge-node__ico" aria-hidden="true">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="28" height="28"><rect width="32" height="32" rx="8" fill="#0ea5e9"/><path fill="#fff" d="M10 11.5c0-1.1.9-2 2-2h8c1.1 0 2 .9 2 2v6.5c0 1.1-.9 2-2 2h-4.2L12 22v-2.5h-2c-1.1 0-2-.9-2-2v-6z"/></svg>
                                        </div>
                                        <div class="bridge-node__label">SMS</div>
                                        <small>
                                            Plugado no GIDE; depende do Gestor para presença e da integração SMS.
                                            @if (! $smsChainReady && $smsWaitHint)
                                                <span style="display:block;margin-top:4px;color:var(--muted);">Ramo em espera ({{ $smsWaitHint }}).</span>
                                            @endif
                                        </small>
                                    </div>
                                </div>
                                <div class="bridge-legend">
                                    <div><span>→</span> <strong>Tronco</strong>: iEducar — GIDE — Gestor (fluxo principal animado como “canal ativo”).</div>
                                    <div><span>↓</span> <strong>SMS</strong>: ramo a partir do GIDE; animação leve só quando Gestor + SMS estão prontos e o SMS está habilitado.</div>
                                </div>
                                <div class="bridge-actions">
                                    <button type="button" class="bridge-btn bridge-btn--ghost bridge-btn--icononly" id="btn-bridge-ieducar" data-url="{{ route('integrations.bridge.ieducar') }}" title="Testar ponte iEducar" aria-label="Testar ponte iEducar: rede e API catraca-frequência">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="22" height="22" aria-hidden="true"><rect width="32" height="32" rx="8" fill="#1b6b3a"/><path fill="#fff" d="M8 9h2.6v14H8V9zm5.3 0h4.2c3.8 0 6.1 2.1 6.1 5.6 0 3.4-2.3 5.5-6.1 5.5h-1.6V23h-2.6V9zm2.6 2.4v6.3h1.4c2.1 0 3.3-1 3.3-3.1 0-2.2-1.2-3.2-3.3-3.2h-1.4z"/></svg>
                                    </button>
                                    <button type="button" class="bridge-btn bridge-btn--ghost bridge-btn--icononly" id="btn-bridge-gestor" data-url="{{ route('integrations.bridge.gestor') }}" title="Testar ponte Gestor" aria-label="Testar ponte Gestor">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                    </button>
                                    <button type="button" class="bridge-btn bridge-btn--ghost bridge-btn--icononly" id="btn-bridge-sms" data-url="{{ route('integrations.bridge.sms') }}" title="Testar ponte SMS" aria-label="Testar ponte SMS">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>
                                    </button>
                                    <span class="bridge-muted" style="font-size:12px;">Testes rápidos (JSON, timeout 12s).</span>
                                </div>
                                <div class="bridge-result" id="bridge-result" role="status" aria-live="polite"><pre id="bridge-result-pre"></pre></div>
                            </section>

                            <section class="queue-panel" aria-labelledby="queue-panel-title">
                                <div class="queue-panel__head">
                                    <div>
                                        <div style="font-weight: 850;" id="queue-panel-title">Fila e status de entregas</div>
                                        <div class="bridge-muted" style="margin-top:4px;font-size:12px;">Jobs pendentes, falhas de job, outbound para o Gestor e SMS — com HTTP, tentativas e último erro. Horários em <strong>{{ \App\Support\DateDisplay::timezoneLabel() }}</strong>. Detalhe por SMS: <a href="{{ route('sms.index') }}">lista de envios</a>.</div>
                                    </div>
                                </div>
                                <div class="queue-tabs" role="tablist">
                                    <button type="button" class="queue-tab is-on" data-tab="jobs">Jobs ({{ count($qs['jobs'] ?? []) }})</button>
                                    <button type="button" class="queue-tab" data-tab="failed">Falhas ({{ count($qs['failed_jobs'] ?? []) }})</button>
                                    <button type="button" class="queue-tab" data-tab="outbound">Outbound Gestor ({{ count($qs['outbound'] ?? []) }})</button>
                                    <button type="button" class="queue-tab" data-tab="sms">SMS ({{ count($qs['sms'] ?? []) }})</button>
                                </div>
                                <div class="queue-table-wrap" data-panel="jobs">
                                    <table class="queue-table">
                                        <thead><tr><th>ID</th><th>Fila</th><th>Job</th><th>Tent.</th><th>Criado</th><th>Disponível</th></tr></thead>
                                        <tbody>
                                            @forelse ($qs['jobs'] ?? [] as $j)
                                                <tr>
                                                    <td class="mono">{{ $j['id'] ?? '' }}</td>
                                                    <td class="mono">{{ $j['queue'] ?? '' }}</td>
                                                    <td class="mono">{{ $j['label'] ?? '—' }}</td>
                                                    <td>{{ (int) ($j['attempts'] ?? 0) }}</td>
                                                    <td style="font-size:11px;line-height:1.35;">{{ $j['created_at_display'] ?? '—' }}</td>
                                                    <td style="font-size:11px;line-height:1.35;">{{ $j['available_at_display'] ?? '—' }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="6" class="bridge-muted">Nenhum job na tabela <code>jobs</code> (fila vazia ou worker já drenou).</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="queue-table-wrap" data-panel="failed" hidden>
                                    <table class="queue-table">
                                        <thead><tr><th>ID</th><th>Fila</th><th>Job</th><th>Falhou em</th><th>Exceção (início)</th></tr></thead>
                                        <tbody>
                                            @forelse ($qs['failed_jobs'] ?? [] as $j)
                                                <tr>
                                                    <td class="mono">{{ $j['id'] ?? '' }}</td>
                                                    <td class="mono">{{ $j['queue'] ?? '' }}</td>
                                                    <td class="mono">{{ $j['label'] ?? '—' }}</td>
                                                    <td style="font-size:11px;line-height:1.35;">{{ $j['failed_at_display'] ?? '—' }}</td>
                                                    <td class="mono" style="font-size:11px;">{{ $j['exception'] ?? '—' }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="5" class="bridge-muted">Sem registros em <code>failed_jobs</code>.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="queue-table-wrap" data-panel="outbound" hidden>
                                    <table class="queue-table">
                                        <thead><tr><th>ID</th><th>Event id</th><th>Status</th><th>Tent.</th><th>HTTP</th><th>Entregue</th><th>Próximo retry</th><th>Erro</th></tr></thead>
                                        <tbody>
                                            @forelse ($qs['outbound'] ?? [] as $r)
                                                @php
                                                    $st = (string) ($r['status'] ?? '');
                                                    $cls = $st === 'completed' ? 'st-ok' : ($st === 'failed' ? 'st-bad' : ($st === 'retry_scheduled' ? 'st-warn' : ''));
                                                @endphp
                                                <tr>
                                                    <td class="mono">{{ $r['id'] ?? '' }}</td>
                                                    <td class="mono" style="max-width:120px;word-break:break-all;">{{ $r['event_id'] ?? '' }}</td>
                                                    <td class="{{ $cls }}">{{ $st !== '' ? $st : '—' }}</td>
                                                    <td>{{ (int) ($r['attempts'] ?? 0) }}</td>
                                                    <td class="mono">{{ $r['http'] ?? '—' }}</td>
                                                    <td style="font-size:11px;line-height:1.35;">{{ $r['delivered_at_display'] ?? '—' }}</td>
                                                    <td style="font-size:11px;line-height:1.35;">{{ $r['next_retry_at_display'] ?? '—' }}</td>
                                                    <td class="mono" style="font-size:11px;">{{ $r['error'] ?? '—' }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="8" class="bridge-muted">Sem linhas recentes em <code>outbound_deliveries</code>.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="queue-table-wrap" data-panel="sms" hidden>
                                    <table class="queue-table">
                                        <thead><tr><th>ID</th><th>Event id</th><th>Status</th><th>Tent.</th><th>HTTP</th><th>Enviado</th><th>Próximo retry</th><th>Erro</th></tr></thead>
                                        <tbody>
                                            @forelse ($qs['sms'] ?? [] as $r)
                                                @php
                                                    $st = (string) ($r['status'] ?? '');
                                                    $cls = ($r['sent_at'] ?? null) ? 'st-ok' : ($st === 'error' ? 'st-bad' : 'st-warn');
                                                @endphp
                                                <tr>
                                                    <td class="mono">{{ $r['id'] ?? '' }}</td>
                                                    <td class="mono" style="max-width:120px;word-break:break-all;">{{ $r['event_id'] ?? '' }}</td>
                                                    <td class="{{ $cls }}">{{ $st !== '' ? $st : '—' }}</td>
                                                    <td>{{ (int) ($r['attempts'] ?? 0) }}</td>
                                                    <td class="mono">{{ $r['http'] ?? '—' }}</td>
                                                    <td style="font-size:11px;line-height:1.35;">{{ $r['sent_at_display'] ?? '—' }}</td>
                                                    <td style="font-size:11px;line-height:1.35;">{{ $r['next_retry_at_display'] ?? '—' }}</td>
                                                    <td class="mono" style="font-size:11px;">{{ $r['error'] ?? '—' }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="8" class="bridge-muted">Sem linhas recentes em <code>sms_deliveries</code>.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </section>

                            @php
                                $m = is_array($metrics ?? null) ? $metrics : ['total' => 0, 'enabled' => 0, 'configured' => 0, 'not_configured' => 0, 'disabled' => 0];
                                $total = max(1, (int) ($m['total'] ?? 1));
                                $pConfigured = (int) round(((int) ($m['configured'] ?? 0) / $total) * 100);
                                $pEnabled = (int) round(((int) ($m['enabled'] ?? 0) / $total) * 100);
                            @endphp

                            <div class="kpi-grid">
                                <div class="kpi">
                                    <div class="kpi__k">Total</div>
                                    <div class="kpi__v">{{ (int) ($m['total'] ?? 0) }}</div>
                                </div>
                                <div class="kpi">
                                    <div class="kpi__k">Configuradas</div>
                                    <div class="kpi__v">{{ (int) ($m['configured'] ?? 0) }}</div>
                                    <div class="bar" style="margin-top: 10px;"><span style="width: {{ $pConfigured }}%"></span></div>
                                    <div class="bridge-muted mono" style="margin-top: 6px;">{{ $pConfigured }}%</div>
                                </div>
                                <div class="kpi">
                                    <div class="kpi__k">Habilitadas</div>
                                    <div class="kpi__v">{{ (int) ($m['enabled'] ?? 0) }}</div>
                                    <div class="bar" style="margin-top: 10px;"><span style="width: {{ $pEnabled }}%"></span></div>
                                    <div class="bridge-muted mono" style="margin-top: 6px;">{{ $pEnabled }}%</div>
                                </div>
                                <div class="kpi">
                                    <div class="kpi__k">Pendentes</div>
                                    <div class="kpi__v">{{ (int) ($m['not_configured'] ?? 0) }}</div>
                                    <div class="bridge-muted" style="margin-top: 6px;">não configuradas</div>
                                </div>
                            </div>

                            <div class="card" style="margin-top: 12px;">
                                <div class="row">
                                    <div>
                                        <div style="font-weight: 850;">Base de dados (GIDE)</div>
                                        <div class="bridge-muted" style="margin-top: 4px;">contadores e pendências operacionais</div>
                                    </div>
                                    <span class="pill {{ is_array($dbMetrics ?? null) && empty($dbMetrics['error']) ? 'pill--ok' : 'pill--bad' }}">
                                        {{ is_array($dbMetrics ?? null) && empty($dbMetrics['error']) ? 'ok' : 'erro' }}
                                    </span>
                                </div>

                                @if (is_array($dbMetrics ?? null) && !empty($dbMetrics['error']))
                                    <div class="mono" style="margin-top: 10px; color:#ef4444; white-space: pre-wrap; overflow-wrap:anywhere;">{{ $dbMetrics['error'] }}</div>
                                @else
                                    @php $db = is_array($dbMetrics ?? null) ? $dbMetrics : []; @endphp
                                    <div class="kpi-grid" style="margin-top: 12px;">
                                        <div class="kpi">
                                            <div class="kpi__k">Inbounds iEducar</div>
                                            <div class="kpi__v">{{ (int) ($db['gide_facial_inbounds'] ?? 0) }}</div>
                                            <div class="bridge-muted" style="margin-top: 6px;">`gide_facial_inbounds`</div>
                                        </div>
                                        <div class="kpi">
                                            <div class="kpi__k">Solicitações faciais</div>
                                            <div class="kpi__v">{{ (int) ($db['facial_send_requests'] ?? 0) }}</div>
                                            <div class="bridge-muted" style="margin-top: 6px;">`facial_send_requests`</div>
                                        </div>
                                        <div class="kpi">
                                            <div class="kpi__k">Tentativas (face)</div>
                                            <div class="kpi__v">{{ (int) ($db['facial_enroll_attempts'] ?? 0) }}</div>
                                            <div class="bridge-muted" style="margin-top: 6px;">`facial_enroll_attempts`</div>
                                        </div>
                                        <div class="kpi">
                                            <div class="kpi__k">Snapshots iEducar</div>
                                            <div class="kpi__v">{{ (int) ($db['facial_status_snapshots'] ?? 0) }}</div>
                                            <div class="bridge-muted" style="margin-top: 6px;">`facial_ieducar_status_snapshots`</div>
                                        </div>
                                    </div>

                                    <div class="kpi-grid" style="margin-top: 12px;">
                                        <div class="kpi">
                                            <div class="kpi__k">Outbound deliveries</div>
                                            <div class="kpi__v">{{ (int) ($db['outbound_deliveries'] ?? 0) }}</div>
                                            <div class="bridge-muted" style="margin-top: 6px;">sem entrega: <span class="mono">{{ (int) ($db['outbound_pending'] ?? 0) }}</span> · falhou: <span class="mono">{{ (int) ($db['outbound_failed'] ?? 0) }}</span> · retry devido: <span class="mono">{{ (int) ($db['outbound_retry_due'] ?? 0) }}</span></div>
                                        </div>
                                        <div class="kpi">
                                            <div class="kpi__k">SMS deliveries</div>
                                            <div class="kpi__v">{{ (int) ($db['sms_deliveries'] ?? 0) }}</div>
                                            <div class="bridge-muted" style="margin-top: 6px;">sem envio: <span class="mono">{{ (int) ($db['sms_pending'] ?? 0) }}</span> · retry devido: <span class="mono">{{ (int) ($db['sms_retry_due'] ?? 0) }}</span></div>
                                        </div>
                                        <div class="kpi">
                                            <div class="kpi__k">Fila (jobs)</div>
                                            <div class="kpi__v">{{ (int) ($db['jobs_pending'] ?? 0) }}</div>
                                            <div class="bridge-muted" style="margin-top: 6px;">tabela `jobs` (cron: schedule + worker)</div>
                                        </div>
                                        <div class="kpi">
                                            <div class="kpi__k">Links Guest/Face</div>
                                            <div class="kpi__v">{{ (int) ($db['gestor_guest_links'] ?? 0) }}</div>
                                            <div class="bridge-muted" style="margin-top: 6px;">`gestor_guest_links`</div>
                                        </div>
                                        <div class="kpi">
                                            <div class="kpi__k">Saúde</div>
                                            @php
                                                $attention = (int) ($db['jobs_pending'] ?? 0)
                                                    + (int) ($db['outbound_retry_due'] ?? 0)
                                                    + (int) ($db['sms_retry_due'] ?? 0);
                                            @endphp
                                            <div class="kpi__v">{{ $attention === 0 ? 'OK' : $attention }}</div>
                                            <div class="bridge-muted" style="margin-top: 6px;">jobs + retries devidos (fila/cron)</div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            @if (is_array($lastTest ?? null) && ($lastTestKey ?? ''))
                                @php
                                    $ltLane = (string) data_get($lastTest, 'lane', 'out');
                                    $ltLaneLabel = $ltLane === 'in' ? 'entrada (→ GIDE)' : 'saída (← GIDE)';
                                @endphp
                                <div class="card" style="margin-top: 12px;">
                                    <div class="row">
                                        <div>
                                            <div style="font-weight: 800;">
                                                Último teste: <span class="mono">{{ $lastTestKey }}</span>
                                                <span class="pill" style="margin-left:8px;">{{ $ltLaneLabel }}</span>
                                            </div>
                                            <div class="bridge-muted" style="margin-top: 4px;">
                                                @if (data_get($lastTest, 'tested_at_display'))
                                                    {{ data_get($lastTest, 'tested_at_display') }}
                                                @elseif (data_get($lastTest, 'tested_at'))
                                                    {{ \App\Support\DateDisplay::formatHuman(\Illuminate\Support\Carbon::parse(data_get($lastTest, 'tested_at')), true) }}
                                                @endif
                                                {{ data_get($lastTest, 'timeout') ? ' • timeout '.data_get($lastTest, 'timeout').'s' : '' }}
                                            </div>
                                        </div>
                                        <span class="pill {{ data_get($lastTest, 'ok') ? 'pill--ok' : 'pill--bad' }}">
                                            {{ data_get($lastTest, 'ok') ? 'OK' : 'falha' }}
                                        </span>
                                    </div>
                                    <div class="steps">
                                        @foreach ((array) data_get($lastTest, 'steps', []) as $st)
                                            <div class="step">
                                                <div class="row" style="justify-content:flex-start;">
                                                    <span class="pill {{ data_get($st, 'ok') ? 'pill--ok' : 'pill--bad' }}">{{ data_get($st, 'ok') ? 'OK' : 'falhou' }}</span>
                                                    <div style="font-weight: 700;">{{ data_get($st, 'name') }}</div>
                                                </div>
                                                @if (data_get($st, 'message'))
                                                    <div class="mono" style="margin-top: 6px; white-space: pre-wrap; overflow-wrap:anywhere;">{{ data_get($st, 'message') }}</div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @php
                                $cf = $catracaFrequencia ?? null;
                                $cfPersisted = $cf instanceof \App\Models\Integration && $cf->getKey() !== null;
                                $integrationCardsList = ($integrationCards ?? collect())->isNotEmpty()
                                    ? $integrationCards
                                    : ($items ?? collect())->filter(fn ($i) => in_array((string) ($i->key ?? ''), ['ieducar', 'gestor', 'sms'], true))->values();
                            @endphp
                            <div class="integr-grid">
                                @foreach ($integrationCardsList as $it)
                                    @php
                                        $enabled = (bool) ($it->enabled ?? false);
                                        $hasBase = is_string($it->base_url ?? null) && (string) $it->base_url !== '';
                                        $hasAuthToken = is_string($it->auth_token ?? null) && (string) $it->auth_token !== '';
                                        $hasHmac = is_string($it->hmac_secret ?? null) && (string) $it->hmac_secret !== '';
                                        $configured = $hasBase || $hasAuthToken || $hasHmac || ! empty($it->extra);
                                        $k = (string) ($it->key ?? '');
                                        $configHref = match ($k) {
                                            'ieducar' => route('integrations.ieducar'),
                                            'gestor' => route('integrations.gestor'),
                                            'sms' => route('integrations.sms'),
                                            default => url('/dashboard'),
                                        };
                                        $configLabel = 'Abrir configuração: '.($it->name ?? $k);
                                        $cfHasBearer = $cf && is_string($cf->auth_token ?? null) && (string) $cf->auth_token !== '';
                                        $confirmTok = is_string(data_get($it->extra, 'catraca_frequencia.confirmacao_token')) && (string) data_get($it->extra, 'catraca_frequencia.confirmacao_token') !== '';
                                    @endphp
                                    <div class="card integration-card">
                                        <div class="row">
                                            <div class="integration-card__head" style="flex: 1; min-width: 0;">
                                                <div class="integration-card__ico" aria-hidden="true">
                                                    @if ($k === 'ieducar')
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="28" height="28"><rect width="32" height="32" rx="8" fill="#1b6b3a"/><path fill="#fff" d="M8 9h2.6v14H8V9zm5.3 0h4.2c3.8 0 6.1 2.1 6.1 5.6 0 3.4-2.3 5.5-6.1 5.5h-1.6V23h-2.6V9zm2.6 2.4v6.3h1.4c2.1 0 3.3-1 3.3-3.1 0-2.2-1.2-3.2-3.3-3.2h-1.4z"/></svg>
                                                    @elseif ($k === 'gestor')
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="28" height="28"><rect width="32" height="32" rx="8" fill="#4338ca"/><path fill="none" stroke="#fff" stroke-width="2" d="M10 11h12M10 16h12M10 21h12M16 9v14"/></svg>
                                                    @elseif ($k === 'sms')
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="28" height="28"><rect width="32" height="32" rx="8" fill="#0ea5e9"/><path fill="#fff" d="M10 11.5c0-1.1.9-2 2-2h8c1.1 0 2 .9 2 2v6.5c0 1.1-.9 2-2 2h-4.2L12 22v-2.5h-2c-1.1 0-2-.9-2-2v-6z"/></svg>
                                                    @else
                                                        <img src="/favicon.svg" width="28" height="28" alt="" />
                                                    @endif
                                                </div>
                                                <div style="min-width:0;">
                                                    <div style="font-weight: 850;">{{ $it->name ?? $it->key }}</div>
                                                    <div class="bridge-muted mono" style="margin-top: 4px;">key={{ $it->key }}</div>
                                                </div>
                                            </div>
                                            <span class="pill {{ $enabled ? 'pill--ok' : '' }}">{{ $enabled ? 'habilitada' : 'desabilitada' }}</span>
                                        </div>

                                        <p class="bridge-muted" style="margin: 10px 0 0; font-size: 12px; line-height: 1.45;">
                                            Cada conector é <strong>bidirecional</strong>: o ERP ou a catraca enviam eventos ao GIDE (recepção) e o GIDE chama APIs remotas (saída). Configuração e testes podem diferir em cada sentido.
                                        </p>

                                        @if ($k === 'ieducar')
                                            <div class="int-lanes">
                                                <div class="int-lane int-lane--in">
                                                    <div class="int-lane__k">Recepção → GIDE</div>
                                                    <p class="int-lane__p">Chamadas do iEducar aos endpoints inbound do GIDE (facial, frequência, etc.). Exige segredo HMAC alinhado ao iEducar.</p>
                                                    <div class="mono bridge-muted" style="font-size:11px;margin-top:6px;">HMAC: <strong>{{ $hasHmac ? 'configurado' : '(vazio)' }}</strong></div>
                                                    <div class="int-lane__actions">
                                                        <form method="POST" action="{{ route('integrations.overview.test', ['key' => 'ieducar']) }}" style="display:inline;">
                                                            @csrf
                                                            <input type="hidden" name="timeout" value="10" />
                                                            <input type="hidden" name="lane" value="in" />
                                                            <button type="submit" class="bridge-btn bridge-btn--icononly" title="Testar recepção: HMAC iEducar → GIDE" aria-label="Testar recepção iEducar (HMAC)">
                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                                <div class="int-lane int-lane--out">
                                                    <div class="int-lane__k">Saída ← GIDE</div>
                                                    <p class="int-lane__p">Chamadas do GIDE ao iEducar (host, API catraca-frequência com Bearer ou token em <span class="mono">extra</span>).</p>
                                                    <div class="mono bridge-muted" style="font-size:11px;margin-top:6px;">
                                                        base_url: <strong>{{ $hasBase ? 'ok' : '(vazio)' }}</strong>
                                                        · Bearer (integração ieducar): <strong>{{ $hasAuthToken ? 'sim' : 'não' }}</strong>
                                                        @if ($cfPersisted)
                                                            · Bearer (integração <span class="mono">catraca_frequencia</span>): <strong>{{ $cfHasBearer ? 'sim' : 'não' }}</strong>
                                                        @endif
                                                        <br>Token confirmação (extra): <strong>{{ $confirmTok ? 'sim' : 'não' }}</strong>
                                                    </div>
                                                    <div class="int-lane__actions">
                                                        <form method="POST" action="{{ route('integrations.overview.test', ['key' => 'ieducar']) }}" style="display:inline;">
                                                            @csrf
                                                            <input type="hidden" name="timeout" value="10" />
                                                            <input type="hidden" name="lane" value="out" />
                                                            <button type="submit" class="bridge-btn bridge-btn--icononly" title="Testar saída: reachability base_url iEducar" aria-label="Testar saída iEducar (HTTP base)">
                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                                            </button>
                                                        </form>
                                                        @if ($cfPersisted)
                                                            <form method="POST" action="{{ route('integrations.overview.test', ['key' => 'catraca_frequencia']) }}" style="display:inline;">
                                                                @csrf
                                                                <input type="hidden" name="timeout" value="10" />
                                                                <button type="submit" class="bridge-btn bridge-btn--icononly" title="Testar Bearer API catraca-frequência (integração catraca_frequencia)" aria-label="Testar Bearer catraca-frequência">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="int-foot">
                                                A integração <span class="mono">catraca_frequencia</span> (Bearer dedicado) não tem card próprio: compõe o mesmo conector iEducar e é configurada na mesma tela.
                                                @if (! $cfPersisted)
                                                    Cadastre a chave <span class="mono">catraca_frequencia</span> no banco para habilitar o teste extra do Bearer.
                                                @endif
                                            </div>
                                        @elseif ($k === 'gestor')
                                            <div class="int-lanes">
                                                <div class="int-lane int-lane--in int-lane--gestor">
                                                    <div class="int-lane__k">Recepção → GIDE</div>
                                                    <p class="int-lane__p">Webhooks / eventos da catraca (Gestor) para o GIDE. Depende de <span class="mono">base_url</span> e HMAC de inbound.</p>
                                                    <div class="mono bridge-muted" style="font-size:11px;margin-top:6px;">URL: <strong>{{ $hasBase ? 'ok' : '(vazio)' }}</strong> · HMAC: <strong>{{ $hasHmac ? 'ok' : '(vazio)' }}</strong></div>
                                                    <div class="int-lane__actions">
                                                        <form method="POST" action="{{ route('integrations.overview.test', ['key' => 'gestor']) }}" style="display:inline;">
                                                            @csrf
                                                            <input type="hidden" name="timeout" value="10" />
                                                            <input type="hidden" name="lane" value="in" />
                                                            <button type="submit" class="bridge-btn bridge-btn--icononly" title="Testar recepção Gestor → GIDE" aria-label="Testar recepção Gestor">
                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                                <div class="int-lane int-lane--out int-lane--gestor">
                                                    <div class="int-lane__k">Saída ← GIDE</div>
                                                    <p class="int-lane__p">SDK: signin e chamadas autenticadas do GIDE para o Gestor (matrícula, face, convites, etc.).</p>
                                                    <div class="mono bridge-muted" style="font-size:11px;margin-top:6px;">Credenciais SDK conforme formulário Gestor.</div>
                                                    <div class="int-lane__actions">
                                                        <form method="POST" action="{{ route('integrations.overview.test', ['key' => 'gestor']) }}" style="display:inline;">
                                                            @csrf
                                                            <input type="hidden" name="timeout" value="10" />
                                                            <input type="hidden" name="lane" value="out" />
                                                            <button type="submit" class="bridge-btn bridge-btn--icononly" title="Testar saída: Signin GIDE → Gestor" aria-label="Testar Signin Gestor">
                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @elseif ($k === 'sms')
                                            <div class="int-lanes">
                                                <div class="int-lane int-lane--in int-lane--sms">
                                                    <div class="int-lane__k">Recepção → GIDE</div>
                                                    <p class="int-lane__p">Sem webhook padrão nesta integração: o fluxo principal é o GIDE enviar SMS após regras de presença.</p>
                                                    <div class="int-lane__actions">
                                                        <form method="POST" action="{{ route('integrations.overview.test', ['key' => 'sms']) }}" style="display:inline;">
                                                            @csrf
                                                            <input type="hidden" name="timeout" value="10" />
                                                            <input type="hidden" name="lane" value="in" />
                                                            <button type="submit" class="bridge-btn bridge-btn--icononly" title="Testar nota de recepção SMS" aria-label="Testar recepção SMS">
                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                                <div class="int-lane int-lane--out int-lane--sms">
                                                    <div class="int-lane__k">Saída ← GIDE</div>
                                                    <p class="int-lane__p">HTTP autenticado ao provedor (envio e retries na fila).</p>
                                                    <div class="mono bridge-muted" style="font-size:11px;margin-top:6px;">URL: <strong>{{ $hasBase ? 'ok' : '(vazio)' }}</strong> · Token: <strong>{{ $hasAuthToken ? 'ok' : '(vazio)' }}</strong></div>
                                                    <div class="int-lane__actions">
                                                        <form method="POST" action="{{ route('integrations.overview.test', ['key' => 'sms']) }}" style="display:inline;">
                                                            @csrf
                                                            <input type="hidden" name="timeout" value="10" />
                                                            <input type="hidden" name="lane" value="out" />
                                                            <button type="submit" class="bridge-btn bridge-btn--icononly" title="Testar saída: HTTP com Bearer ao provedor" aria-label="Testar saída SMS">
                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="int-global-actions">
                                            <a class="bridge-btn bridge-btn--icononly" href="{{ $configHref }}" title="{{ $configLabel }}" aria-label="{{ $configLabel }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                                            </a>
                                            <span class="bridge-muted mono" style="font-size:11px;">Resumo: {{ $configured ? 'há credenciais preenchidas' : 'não configurada' }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="bridge-form__actions" style="margin-top: 14px;">
                                <a class="bridge-btn" href="/dashboard">Voltar</a>
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
        <script>
            (function () {
                var tabs = document.querySelectorAll('.queue-tab');
                var panels = document.querySelectorAll('.queue-table-wrap[data-panel]');
                tabs.forEach(function (tab) {
                    tab.addEventListener('click', function () {
                        var name = tab.getAttribute('data-tab');
                        tabs.forEach(function (t) { t.classList.toggle('is-on', t === tab); });
                        panels.forEach(function (p) {
                            p.hidden = p.getAttribute('data-panel') !== name;
                        });
                    });
                });

                function csrf() {
                    var m = document.querySelector('meta[name="csrf-token"]');
                    return m ? m.getAttribute('content') || '' : '';
                }

                function runBridge(btnId) {
                    var btn = document.getElementById(btnId);
                    if (!btn) return;
                    var url = btn.getAttribute('data-url');
                    var map = document.getElementById('bridge-map');
                    var out = document.getElementById('bridge-result');
                    var pre = document.getElementById('bridge-result-pre');
                    btn.addEventListener('click', function () {
                        if (!url) return;
                        map.classList.add('is-probing');
                        out.classList.add('is-open');
                        pre.textContent = 'Executando…';
                        fetch(url, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf(),
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ timeout: 12 })
                        }).then(function (r) {
                            return r.text().then(function (t) {
                                try { return JSON.parse(t); } catch (_) { return { ok: false, parse_error: true, status: r.status, body: t }; }
                            });
                        })
                        .then(function (j) {
                            pre.textContent = JSON.stringify(j, null, 2);
                        }).catch(function (e) {
                            pre.textContent = String(e);
                        }).finally(function () {
                            map.classList.remove('is-probing');
                        });
                    });
                }
                runBridge('btn-bridge-ieducar');
                runBridge('btn-bridge-gestor');
                runBridge('btn-bridge-sms');
            })();
        </script>
    </body>
</html>

