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
        @include('partials.integr-visual-kit')
        <script defer src="/home.js"></script>
        <style>
            .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 12px; }
            .integr-grid { display: grid; gap: 14px; margin-top: 14px; grid-template-columns: 1fr; align-items: stretch; }
            @media (min-width: 640px) { .integr-grid.integr-details__grid { grid-template-columns: repeat(2, 1fr); grid-auto-rows: 1fr; } }
            .int-lanes { display: grid; gap: 16px; margin-top: 0; flex: 1 1 auto; align-content: start; grid-auto-rows: 1fr; }
            @media (min-width: 520px) { .int-lanes { grid-template-columns: 1fr 1fr; } }
            .int-lane { border: 1px solid var(--border); border-radius: 14px; padding: 16px 16px 14px; background: color-mix(in srgb, var(--surface-2) 88%, transparent); min-height: 100%; display: flex; flex-direction: column; }
            .int-lane--in { border-top: 3px solid color-mix(in srgb, var(--accent-a) 75%, var(--border)); }
            .int-lane--out { border-top: 3px solid color-mix(in srgb, var(--accent-c) 75%, var(--border)); }
            .int-lane--in.int-lane--gestor { border-top-color: color-mix(in srgb, #6366f1 70%, var(--border)); }
            .int-lane--out.int-lane--gestor { border-top-color: color-mix(in srgb, #0d9488 70%, var(--border)); }
            .int-lane--in.int-lane--sms { border-top-color: color-mix(in srgb, var(--muted) 55%, var(--border)); }
            .int-lane--out.int-lane--sms { border-top-color: color-mix(in srgb, #0284c7 70%, var(--border)); }
            .int-lane__k { font-size: 10px; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; color: var(--muted); margin-bottom: 8px; }
            .int-lane__status { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; margin-bottom: 12px; }
            .int-lane__status-stack { display: flex; flex-direction: column; align-items: flex-start; gap: 10px; margin-bottom: 12px; width: 100%; }
            .int-lane__status-stack .int-lane__status { margin-bottom: 0; }
            .int-lane__subhint { font-size: 10px; font-weight: 750; letter-spacing: 0.04em; text-transform: uppercase; color: var(--muted); margin: 0 0 2px; }
            .int-lane__badge { display: inline-flex; align-items: center; gap: 7px; padding: 6px 11px; border-radius: 999px; font-size: 11px; font-weight: 750; line-height: 1.2; border: 1px solid var(--border); }
            .int-lane__badge-ico { flex-shrink: 0; opacity: 0.95; }
            .int-lane__badge--pending { color: var(--muted); background: color-mix(in srgb, var(--surface-1) 70%, transparent); border-color: color-mix(in srgb, var(--muted) 28%, var(--border)); }
            .int-lane__badge--ok { color: color-mix(in srgb, var(--text) 88%, var(--accent-c)); background: color-mix(in srgb, var(--accent-c) 14%, transparent); border-color: color-mix(in srgb, var(--accent-c) 42%, var(--border)); }
            .int-lane__badge--bad { color: #ef4444; background: color-mix(in srgb, #ef4444 10%, transparent); border-color: color-mix(in srgb, #ef4444 45%, var(--border)); }
            .int-lane__when { font-size: 10px; font-weight: 650; color: var(--muted); }
            .int-lane__p { margin: 0; font-size: 12px; color: var(--muted); line-height: 1.62; flex: 1 1 auto; min-height: 0; }
            .int-lane__meta { margin-top: 12px; font-size: 11px; line-height: 1.5; }
            .int-lane__actions { margin-top: auto; padding-top: 14px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
            .int-foot { margin-top: 14px; padding-top: 14px; border-top: 1px dashed var(--border); font-size: 11px; color: var(--muted); line-height: 1.5; flex: 0 0 auto; }
            .int-global-actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
            .card { border: 1px solid var(--border); border-radius: 18px; background: var(--card-strong); box-shadow: var(--shadow-soft); padding: 16px 16px 14px; }
            .row { display:flex; align-items:flex-start; justify-content: space-between; gap: 10px; }
            .pill { display:inline-flex; padding: 4px 10px; border-radius: 999px; font-size: 12px; border: 1px solid var(--border); color: var(--muted); }
            .pill--ok { border-color: color-mix(in srgb, var(--accent-c) 45%, var(--border)); background: color-mix(in srgb, var(--accent-c) 12%, transparent); color: color-mix(in srgb, var(--text) 92%, var(--accent-c)); }
            .pill--bad { border-color: color-mix(in srgb, #ef4444 55%, var(--border)); background: color-mix(in srgb, #ef4444 10%, transparent); color: #ef4444; }
            .steps { margin-top: 10px; display: grid; gap: 6px; }
            .step { padding: 8px 10px; border-radius: 14px; border: 1px solid var(--border); background: color-mix(in srgb, var(--bg0) 55%, transparent); }
            .kpi-grid { display: grid; gap: 12px; margin-top: 12px; }
            @media (min-width: 900px) { .kpi-grid { grid-template-columns: 1fr 1fr 1fr 1fr; } }
            .kpi { border: 1px solid var(--border); border-radius: 18px; padding: 14px 16px; background: var(--surface-2); display: flex; flex-direction: column; min-height: 108px; }
            .kpi__k { font-size: 11px; color: var(--muted); letter-spacing: .06em; text-transform: uppercase; font-weight: 700; }
            .kpi__v { font-size: 22px; font-weight: 850; margin-top: 8px; line-height: 1.1; }
            .integr-details { margin-top: 14px; padding: 16px 16px 14px; border-radius: 18px; border: 1px solid var(--border); background: color-mix(in srgb, var(--card-strong) 94%, var(--bg0)); box-shadow: var(--shadow-soft); }
            .integr-details__head { margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border); }
            .integr-details__kpi { margin-top: 0; }
            .integr-details__last { margin-top: 14px; }
            .integr-details__grid { margin-top: 0; }
            .integr-details__connector-lead { margin: 18px 0 14px; font-size: 12px; line-height: 1.55; color: var(--muted); max-width: 960px; }
            .integration-card { display: flex; flex-direction: column; height: 100%; min-height: 0; }
            .integration-card__header { flex: 0 0 auto; }
            .integration-card__header-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
            .integration-card__body { flex: 1 1 auto; display: flex; flex-direction: column; min-height: 0; margin-top: 16px; gap: 14px; }
            .integration-card__footer {
                flex: 0 0 auto;
                margin-top: auto;
                padding-top: 14px;
                border-top: 1px solid color-mix(in srgb, var(--border) 92%, transparent);
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
            }
            .integration-card__footer .bridge-btn { flex-shrink: 0; }
            .integration-card__footer .bridge-muted { flex: 1 1 140px; min-width: 0; line-height: 1.45; text-align: right; }
            @media (max-width: 520px) {
                .integration-card__footer { flex-direction: column-reverse; align-items: stretch; gap: 8px; }
                .integration-card__footer .bridge-muted { text-align: left; }
            }
            .integration-card__head { display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1; }
            .integration-card__title-stack { min-width: 0; flex: 1; }
            .integration-card__title-stack .integr-section__title { line-height: 1.3; }
            .integration-card__title-stack .integr-section__lead { margin-top: 4px; line-height: 1.35; }
            .integration-card__pill { flex-shrink: 0; margin-top: 2px; }
            .integration-card--placeholder {
                border-style: dashed;
                border-color: color-mix(in srgb, var(--muted) 22%, var(--border));
                background: color-mix(in srgb, var(--surface-2) 45%, transparent);
                box-shadow: none;
                min-height: 0;
            }
            .integr-last-card { margin-top: 0; }
            .bar { height: 10px; border-radius: 999px; border: 1px solid var(--border); background: color-mix(in srgb, var(--bg0) 55%, transparent); overflow: hidden; }
            .bar > span { display:block; height: 100%; background: color-mix(in srgb, var(--accent-c) 42%, transparent); }
            .bridge-map { margin-top: 14px; padding: 18px 16px 16px; border-radius: 20px; border: 1px solid color-mix(in srgb, var(--accent-c) 18%, var(--border)); background: linear-gradient(165deg, color-mix(in srgb, var(--card-strong) 92%, var(--bg0)), color-mix(in srgb, var(--surface-2) 35%, var(--card-strong))); box-shadow: 0 10px 36px color-mix(in srgb, var(--text) 5%, transparent); }
            .bridge-map__title { font-weight: 850; font-size: 16px; letter-spacing: -0.02em; }
            .bridge-map__sub { margin-top: 6px; color: var(--muted); font-size: 13px; line-height: 1.5; max-width: 72ch; }
            .bridge-map__sub strong { color: color-mix(in srgb, var(--text) 90%, var(--muted)); }
            .bridge-map__row { margin-top: 14px; display: flex; align-items: stretch; gap: 10px; flex-wrap: wrap; justify-content: center; }
            .bridge-node { flex: 0 1 140px; min-height: 72px; padding: 10px 12px; border-radius: 16px; border: 1px solid var(--border); background: var(--surface-2); display: flex; flex-direction: column; justify-content: center; text-align: center; font-weight: 750; font-size: 13px; }
            .bridge-node--gide { border-color: color-mix(in srgb, var(--accent-c) 40%, var(--border)); box-shadow: 0 0 0 1px color-mix(in srgb, var(--accent-c) 18%, transparent); }
            .bridge-node small { display: block; margin-top: 4px; font-weight: 500; color: var(--muted); font-size: 11px; line-height: 1.35; }
            .bridge-link { flex: 1 1 72px; min-width: 56px; min-height: 36px; position: relative; border-radius: 12px; align-self: center; overflow: hidden; border: 1px solid color-mix(in srgb, var(--accent-c) 25%, var(--border)); background: color-mix(in srgb, var(--bg0) 40%, transparent); }
            .bridge-link--data::after { content: ""; position: absolute; inset: 0; background: linear-gradient(90deg, transparent, color-mix(in srgb, var(--accent-c) 55%, transparent), transparent); transform: translateX(-60%); animation: bridgeFlow 2.2s ease-in-out infinite; opacity: .5; }
            .bridge-link--data.bridge-link--rev::after { animation-name: bridgeFlowRev; }
            .bridge-map.is-probing .bridge-link--data::after,
            .bridge-map.is-probing .bridge-link--data.bridge-link--rev::after { opacity: .92; animation-duration: 1.1s; }
            .bridge-link--data[data-segment-tone="ok"]::after,
            .bridge-link--data[data-segment-tone="ok"].bridge-link--rev::after { background: linear-gradient(90deg, transparent, color-mix(in srgb, var(--accent-c) 52%, transparent), transparent); animation-duration: 2.65s; opacity: .5; }
            .bridge-link--data[data-segment-tone="warn"]::after,
            .bridge-link--data[data-segment-tone="warn"].bridge-link--rev::after { background: linear-gradient(90deg, transparent, color-mix(in srgb, #f59e0b 58%, transparent), transparent); animation-duration: 1.35s; opacity: .78; }
            .bridge-link--data[data-segment-tone="bad"]::after,
            .bridge-link--data[data-segment-tone="bad"].bridge-link--rev::after { animation: none; transform: none; opacity: .22; background: color-mix(in srgb, #ef4444 45%, transparent); }
            .bridge-link--data[data-segment-tone="bad"] { border-color: color-mix(in srgb, #ef4444 38%, var(--border)); }
            .bridge-map[data-tone="bad"] .bridge-link--stub.is-live::after { animation: none; opacity: .15; }
            .bridge-map[data-tone="warn"] .bridge-vstub.is-live::after { animation-duration: 1.5s; opacity: .72; }
            .bridge-map[data-tone="bad"] .bridge-vstub.is-live::after { animation: none; opacity: .2; }
            .bridge-map__status-hint { margin-top: 8px; font-size: 11px; color: var(--muted); font-weight: 600; }
            .bridge-link--stub { border-style: dashed; border-color: color-mix(in srgb, var(--muted) 35%, var(--border)); background: color-mix(in srgb, var(--bg0) 65%, transparent); opacity: .9; }
            .bridge-link--stub::after { display: none; }
            .bridge-link--stub.is-live { border-style: solid; border-color: color-mix(in srgb, var(--accent-c) 28%, var(--border)); opacity: 1; }
            .bridge-link--stub.is-live::after { display: block; content: ""; position: absolute; inset: 0; background: linear-gradient(90deg, transparent, color-mix(in srgb, var(--accent-c) 40%, transparent), transparent); transform: translateX(-60%); animation: bridgeFlow 2.8s ease-in-out infinite; opacity: .35; }
            .bridge-spine-grid { margin-top: 16px; display: grid; grid-template-columns: minmax(108px, 1.1fr) minmax(40px, 0.85fr) minmax(112px, 1.15fr) minmax(40px, 0.85fr) minmax(108px, 1.1fr); gap: 10px 12px; align-items: center; justify-items: stretch; }
            .bridge-spine-grid .bridge-node { width: 100%; min-height: 78px; box-shadow: 0 1px 0 color-mix(in srgb, #fff 10%, transparent); }
            .dark .bridge-spine-grid .bridge-node { box-shadow: 0 1px 0 color-mix(in srgb, #fff 4%, transparent); }
            .bridge-node--gide { background: linear-gradient(180deg, color-mix(in srgb, var(--accent-c) 10%, var(--surface-2)), var(--surface-2)); }
            .bridge-spine-grid .bridge-link { width: 100%; min-width: 0; flex: unset; }
            .bridge-vstub-wrap { display: flex; justify-content: center; align-items: stretch; padding: 2px 0 0; }
            .bridge-vstub { width: 3px; border-radius: 999px; background: color-mix(in srgb, var(--muted) 50%, var(--border)); min-height: 18px; position: relative; overflow: hidden; }
            .bridge-sms-branch { margin-top: 4px; display: flex; flex-direction: column; align-items: center; gap: 0; width: 100%; }
            .bridge-sms-branch.is-sms-chain-ready .bridge-vstub { background: color-mix(in srgb, var(--accent-c) 38%, var(--border)); }
            .bridge-notify-bundle { margin-top: 18px; padding-top: 16px; border-top: 1px dashed color-mix(in srgb, var(--accent-c) 28%, var(--border)); }
            .bridge-notify-head { text-align: center; max-width: 640px; margin: 0 auto 14px; }
            .bridge-notify-kicker { display: inline-flex; align-items: center; gap: 6px; font-size: 10px; font-weight: 850; letter-spacing: 0.1em; text-transform: uppercase; color: color-mix(in srgb, var(--accent-c) 55%, var(--muted)); }
            .bridge-notify-kicker::before { content: ""; width: 6px; height: 6px; border-radius: 999px; background: var(--accent-c); box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent-c) 22%, transparent); }
            .bridge-notify-title { margin: 6px 0 0; font-size: 15px; font-weight: 850; letter-spacing: -0.02em; color: var(--text); }
            .bridge-notify-lead { margin: 6px 0 0; font-size: 12px; line-height: 1.5; color: var(--muted); }
            .bridge-notify-cols { display: flex; flex-wrap: wrap; gap: 14px; align-items: stretch; justify-content: center; }
            .bridge-notify-col { flex: 1 1 280px; max-width: 400px; border-radius: 16px; border: 1px solid var(--border); background: color-mix(in srgb, var(--surface-2) 88%, var(--card-strong)); padding: 14px 14px 12px; display: flex; flex-direction: column; min-height: 0; }
            .bridge-notify-col--sms { border-top: 3px solid #0ea5e9; }
            .bridge-notify-col--wa { border-top: 3px solid #16a34a; }
            .bridge-notify-col--wa.is-wa-roadmap { opacity: 0.92; border-top-color: color-mix(in srgb, #16a34a 45%, var(--border)); }
            .bridge-notify-col__head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
            .bridge-notify-col__h { margin: 0; font-size: 14px; font-weight: 850; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
            .bridge-notify-provider { font-size: 11px; font-weight: 750; padding: 4px 10px; border-radius: 999px; border: 1px solid color-mix(in srgb, #0ea5e9 40%, var(--border)); background: color-mix(in srgb, #0ea5e9 12%, transparent); color: #0369a1; }
            .dark .bridge-notify-provider { color: #7dd3fc; }
            .bridge-notify-chips { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-top: 8px; }
            .bridge-notify-chip { font-size: 10px; font-weight: 750; letter-spacing: 0.03em; text-transform: uppercase; padding: 4px 8px; border-radius: 999px; border: 1px solid var(--border); color: var(--muted); }
            .bridge-notify-chip--ok { border-color: color-mix(in srgb, #059669 45%, var(--border)); color: #047857; background: color-mix(in srgb, #059669 10%, transparent); }
            .dark .bridge-notify-chip--ok { color: #6ee7b7; }
            .bridge-notify-chip--bad { border-color: color-mix(in srgb, #dc2626 45%, var(--border)); color: #b91c1c; background: color-mix(in srgb, #dc2626 10%, transparent); }
            .bridge-notify-chip--wait { border-color: color-mix(in srgb, #64748b 40%, var(--border)); color: var(--muted); }
            .bridge-notify-flow { margin-top: 12px; display: flex; flex-wrap: wrap; align-items: stretch; gap: 6px; justify-content: center; }
            .bridge-notify-step { flex: 1 1 76px; max-width: 118px; padding: 8px 6px; border-radius: 12px; border: 1px solid color-mix(in srgb, var(--border) 95%, transparent); background: color-mix(in srgb, var(--surface-1) 90%, transparent); text-align: center; }
            .bridge-notify-step__n { font-size: 9px; font-weight: 850; letter-spacing: 0.06em; color: var(--muted); text-transform: uppercase; }
            .bridge-notify-step__t { font-size: 11px; font-weight: 800; margin-top: 4px; line-height: 1.25; color: var(--text); }
            .bridge-notify-step__d { font-size: 10px; color: var(--muted); margin-top: 5px; line-height: 1.35; }
            .bridge-notify-chev { flex: 0 0 auto; align-self: center; font-size: 13px; font-weight: 900; color: color-mix(in srgb, var(--muted) 75%, transparent); padding: 0 2px; user-select: none; }
            .bridge-notify-foot { margin-top: 10px; font-size: 11px; line-height: 1.45; color: var(--muted); }
            .bridge-notify-foot code { font-size: 10px; }
            .bridge-vstub.is-live::after { content: ""; position: absolute; left: 0; right: 0; top: -50%; height: 55%; background: linear-gradient(180deg, transparent, color-mix(in srgb, var(--accent-c) 55%, transparent), transparent); animation: bridgeFlowY 2.6s ease-in-out infinite; opacity: .55; }
            .bridge-node__ico { display: flex; justify-content: center; margin-bottom: 6px; }
            .bridge-node__ico svg { display: block; width: 28px; height: 28px; border-radius: 8px; }
            .bridge-node__label { font-weight: 750; font-size: 13px; }
            .integration-card__ico { flex: 0 0 46px; width: 46px; height: 46px; border-radius: 14px; border: 1px solid var(--border); background: var(--surface-2); display: grid; place-items: center; align-self: flex-start; }
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
            /* Botões “Testes rápidos”: ok = funcional, warn = ainda não configurado / atenção, error = falha */
            .bridge-probe-btn { transition: border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease; }
            .bridge-probe-btn[data-probe-state="ok"] {
                border-style: solid;
                border-color: color-mix(in srgb, #059669 52%, var(--border));
                background: color-mix(in srgb, #059669 16%, var(--surface-2));
                box-shadow: 0 0 0 1px color-mix(in srgb, #059669 12%, transparent);
            }
            .bridge-probe-btn[data-probe-state="warn"] {
                border-style: solid;
                border-color: color-mix(in srgb, #64748b 45%, var(--border));
                background: color-mix(in srgb, #64748b 12%, var(--surface-2));
                box-shadow: none;
            }
            .bridge-probe-btn[data-probe-state="error"] {
                border-style: solid;
                border-color: color-mix(in srgb, #dc2626 55%, var(--border));
                background: color-mix(in srgb, #dc2626 14%, var(--surface-2));
                box-shadow: 0 0 0 1px color-mix(in srgb, #dc2626 10%, transparent);
            }
            html[data-theme="dark"] .bridge-probe-btn[data-probe-state="ok"] {
                border-color: color-mix(in srgb, #34d399 42%, var(--border));
                background: color-mix(in srgb, #34d399 12%, rgba(0,0,0,0.2));
            }
            html[data-theme="dark"] .bridge-probe-btn[data-probe-state="warn"] {
                border-color: color-mix(in srgb, #94a3b8 50%, var(--border));
                background: color-mix(in srgb, #94a3b8 14%, rgba(0,0,0,0.15));
            }
            html[data-theme="dark"] .bridge-probe-btn[data-probe-state="error"] {
                border-color: color-mix(in srgb, #f87171 45%, var(--border));
                background: color-mix(in srgb, #f87171 12%, rgba(0,0,0,0.2));
            }
            .bridge-probe-btn[data-probe-state="warn"] svg[stroke],
            .bridge-probe-btn[data-probe-state="error"] svg[stroke] { stroke: color-mix(in srgb, var(--text) 88%, var(--muted)); }
            .bridge-probe-legend { margin-top: 8px; font-size: 11px; color: var(--muted); line-height: 1.45; width: 100%; }
            .bridge-probe-legend__i { display: inline-flex; align-items: center; gap: 6px; margin-right: 14px; margin-top: 4px; }
            .bridge-probe-legend__dot { width: 9px; height: 9px; border-radius: 999px; flex-shrink: 0; }
            .bridge-probe-legend__dot--ok { background: #059669; box-shadow: 0 0 0 1px color-mix(in srgb, #059669 35%, var(--border)); }
            .bridge-probe-legend__dot--warn { background: #64748b; box-shadow: 0 0 0 1px color-mix(in srgb, #64748b 35%, var(--border)); }
            .bridge-probe-legend__dot--err { background: #dc2626; box-shadow: 0 0 0 1px color-mix(in srgb, #dc2626 35%, var(--border)); }
            .bridge-result { margin-top: 10px; padding: 10px 12px; border-radius: 14px; border: 1px solid var(--border); background: var(--surface-2); max-height: 220px; overflow: auto; display: none; }
            .bridge-result.is-open { display: block; }
            .bridge-result pre { margin: 0; white-space: pre-wrap; word-break: break-word; font-size: 12px; }
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
                                <div class="bridge-brand__tagline">Visão geral • Integrações</div>
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
                                <div class="bridge-panel__title">Integrações</div>
                                <div class="bridge-panel__meta">pontes ida e volta • métricas • testes rápidos</div>
                            </div>

                            @if ($integrationsOverviewAdmin ?? false)
                                <x-audit-toolbar style="margin-top: 12px;" />
                            @endif

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
                                $nb = is_array($notifyBridge ?? null) ? $notifyBridge : [];
                                $nbSms = is_array($nb['sms'] ?? null) ? $nb['sms'] : [];
                                $nbWa = is_array($nb['whatsapp'] ?? null) ? $nb['whatsapp'] : [];
                            @endphp
                            <section class="bridge-map" id="bridge-map" aria-labelledby="bridge-map-title" data-tone="{{ $connectionTone ?? 'ok' }}" data-status-url="{{ route('integrations.overview.status') }}">
                                <div class="bridge-map__title" id="bridge-map-title">Mapa da ponte</div>
                                <div class="bridge-map__sub">
                                    <strong>Tronco</strong>: iEducar → GIDE → Gestor (catraca). <strong>Notify</strong> (GIDE): após presença, fila <code class="mono">SendPresenceSms</code> + <code class="mono">SmsService</code> até o cliente SMS configurado; WhatsApp seguirá o mesmo desenho quando a integração existir.
                                    Faixas animadas = tronco ativo; o ramo vertical abaixo liga o GIDE aos canais Notify.
                                </div>
                                <div class="bridge-spine-grid" role="img" aria-label="Fluxo iEducar, GIDE e Gestor">
                                    <div class="bridge-node">
                                        <div class="bridge-node__ico" aria-hidden="true">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="28" height="28"><rect width="32" height="32" rx="8" fill="#1b6b3a"/><path fill="#fff" d="M8 9h2.6v14H8V9zm5.3 0h4.2c3.8 0 6.1 2.1 6.1 5.6 0 3.4-2.3 5.5-6.1 5.5h-1.6V23h-2.6V9zm2.6 2.4v6.3h1.4c2.1 0 3.3-1 3.3-3.1 0-2.2-1.2-3.2-3.3-3.2h-1.4z"/></svg>
                                        </div>
                                        <div class="bridge-node__label">iEducar</div>
                                        <small>Origem: matrícula, facial, frequência (HMAC). Volta: API catraca-frequência (Bearer).</small>
                                    </div>
                                    <div class="bridge-link bridge-link--data bridge-link--lr" data-bridge-segment="ieducar" data-segment-tone="{{ ($mapSegmentTones ?? [])['ieducar'] ?? 'ok' }}" title="Tráfego típico GIDE ↔ iEducar"></div>
                                    <div class="bridge-node bridge-node--gide">
                                        <div class="bridge-node__ico" aria-hidden="true"><img src="/favicon.svg" width="28" height="28" alt="" /></div>
                                        <div class="bridge-node__label">GIDE</div>
                                        <small>Meio: eventos, motor de presença, fila, outbound Gestor e <strong>Notify</strong> (<code class="mono">SendPresenceSms</code> / SMS).</small>
                                    </div>
                                    <div class="bridge-link bridge-link--data bridge-link--lr" data-bridge-segment="gestor" data-segment-tone="{{ ($mapSegmentTones ?? [])['gestor'] ?? 'ok' }}" title="Tráfego típico GIDE ↔ Gestor"></div>
                                    <div class="bridge-node">
                                        <div class="bridge-node__ico" aria-hidden="true">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="28" height="28"><rect width="32" height="32" rx="8" fill="#4338ca"/><path fill="none" stroke="#fff" stroke-width="2" d="M10 11h12M10 16h12M10 21h12M16 9v14"/></svg>
                                        </div>
                                        <div class="bridge-node__label">Gestor (catraca)</div>
                                        <small>Ponta: eventos de acesso (HMAC). Volta: Signin, Invite, Face…</small>
                                    </div>
                                </div>
                                <div class="bridge-sms-branch bridge-notify-bundle {{ $smsChainReady ? 'is-sms-chain-ready' : '' }}">
                                    <div class="bridge-vstub-wrap">
                                        <div class="bridge-vstub {{ $smsChainReady ? 'is-live' : '' }}" title="Ramos Notify (SMS e WhatsApp) a partir do GIDE"></div>
                                    </div>
                                    <div class="bridge-notify-head">
                                        <div class="bridge-notify-kicker">Notify no GIDE</div>
                                        <p class="bridge-notify-title">Canais de notificação após presença</p>
                                        <p class="bridge-notify-lead">
                                            O mesmo gatilho do negócio alimenta SMS hoje (fila + templates). WhatsApp reutiliza o desenho; estado abaixo reflete integração <code class="mono">whatsapp</code> na base (quando existir) e testes de faixa <strong>saída</strong>/<strong>entrada</strong> dos cartões.
                                        </p>
                                    </div>
                                    <div class="bridge-notify-cols">
                                        <div class="bridge-notify-col bridge-notify-col--sms" aria-labelledby="notify-sms-title">
                                            <div class="bridge-notify-col__head">
                                                <h3 class="bridge-notify-col__h" id="notify-sms-title">
                                                    <span aria-hidden="true" style="display:inline-flex;width:26px;height:26px;border-radius:8px;background:#0ea5e9;align-items:center;justify-content:center;">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                                    </span>
                                                    SMS
                                                </h3>
                                                @if (! empty($nbSms['provider_label'] ?? null))
                                                    <span class="bridge-notify-provider" title="integrations.extra.provider">{{ $nbSms['provider_label'] }}</span>
                                                @endif
                                            </div>
                                            <div class="bridge-notify-chips" role="list">
                                                @if ($smsChainReady)
                                                    <span class="bridge-notify-chip bridge-notify-chip--ok" role="listitem">Encadeamento Gestor+SMS OK</span>
                                                @else
                                                    <span class="bridge-notify-chip bridge-notify-chip--wait" role="listitem">Encadeamento: {{ $smsWaitHint ?? 'pendente' }}</span>
                                                @endif
                                                @if (! empty($nbSms['lane_out_tested']))
                                                    <span class="bridge-notify-chip {{ ! empty($nbSms['lane_out_ok']) ? 'bridge-notify-chip--ok' : 'bridge-notify-chip--bad' }}" role="listitem">Teste saída (cartão)</span>
                                                @else
                                                    <span class="bridge-notify-chip bridge-notify-chip--wait" role="listitem">Teste saída: não corrido</span>
                                                @endif
                                                @if (! empty($nbSms['lane_in_tested']))
                                                    <span class="bridge-notify-chip {{ ! empty($nbSms['lane_in_ok']) ? 'bridge-notify-chip--ok' : 'bridge-notify-chip--bad' }}" role="listitem">Teste entrada (cartão)</span>
                                                @endif
                                            </div>
                                            <div class="bridge-notify-flow" aria-label="Fluxo SMS via Notify">
                                                @foreach ($nbSms['steps'] ?? [] as $i => $st)
                                                    @if ($i > 0)
                                                        <span class="bridge-notify-chev" aria-hidden="true">›</span>
                                                    @endif
                                                    <div class="bridge-notify-step" title="{{ $st['d'] ?? '' }}">
                                                        <div class="bridge-notify-step__n">Passo {{ $st['n'] ?? $i + 1 }}</div>
                                                        <div class="bridge-notify-step__t">{{ $st['t'] ?? '—' }}</div>
                                                        <div class="bridge-notify-step__d">{{ $st['d'] ?? '' }}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <p class="bridge-notify-foot">
                                                Cliente HTTP em uso: <code class="mono">{{ $nbSms['provider_client_class'] ?? '—' }}</code>.
                                                Código: <code class="mono">App\Jobs\SendPresenceSms</code> → <code class="mono">App\Services\Sms\SmsService</code>.
                                            </p>
                                        </div>
                                        <div class="bridge-notify-col bridge-notify-col--wa {{ empty($nbWa['has_row'] ?? null) ? 'is-wa-roadmap' : '' }}" aria-labelledby="notify-wa-title">
                                            <div class="bridge-notify-col__head">
                                                <h3 class="bridge-notify-col__h" id="notify-wa-title">
                                                    <span aria-hidden="true" style="display:inline-flex;width:26px;height:26px;border-radius:8px;background:#16a34a;align-items:center;justify-content:center;">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                                                    </span>
                                                    WhatsApp
                                                </h3>
                                                @if (! empty($nbWa['operational']))
                                                    <span class="bridge-notify-provider" style="border-color:color-mix(in srgb,#16a34a 40%,var(--border));background:color-mix(in srgb,#16a34a 12%,transparent);color:#15803d;">Canal verificado</span>
                                                @elseif (! empty($nbWa['has_row']))
                                                    <span class="bridge-notify-provider" style="border-color:color-mix(in srgb,#64748b 40%,var(--border));">Integração na base</span>
                                                @else
                                                    <span class="bridge-notify-provider" style="border-color:color-mix(in srgb,var(--muted) 35%,var(--border));color:var(--muted);">Roadmap</span>
                                                @endif
                                            </div>
                                            <div class="bridge-notify-chips" role="list">
                                                @if (empty($nbWa['has_row']))
                                                    <span class="bridge-notify-chip bridge-notify-chip--wait" role="listitem">Sem <code class="mono">integrations.key=whatsapp</code></span>
                                                @else
                                                    @if (! empty($nbWa['configured']))
                                                        <span class="bridge-notify-chip {{ ! empty($nbWa['enabled']) ? 'bridge-notify-chip--ok' : 'bridge-notify-chip--wait' }}" role="listitem">{{ ! empty($nbWa['enabled']) ? 'Habilitada' : 'Desligada' }}</span>
                                                    @else
                                                        <span class="bridge-notify-chip bridge-notify-chip--wait" role="listitem">Credenciais incompletas</span>
                                                    @endif
                                                    @if (! empty($nbWa['lane_out_tested']))
                                                        <span class="bridge-notify-chip {{ ! empty($nbWa['lane_out_ok']) ? 'bridge-notify-chip--ok' : 'bridge-notify-chip--bad' }}" role="listitem">Teste saída</span>
                                                    @elseif (! empty($nbWa['has_row']))
                                                        <span class="bridge-notify-chip bridge-notify-chip--wait" role="listitem">Teste saída: pendente</span>
                                                    @endif
                                                @endif
                                            </div>
                                            <div class="bridge-notify-flow" aria-label="Fluxo WhatsApp via Notify (referência)">
                                                @foreach ($nbWa['steps'] ?? [] as $i => $st)
                                                    @if ($i > 0)
                                                        <span class="bridge-notify-chev" aria-hidden="true">›</span>
                                                    @endif
                                                    <div class="bridge-notify-step" title="{{ $st['d'] ?? '' }}">
                                                        <div class="bridge-notify-step__n">Passo {{ $st['n'] ?? $i + 1 }}</div>
                                                        <div class="bridge-notify-step__t">{{ $st['t'] ?? '—' }}</div>
                                                        <div class="bridge-notify-step__d">{{ $st['d'] ?? '' }}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <p class="bridge-notify-foot">
                                                @if (! empty($nbWa['operational']))
                                                    Fluxo de WhatsApp considerado <strong>operacional</strong> para este painel: integração presente, habilitada e último teste de <strong>saída</strong> nos cartões OK.
                                                @elseif (! empty($nbWa['has_row']))
                                                    Há registo de integração WhatsApp: complete credenciais, habilite e use os testes de faixa do cartão quando existir na grelha abaixo.
                                                @else
                                                    Estado alinhado a <code class="mono">docs/WHATSAPP_INTEGRACAO_NOTIFICACOES.md</code> — mesmo padrão Notify que o SMS quando o cliente e os jobs forem adicionados.
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="bridge-legend">
                                    <div><span>→</span> <strong>Tronco</strong>: iEducar — GIDE — Gestor (canal principal animado).</div>
                                    <div><span>↓</span> <strong>Notify</strong>: ramos SMS e WhatsApp a partir do GIDE; o traço vertical anima quando o encadeamento Gestor+SMS está pronto (como antes).</div>
                                    <div><span>◎</span> <strong>Status</strong>: tronco usa testes por faixa + sinais operacionais; em Notify, o <strong>cliente SMS</strong> vem de <code class="mono">integrations.extra.provider</code> e os <strong>chips</strong> espelham encadeamento e testes dos cartões. <a href="{{ route('integrations.gide-queues') }}">Filas GIDE</a> · atualização automática a cada <strong>1 minuto</strong>.</div>
                                </div>
                                <p class="bridge-map__status-hint" id="bridge-map-status-hint" aria-live="polite">Tom atual: <span class="mono" id="bridge-map-tone-label">{{ $connectionTone ?? 'ok' }}</span> · próxima verificação em <span id="bridge-map-countdown">60</span>s</p>
                                @if ($integrationsOverviewAdmin ?? false)
                                    @php
                                        $segTones = is_array($mapSegmentTones ?? null) ? $mapSegmentTones : [];
                                        $probeTone = static function (string $t): string {
                                            return $t === 'bad' ? 'error' : $t;
                                        };
                                        $probeBtnIeducar = $probeTone($segTones['ieducar'] ?? 'ok');
                                        $probeBtnGestor = $probeTone($segTones['gestor'] ?? 'ok');
                                        $probeBtnSms = ($smsChainReady ?? false) ? 'ok' : 'warn';
                                    @endphp
                                    <div class="bridge-actions">
                                        <button type="button" class="bridge-btn bridge-btn--ghost bridge-btn--icononly bridge-probe-btn" id="btn-bridge-ieducar" data-probe-state="{{ $probeBtnIeducar }}" data-url="{{ route('integrations.bridge.ieducar') }}" title="Testar ponte iEducar" aria-label="Testar ponte iEducar: rede e API catraca-frequência">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="22" height="22" aria-hidden="true"><rect width="32" height="32" rx="8" fill="#1b6b3a"/><path fill="#fff" d="M8 9h2.6v14H8V9zm5.3 0h4.2c3.8 0 6.1 2.1 6.1 5.6 0 3.4-2.3 5.5-6.1 5.5h-1.6V23h-2.6V9zm2.6 2.4v6.3h1.4c2.1 0 3.3-1 3.3-3.1 0-2.2-1.2-3.2-3.3-3.2h-1.4z"/></svg>
                                        </button>
                                        <button type="button" class="bridge-btn bridge-btn--ghost bridge-btn--icononly bridge-probe-btn" id="btn-bridge-gestor" data-probe-state="{{ $probeBtnGestor }}" data-url="{{ route('integrations.bridge.gestor') }}" title="Testar ponte Gestor" aria-label="Testar ponte Gestor">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                        </button>
                                        <button type="button" class="bridge-btn bridge-btn--ghost bridge-btn--icononly bridge-probe-btn" id="btn-bridge-sms" data-probe-state="{{ $probeBtnSms }}" data-url="{{ route('integrations.bridge.sms') }}" title="Testar ponte SMS" aria-label="Testar ponte SMS">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>
                                        </button>
                                        <span class="bridge-muted" style="font-size:12px;">Testes rápidos (JSON, timeout 12s).</span>
                                    </div>
                                    <div class="bridge-probe-legend" aria-hidden="true">
                                        <span class="bridge-probe-legend__i"><span class="bridge-probe-legend__dot bridge-probe-legend__dot--ok"></span> conexão OK</span>
                                        <span class="bridge-probe-legend__i"><span class="bridge-probe-legend__dot bridge-probe-legend__dot--warn"></span> pendente / não configurado</span>
                                        <span class="bridge-probe-legend__i"><span class="bridge-probe-legend__dot bridge-probe-legend__dot--err"></span> erro no teste</span>
                                    </div>
                                    <div class="bridge-result" id="bridge-result" role="status" aria-live="polite"><pre id="bridge-result-pre"></pre></div>
                                @endif
                            </section>

                            <div class="card integr-section-card" style="margin-top: 12px;">
                                <div class="row integr-section-card__head">
                                    <div>
                                        <h2 class="integr-section__title">Base de dados (GIDE)</h2>
                                        <p class="integr-section__lead" style="margin-top:6px;">Contadores e pendências operacionais nas tabelas do GIDE.</p>
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
                                            <div class="bridge-muted" style="margin-top: 6px;">tabela `jobs` · access-event iEducar pendente: <span class="mono">{{ (int) ($db['gestor_access_event_ieducar_pending'] ?? 0) }}</span> (`gestor_access_event_deliveries`)</div>
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
                                                    + (int) ($db['sms_retry_due'] ?? 0)
                                                    + (int) ($db['gestor_access_event_ieducar_pending'] ?? 0);
                                            @endphp
                                            <div class="kpi__v">{{ $attention === 0 ? 'OK' : $attention }}</div>
                                            <div class="bridge-muted" style="margin-top: 6px;">jobs + retries devidos (fila/cron)</div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <section class="integr-details" aria-labelledby="integr-details-title">
                                <div class="integr-details__head">
                                    <h2 class="integr-section__title" id="integr-details-title">Detalhes da integração</h2>
                                    <p class="integr-section__lead" style="margin-top:6px;">Resumo das integrações cadastradas, último teste manual por conector e cartões de conexão iEducar, Gestor e SMS.</p>
                                </div>
                                @php
                                    $m = is_array($metrics ?? null) ? $metrics : ['total' => 0, 'enabled' => 0, 'configured' => 0, 'not_configured' => 0, 'disabled' => 0];
                                    $total = max(1, (int) ($m['total'] ?? 1));
                                    $pConfigured = (int) round(((int) ($m['configured'] ?? 0) / $total) * 100);
                                    $pEnabled = (int) round(((int) ($m['enabled'] ?? 0) / $total) * 100);
                                @endphp
                                <div class="kpi-grid integr-details__kpi">
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

                            @if (($integrationsOverviewAdmin ?? false) && is_array($lastTest ?? null) && ($lastTestKey ?? ''))
                                @php
                                    $ltLane = (string) data_get($lastTest, 'lane', 'out');
                                    $ltLaneLabel = $ltLane === 'in' ? 'entrada (→ GIDE)' : 'saída (← GIDE)';
                                @endphp
                                <div class="card integr-section-card integr-details__last integr-last-card">
                                    <div class="row integr-section-card__head">
                                        <div>
                                            <div class="integr-section__title" style="font-size:14px;">
                                                Último teste: <span class="mono">{{ $lastTestKey }}</span>
                                                <span class="pill" style="margin-left:8px;">{{ $ltLaneLabel }}</span>
                                            </div>
                                            <div class="integr-section__lead" style="margin-top:6px;font-size:12px;">
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
                            <p class="integr-details__connector-lead">
                                Cada conector é <strong>bidirecional</strong>: o ERP ou a catraca enviam eventos ao GIDE (recepção) e o GIDE chama APIs remotas (saída). Configuração e testes podem diferir em cada sentido.
                            </p>
                            <div class="integr-grid integr-details__grid">
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
                                        $lt = is_array($laneTests ?? null) ? $laneTests : [];
                                    @endphp
                                    <div class="card integration-card">
                                        <div class="integration-card__header">
                                            <div class="integration-card__header-row">
                                                <div class="integration-card__head">
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
                                                    <div class="integration-card__title-stack">
                                                        <div class="integr-section__title" style="font-size:14px;">{{ $it->name ?? $it->key }}</div>
                                                        <div class="bridge-muted mono integr-section__lead" style="margin-top:4px;font-size:11px;">key={{ $it->key }}</div>
                                                    </div>
                                                </div>
                                                <span class="pill integration-card__pill {{ $enabled ? 'pill--ok' : '' }}">{{ $enabled ? 'habilitada' : 'desabilitada' }}</span>
                                            </div>
                                        </div>

                                        <div class="integration-card__body">
                                        @if ($k === 'ieducar')
                                            <div class="int-lanes">
                                                <div class="int-lane int-lane--in">
                                                    <div class="int-lane__k">Recepção → GIDE</div>
                                                    @include('integrations.partials.lane_test_badge', ['entry' => $lt['ieducar:in'] ?? null])
                                                    <p class="int-lane__p">Chamadas do iEducar aos endpoints inbound do GIDE (facial, frequência, etc.). Exige segredo HMAC alinhado ao iEducar.</p>
                                                    <div class="mono bridge-muted int-lane__meta">HMAC: <strong>{{ $hasHmac ? 'configurado' : '(vazio)' }}</strong></div>
                                                    @if ($integrationsOverviewAdmin ?? false)
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
                                                    @endif
                                                </div>
                                                <div class="int-lane int-lane--out">
                                                    <div class="int-lane__k">Saída ← GIDE</div>
                                                    <div class="int-lane__status-stack">
                                                        <div>
                                                            <p class="int-lane__subhint">Reachability HTTP (base_url)</p>
                                                            @include('integrations.partials.lane_test_badge', ['entry' => $lt['ieducar:out'] ?? null])
                                                        </div>
                                                        @if ($cfPersisted)
                                                            <div>
                                                                <p class="int-lane__subhint">Bearer API <span class="mono">catraca_frequencia</span></p>
                                                                @include('integrations.partials.lane_test_badge', ['entry' => $lt['catraca_frequencia:out'] ?? ($catracaFrequenciaPreviewProbe ?? null)])
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <p class="int-lane__p">Chamadas do GIDE ao iEducar (host, API catraca-frequência com Bearer ou token em <span class="mono">extra</span>).</p>
                                                    <div class="mono bridge-muted int-lane__meta">
                                                        base_url: <strong>{{ $hasBase ? 'ok' : '(vazio)' }}</strong>
                                                        · Bearer (integração ieducar): <strong>{{ $hasAuthToken ? 'sim' : 'não' }}</strong>
                                                        @if ($cfPersisted)
                                                            · Bearer (integração <span class="mono">catraca_frequencia</span>): <strong>{{ $cfHasBearer ? 'sim' : 'não' }}</strong>
                                                        @endif
                                                        <br>Token confirmação (extra): <strong>{{ $confirmTok ? 'sim' : 'não' }}</strong>
                                                    </div>
                                                    @if ($integrationsOverviewAdmin ?? false)
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
                                                    @endif
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
                                                    @include('integrations.partials.lane_test_badge', ['entry' => $lt['gestor:in'] ?? null])
                                                    <p class="int-lane__p">Webhooks / eventos da catraca (Gestor) para o GIDE. Depende de <span class="mono">base_url</span> e HMAC de inbound.</p>
                                                    <div class="mono bridge-muted int-lane__meta">URL: <strong>{{ $hasBase ? 'ok' : '(vazio)' }}</strong> · HMAC: <strong>{{ $hasHmac ? 'ok' : '(vazio)' }}</strong></div>
                                                    @if ($integrationsOverviewAdmin ?? false)
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
                                                    @endif
                                                </div>
                                                <div class="int-lane int-lane--out int-lane--gestor">
                                                    <div class="int-lane__k">Saída ← GIDE</div>
                                                    @include('integrations.partials.lane_test_badge', ['entry' => $lt['gestor:out'] ?? null])
                                                    <p class="int-lane__p">SDK: signin e chamadas autenticadas do GIDE para o Gestor (matrícula, face, convites, etc.).</p>
                                                    <div class="mono bridge-muted int-lane__meta">Credenciais SDK conforme formulário Gestor.</div>
                                                    @if ($integrationsOverviewAdmin ?? false)
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
                                                    @endif
                                                </div>
                                            </div>
                                        @elseif ($k === 'sms')
                                            <div class="int-lanes">
                                                <div class="int-lane int-lane--in int-lane--sms">
                                                    <div class="int-lane__k">Recepção → GIDE</div>
                                                    @include('integrations.partials.lane_test_badge', ['entry' => $lt['sms:in'] ?? null])
                                                    <p class="int-lane__p">Sem webhook padrão nesta integração: o fluxo principal é o GIDE enviar SMS após regras de presença.</p>
                                                    @if ($integrationsOverviewAdmin ?? false)
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
                                                    @endif
                                                </div>
                                                <div class="int-lane int-lane--out int-lane--sms">
                                                    <div class="int-lane__k">Saída ← GIDE</div>
                                                    @include('integrations.partials.lane_test_badge', ['entry' => $lt['sms:out'] ?? null])
                                                    <p class="int-lane__p">HTTP autenticado ao provedor (envio e retries na fila).</p>
                                                    <div class="mono bridge-muted int-lane__meta">URL: <strong>{{ $hasBase ? 'ok' : '(vazio)' }}</strong> · Token: <strong>{{ $hasAuthToken ? 'ok' : '(vazio)' }}</strong></div>
                                                    @if ($integrationsOverviewAdmin ?? false)
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
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                        </div>

                                        <div class="integration-card__footer int-global-actions">
                                            @if ($integrationsOverviewAdmin ?? false)
                                                <a class="bridge-btn bridge-btn--icononly" href="{{ $configHref }}" title="{{ $configLabel }}" aria-label="{{ $configLabel }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                                                </a>
                                            @endif
                                            <span class="bridge-muted mono" style="font-size:11px;">Resumo: {{ $configured ? 'há credenciais preenchidas' : 'não configurada' }}</span>
                                        </div>
                                    </div>
                                @endforeach
                                <div class="card integration-card integration-card--placeholder" aria-label="Integração WhatsApp (planejada)">
                                    <div class="integration-card__header">
                                        <div class="integration-card__header-row">
                                            <div class="integration-card__head">
                                                <div class="integration-card__ico" aria-hidden="true">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="28" height="28"><rect width="32" height="32" rx="8" fill="#22c55e"/><path fill="#fff" d="M23.3 8.7A10.4 10.4 0 0 0 8.2 22.6L7 25l2.5-.8a10.3 10.3 0 0 0 13.8-15.5zM16.5 21c-2.4 0-4.6-.9-6.3-2.4l-.4-.4-2.5.8.8-2.4-.5-.5a8.4 8.4 0 1 1 8.9 4.9z"/></svg>
                                                </div>
                                                <div class="integration-card__title-stack">
                                                    <div class="integr-section__title" style="font-size:14px;">WhatsApp (rascunho)</div>
                                                    <div class="bridge-muted integr-section__lead" style="margin-top:4px;font-size:12px;line-height:1.45;">Canal futuro para notificações aos responsáveis.</div>
                                                </div>
                                            </div>
                                            <span class="pill integration-card__pill">em breve</span>
                                        </div>
                                    </div>
                                    <div class="integration-card__body" style="margin-top:0;">
                                        <p class="bridge-muted" style="margin:0;font-size:13px;line-height:1.55;">
                                            Este espaço está reservado para uma integração <strong>WhatsApp</strong> (ex.: API oficial / BSP) com o mesmo objetivo do SMS:
                                            avisar presença, fila e falhas com opt-in e templates aprovados. O envio continuará passando pelo GIDE (mensagem montada no servidor, fila e auditoria),
                                            espelhando o fluxo já usado para SMS após o apontamento de presença.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            </section>

                            @if ($integrationsOverviewAdmin ?? false)
                                <div class="bridge-form__actions" style="margin-top: 14px;">
                                    <a class="bridge-btn" href="/dashboard">Voltar</a>
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
        <script>
            (function () {
                function csrf() {
                    var m = document.querySelector('meta[name="csrf-token"]');
                    return m ? m.getAttribute('content') || '' : '';
                }

                var mapEl = document.getElementById('bridge-map');
                var toneLabelEl = document.getElementById('bridge-map-tone-label');
                function applyBridgeTone(tone) {
                    if (!mapEl) return;
                    var t = (tone === 'ok' || tone === 'warn' || tone === 'bad') ? tone : 'ok';
                    mapEl.setAttribute('data-tone', t);
                    if (toneLabelEl) toneLabelEl.textContent = t;
                }
                function applySegmentTones(tones) {
                    if (!mapEl || !tones || typeof tones !== 'object') return;
                    ['ieducar', 'gestor'].forEach(function (key) {
                        var v = tones[key];
                        if (v !== 'ok' && v !== 'warn' && v !== 'bad') return;
                        var el = mapEl.querySelector('[data-bridge-segment=\"' + key + '\"]');
                        if (el) el.setAttribute('data-segment-tone', v);
                    });
                }
                function applyBridgeStatus(j) {
                    if (!j || typeof j !== 'object') return;
                    if (j.tone) applyBridgeTone(j.tone);
                    if (j.tones) applySegmentTones(j.tones);
                }
                function refreshBridgeMapStatus() {
                    var map = document.getElementById('bridge-map');
                    if (!map) return;
                    var url = map.getAttribute('data-status-url');
                    if (!url) return;
                    fetch(url, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (j) { applyBridgeStatus(j); })
                        .catch(function () {});
                }

                function applyProbeButtonState(btn, j) {
                    if (!btn) return;
                    var st = 'error';
                    if (j && j.probe_state === 'unconfigured') st = 'warn';
                    else if (j && j.probe_state === 'ok') st = 'ok';
                    else if (j && j.probe_state === 'error') st = 'error';
                    else if (j && j.ok === true) st = 'ok';
                    else if (j && j.__httpStatus === 422) st = 'warn';
                    else if (j && j.parse_error) st = 'error';
                    else if (j && j.ok === false) st = 'error';
                    btn.setAttribute('data-probe-state', st);
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
                                var j;
                                try { j = JSON.parse(t); } catch (_) { j = { ok: false, parse_error: true, body: t }; }
                                j.__httpStatus = r.status;
                                return j;
                            });
                        })
                        .then(function (j) {
                            pre.textContent = JSON.stringify(j, null, 2);
                            applyProbeButtonState(btn, j);
                        }).catch(function (e) {
                            pre.textContent = String(e);
                            applyProbeButtonState(btn, { ok: false, probe_state: 'error' });
                        }).finally(function () {
                            map.classList.remove('is-probing');
                            refreshBridgeMapStatus();
                        });
                    });
                }
                runBridge('btn-bridge-ieducar');
                runBridge('btn-bridge-gestor');
                runBridge('btn-bridge-sms');

                (function bridgeStatusRefresh() {
                    var map = document.getElementById('bridge-map');
                    if (!map) return;
                    var url = map.getAttribute('data-status-url');
                    var cdEl = document.getElementById('bridge-map-countdown');
                    if (!url || !cdEl) return;
                    var left = 60;
                    function tick() {
                        left -= 1;
                        if (left < 0) left = 0;
                        cdEl.textContent = String(left);
                        if (left === 0) {
                            fetch(url, {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                credentials: 'same-origin',
                            })
                                .then(function (r) { return r.json(); })
                                .then(function (j) {
                                    applyBridgeStatus(j);
                                })
                                .catch(function () {});
                            left = 60;
                        }
                    }
                    cdEl.textContent = '60';
                    setInterval(tick, 1000);
                })();
            })();
        </script>
    </body>
</html>

