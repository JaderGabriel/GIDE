<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin • Access-event #{{ $delivery->id }} • {{ config('app.name', 'Bridge ERP') }}</title>
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
            .gae-admin {
                --gae-ok: #059669;
                --gae-ok-bg: color-mix(in srgb, #059669 14%, transparent);
                --gae-bad: #dc2626;
                --gae-bad-bg: color-mix(in srgb, #dc2626 12%, transparent);
                --gae-warn: #d97706;
                --gae-warn-bg: color-mix(in srgb, #d97706 14%, transparent);
                --gae-info: #0284c7;
                --gae-info-bg: color-mix(in srgb, #0284c7 12%, transparent);
            }
            .gae-show__id { display: flex; align-items: center; gap: 12px; }
            .gae-show__id-ico { width: 44px; height: 44px; border-radius: 14px; display: grid; place-items: center; border: 1px solid var(--border); background: linear-gradient(145deg, color-mix(in srgb, var(--accent-a) 18%, var(--surface-1)), var(--surface-1)); color: var(--accent-a); }
            .gae-show__id-ico svg { width: 22px; height: 22px; }
            .gae-actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
            .gae-btn { appearance: none; display: inline-flex; align-items: center; gap: 8px; padding: 0 14px; height: 40px; border-radius: 12px; border: 1px solid var(--border); background: var(--surface-1); color: var(--text); font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; transition: background .12s ease, border-color .12s ease; font-family: inherit; }
            .gae-btn:hover { background: color-mix(in srgb, var(--bg0) 82%, transparent); border-color: color-mix(in srgb, var(--accent-a) 28%, var(--border)); text-decoration: none; }
            .gae-btn svg { width: 18px; height: 18px; flex-shrink: 0; }
            .gae-btn--primary { border-color: color-mix(in srgb, var(--accent-a) 35%, var(--border)); background: color-mix(in srgb, var(--accent-a) 10%, var(--surface-1)); color: color-mix(in srgb, var(--text) 80%, var(--accent-a)); }
            .gae-btn--ok { border-color: color-mix(in srgb, var(--gae-ok) 42%, var(--border)); background: color-mix(in srgb, var(--gae-ok) 12%, var(--surface-1)); color: color-mix(in srgb, var(--text) 86%, var(--gae-ok)); }
            .gae-btn--ok:hover { border-color: color-mix(in srgb, var(--gae-ok) 58%, var(--border)); background: color-mix(in srgb, var(--gae-ok) 18%, var(--surface-1)); }
            .gae-btn--warn { border-color: color-mix(in srgb, var(--gae-warn) 42%, var(--border)); background: color-mix(in srgb, var(--gae-warn) 12%, var(--surface-1)); color: color-mix(in srgb, var(--text) 86%, var(--gae-warn)); }
            .gae-btn--warn:hover { border-color: color-mix(in srgb, var(--gae-warn) 58%, var(--border)); background: color-mix(in srgb, var(--gae-warn) 18%, var(--surface-1)); }
            .gae-btn--info { border-color: color-mix(in srgb, var(--gae-info) 42%, var(--border)); background: color-mix(in srgb, var(--gae-info) 12%, var(--surface-1)); color: color-mix(in srgb, var(--text) 86%, var(--gae-info)); }
            .gae-btn--info:hover { border-color: color-mix(in srgb, var(--gae-info) 58%, var(--border)); background: color-mix(in srgb, var(--gae-info) 18%, var(--surface-1)); }
            .gae-btn--danger { border-color: color-mix(in srgb, var(--gae-bad) 45%, var(--border)); background: color-mix(in srgb, var(--gae-bad) 12%, var(--surface-1)); color: color-mix(in srgb, var(--text) 86%, var(--gae-bad)); }
            .gae-btn--danger:hover { border-color: color-mix(in srgb, var(--gae-bad) 62%, var(--border)); background: color-mix(in srgb, var(--gae-bad) 18%, var(--surface-1)); }

            .gae-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 650; border: 1px solid var(--border); line-height: 1.2; }
            .gae-badge--neutral { background: color-mix(in srgb, var(--muted) 8%, transparent); color: var(--muted); }
            .gae-badge--success { border-color: color-mix(in srgb, var(--gae-ok) 42%, var(--border)); background: var(--gae-ok-bg); color: color-mix(in srgb, var(--text) 82%, var(--gae-ok)); }
            .gae-badge--danger { border-color: color-mix(in srgb, var(--gae-bad) 45%, var(--border)); background: var(--gae-bad-bg); color: var(--gae-bad); }
            .gae-badge--warn { border-color: color-mix(in srgb, var(--gae-warn) 40%, var(--border)); background: var(--gae-warn-bg); color: color-mix(in srgb, var(--text) 80%, var(--gae-warn)); }
            .gae-badge--info { border-color: color-mix(in srgb, var(--gae-info) 40%, var(--border)); background: var(--gae-info-bg); color: color-mix(in srgb, var(--text) 82%, var(--gae-info)); }
            .gae-badge-row { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }

            .gae-sms-result-row { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-top: 0; }
            .gae-sms-result-row > .gae-badge { min-height: 28px; box-sizing: border-box; display: inline-flex; align-items: center; }
            .gae-sms-result-row__http { font-size: 11px; margin-left: 2px; }
            .gae-sms-flow--inline { display: inline-flex; align-items: center; gap: 4px; margin: 0; max-width: none; }
            .gae-sms-flow--inline .gae-sms-flow__sep { color: var(--muted); font-size: 12px; font-weight: 800; line-height: 1; padding: 0 1px; user-select: none; }
            .gae-sms-flow__icon { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 8px; border: 1px solid var(--border); flex-shrink: 0; box-sizing: border-box; }
            .gae-sms-flow__icon svg { width: 16px; height: 16px; }
            .gae-sms-flow__icon--success { background: var(--gae-ok-bg); color: color-mix(in srgb, var(--text) 78%, var(--gae-ok)); border-color: color-mix(in srgb, var(--gae-ok) 42%, var(--border)); }
            .gae-sms-flow__icon--danger { background: var(--gae-bad-bg); color: var(--gae-bad); border-color: color-mix(in srgb, var(--gae-bad) 45%, var(--border)); }
            .gae-sms-flow__icon--warn { background: var(--gae-warn-bg); color: color-mix(in srgb, var(--text) 80%, var(--gae-warn)); border-color: color-mix(in srgb, var(--gae-warn) 40%, var(--border)); }
            .gae-sms-flow__icon--neutral { background: color-mix(in srgb, var(--muted) 10%, transparent); color: var(--muted); border-color: color-mix(in srgb, var(--muted) 25%, var(--border)); }
            .gae-sms-flow__icon--na { background: color-mix(in srgb, var(--muted) 6%, transparent); color: color-mix(in srgb, var(--muted) 88%, var(--text)); border-style: dashed; }
            .gae-sms-legend { margin-top: 14px; padding: 12px 14px; border-radius: 12px; border: 1px dashed color-mix(in srgb, var(--border) 90%, transparent); font-size: 12px; color: var(--muted); line-height: 1.55; }
            .gae-sms-legend strong { color: var(--text); font-weight: 750; }
            .gae-sms-legend .gae-sms-flow__icon { width: 24px; height: 24px; margin: 0 3px !important; vertical-align: middle; }
            .gae-sms-legend .gae-sms-flow__icon svg { width: 14px; height: 14px; }

            .gae-callout { margin-top: 16px; padding: 14px 16px; border-radius: 16px; border: 1px solid var(--border); font-size: 14px; line-height: 1.55; background: color-mix(in srgb, var(--surface-2) 70%, transparent); }
            .gae-callout--info { border-color: color-mix(in srgb, var(--gae-info) 35%, var(--border)); background: color-mix(in srgb, var(--gae-info) 8%, var(--surface-1)); }
            .gae-callout--warn { border-color: color-mix(in srgb, var(--gae-warn) 38%, var(--border)); background: color-mix(in srgb, var(--gae-warn) 10%, var(--surface-1)); }
            .gae-callout--danger { border-color: color-mix(in srgb, var(--gae-bad) 40%, var(--border)); background: color-mix(in srgb, var(--gae-bad) 8%, var(--surface-1)); }
            .gae-callout strong { font-weight: 750; }

            .gae-grid { margin-top: 18px; display: grid; gap: 14px; grid-template-columns: 1fr; }
            @media (min-width: 960px) { .gae-grid { grid-template-columns: 1fr 1fr; } }
            .gae-card { border: 1px solid var(--border); border-radius: 18px; padding: 16px 16px 14px; background: var(--card-strong); box-shadow: var(--shadow-soft); }
            .gae-card__head { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid var(--border); }
            .gae-card__head svg { width: 20px; height: 20px; color: var(--accent-a); flex-shrink: 0; }
            .gae-card__title { font-weight: 800; font-size: 14px; margin: 0; }
            .gae-card__hint { font-size: 12px; color: var(--muted); margin: 4px 0 0; }

            .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 12px; }
            .gae-json { margin-top: 10px; padding: 12px; border-radius: 14px; border: 1px solid var(--border); background: color-mix(in srgb, var(--bg0) 72%, transparent); white-space: pre-wrap; word-break: break-word; max-height: min(48vh, 480px); overflow: auto; }

            .gae-json-grid { margin-top: 14px; display: grid; gap: 14px; grid-template-columns: 1fr; }
            @media (min-width: 960px) { .gae-json-grid { grid-template-columns: 1fr 1fr; } }
            .gae-json-grid > .gae-card { margin-top: 0; }

            .gae-reproc-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 10px; }
            .gae-reproc-table th { text-align: left; font-weight: 700; padding: 6px 8px; border-bottom: 2px solid var(--border); font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); }
            .gae-reproc-table td { padding: 6px 8px; border-bottom: 1px solid color-mix(in srgb, var(--border) 50%, transparent); vertical-align: top; }
            .gae-reproc-action { display: inline-block; padding: 2px 8px; border-radius: 6px; font-weight: 650; font-size: 11px; }
            .gae-reproc-action--reevaluate { background: var(--gae-warn-bg); border: 1px solid color-mix(in srgb, var(--gae-warn) 35%, var(--border)); color: color-mix(in srgb, var(--text) 80%, var(--gae-warn)); }
            .gae-reproc-action--retry { background: var(--gae-bad-bg); border: 1px solid color-mix(in srgb, var(--gae-bad) 35%, var(--border)); color: color-mix(in srgb, var(--text) 80%, var(--gae-bad)); }
            .gae-reproc-action--requeue { background: var(--gae-info-bg); border: 1px solid color-mix(in srgb, var(--gae-info) 35%, var(--border)); color: color-mix(in srgb, var(--text) 80%, var(--gae-info)); }
            .gae-reproc-action--force { background: var(--gae-ok-bg); border: 1px solid color-mix(in srgb, var(--gae-ok) 35%, var(--border)); color: color-mix(in srgb, var(--text) 80%, var(--gae-ok)); }

            .gae-sms-flash-success {
                margin-top: 14px;
                padding: 18px 20px;
                border-radius: 16px;
                border: 1px solid color-mix(in srgb, var(--gae-ok) 45%, var(--border));
                background: linear-gradient(135deg, color-mix(in srgb, var(--gae-ok) 16%, var(--surface-1)), color-mix(in srgb, var(--gae-ok) 6%, var(--card-strong)));
                box-shadow: 0 8px 28px color-mix(in srgb, var(--gae-ok) 12%, transparent);
                display: flex;
                align-items: flex-start;
                gap: 14px;
                font-size: 15px;
                line-height: 1.55;
                font-weight: 650;
                color: color-mix(in srgb, var(--text) 88%, var(--gae-ok));
            }
            .gae-sms-flash-success svg { width: 28px; height: 28px; flex-shrink: 0; color: var(--gae-ok); margin-top: 2px; }
            .gae-sms-flash-success__meta { font-size: 12px; font-weight: 600; color: var(--muted); margin-top: 8px; }

            .gae-sms-panel { margin-top: 14px; border: 1px solid var(--border); border-radius: 18px; padding: 4px 0 4px; background: var(--card-strong); box-shadow: var(--shadow-soft); overflow: hidden; }
            .gae-sms-panel__head { display: flex; align-items: flex-start; gap: 12px; padding: 16px 18px 12px; border-bottom: 1px solid var(--border); background: color-mix(in srgb, var(--surface-2) 55%, transparent); }
            .gae-sms-panel__head svg { width: 22px; height: 22px; color: var(--gae-info); flex-shrink: 0; margin-top: 2px; }
            .gae-sms-panel__title { font-weight: 800; font-size: 15px; margin: 0; }
            .gae-sms-panel__hint { font-size: 12px; color: var(--muted); margin: 4px 0 0; line-height: 1.45; }

            .gae-sms-action { margin: 12px 14px 14px; padding: 14px 16px; border-radius: 14px; border: 1px solid var(--border); background: color-mix(in srgb, var(--bg0) 35%, var(--surface-1)); }
            .gae-sms-action__row { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
            .gae-sms-action--config { border-left: 4px solid var(--gae-info); }
            .gae-sms-action--guardians { border-left: 4px solid var(--gae-ok); }
            .gae-sms-action__label { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 750; color: var(--text); margin-bottom: 8px; }
            .gae-sms-action__label svg { width: 18px; height: 18px; flex-shrink: 0; opacity: 0.9; }
            .gae-sms-action--config .gae-sms-action__label svg { color: var(--gae-info); }
            .gae-sms-action--guardians .gae-sms-action__label svg { color: var(--gae-ok); }
            .gae-sms-action__phones { font-size: 12px; color: var(--muted); margin: 0 0 10px; line-height: 1.45; }
            .gae-sms-action__phones .mono { font-weight: 600; color: color-mix(in srgb, var(--text) 75%, var(--muted)); }
        </style>
    </head>
    <body>
        @php
            $marker = is_array($delivery->ieducar_marker_summary ?? null) ? $delivery->ieducar_marker_summary : [];
            $markerStatus = $marker['status'] ?? null;
            $markerReason = $marker['reason'] ?? null;
            $analysisAction = data_get($delivery->analysis_json, 'action');
            $st = $delivery->processing_status;
        @endphp
        <div class="bridge-shell gae-admin integr-app">
            <header class="bridge-header">
                <div class="bridge-container">
                    <div class="bridge-header__inner">
                        <a class="bridge-brand" href="{{ url('/dashboard') }}">
                            <img src="/favicon.svg" alt="" class="bridge-brand__logo" />
                            <div class="bridge-brand__text">
                                <div class="bridge-brand__name">{{ config('app.name', 'Bridge ERP') }}</div>
                                <div class="bridge-brand__tagline">Admin • Webhook access-events</div>
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
                                <div class="gae-show__id">
                                    <div class="gae-show__id-ico" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>
                                    </div>
                                    <div>
                                        <h1 class="integr-section__title">Entrega #{{ $delivery->id }}</h1>
                                        <p class="integr-section__lead">Auditoria Gestor/catraca → motor de presença → preview catraca-frequência (se aplicável)</p>
                                        <div class="gae-badge-row">
                                            <span class="gae-badge gae-badge--neutral mono">{{ $delivery->inbound_channel ?? 'gestor_hmac' }}</span>
                                            @if ($st === \App\Models\GestorAccessEventDelivery::STATUS_COMPLETED)
                                                <span class="gae-badge gae-badge--success">{{ $st }}</span>
                                            @elseif ($st === \App\Models\GestorAccessEventDelivery::STATUS_FAILED)
                                                <span class="gae-badge gae-badge--danger">{{ $st }}</span>
                                            @elseif ($st === \App\Models\GestorAccessEventDelivery::STATUS_SKIPPED)
                                                <span class="gae-badge gae-badge--warn">{{ $st }}</span>
                                            @elseif ($st === \App\Models\GestorAccessEventDelivery::STATUS_PROCESSING)
                                                <span class="gae-badge gae-badge--info">{{ $st }}</span>
                                            @else
                                                <span class="gae-badge gae-badge--info">{{ $st }}</span>
                                            @endif
                                            <span class="gae-badge gae-badge--neutral">HTTP iEducar {{ $delivery->ieducar_frequencia_http_status ?? '—' }}</span>
                                            <span class="gae-badge gae-badge--neutral">rótulo Gestor <span class="mono">{{ $delivery->gestor_ie_environment }}</span></span>
                                        </div>
                                    </div>
                                </div>
                                </div>
                            </div>
                            <x-audit-toolbar style="margin-top: 12px;">
                                <x-slot:left>
                                    <a class="gae-btn" href="{{ route('admin.gestor-access-events.index') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                                        Lista
                                    </a>
                                    <a class="gae-btn gae-btn--primary" href="{{ route('admin.ieducar-frequencia-deliveries.index') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/></svg>
                                        Fila frequência iEducar
                                    </a>
                                    @if ($ieducarEnabled)
                                        <form method="post" action="{{ route('admin.gestor-access-events.force-mark-presence', ['id' => $delivery->id]) }}" style="display:inline;" onsubmit="return confirm('Reavaliar pelo motor de presença e, se aprovado, reenviar ao iEducar?');">
                                            @csrf
                                            <button type="submit" class="gae-btn gae-btn--warn" title="Reavalia o evento pelo motor de presença; só enfileira envio ao iEducar se action=mark_presence">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                                Reavaliar presença
                                            </button>
                                        </form>
                                    @endif
                                    @if ($ieducarEnabled && $st === \App\Models\GestorAccessEventDelivery::STATUS_PENDING)
                                        <form method="post" action="{{ route('admin.gestor-access-events.requeue', ['id' => $delivery->id]) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="gae-btn gae-btn--info" title="Reenfileira a entrega pendente (útil quando o worker não drenou a fila)">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                                                Reenfileirar pendente
                                            </button>
                                        </form>
                                        <form method="post" action="{{ route('admin.gestor-access-events.force-process', ['id' => $delivery->id]) }}" style="display:inline;" onsubmit="return confirm('Executar o processamento agora (sync)?');">
                                            @csrf
                                            <button type="submit" class="gae-btn gae-btn--ok" title="Executa o processamento imediatamente (sem depender do worker)">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                                Forçar processamento
                                            </button>
                                        </form>
                                    @endif
                                    @if ($ieducarEnabled && in_array($st, [\App\Models\GestorAccessEventDelivery::STATUS_FAILED, \App\Models\GestorAccessEventDelivery::STATUS_PROCESSING], true))
                                        <form method="post" action="{{ route('admin.gestor-access-events.retry', ['id' => $delivery->id]) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="gae-btn gae-btn--danger" title="Enfileira novamente o envio ao iEducar">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                                                Reenviar ao iEducar
                                            </button>
                                        </form>
                                    @endif
                                    @php $showCodAluno = (int) data_get($delivery->analysis_json, 'aluno_id', data_get($delivery->inbound_payload, 'aluno_id', 0)); @endphp
                                    @if ($showCodAluno > 0)
                                        <a class="gae-btn gae-btn--info" href="{{ route('admin.student-timeline', ['cod_aluno' => $showCodAluno]) }}" title="Ver todos os eventos deste aluno">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                            Timeline #{{ $showCodAluno }}
                                        </a>
                                    @endif
                                </x-slot:left>
                            </x-audit-toolbar>

                            @if (session('sms_success'))
                                <div class="gae-sms-flash-success" role="status" aria-live="polite">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                    <div>
                                        <div>{{ session('sms_success') }}</div>
                                        <div class="gae-sms-flash-success__meta">Se precisar de outro envio, pode voltar a usar o painel de reenvio SMS mais abaixo nesta página.</div>
                                    </div>
                                </div>
                            @endif
                            @if (session('status'))
                                <div class="gae-callout gae-callout--info" style="margin-top: 12px;" role="status">{{ session('status') }}</div>
                            @endif
                            @if ($errors->has('retry'))
                                <div class="gae-callout gae-callout--danger" style="margin-top: 12px;" role="alert">{{ $errors->first('retry') }}</div>
                            @endif
                            @if ($errors->has('sms'))
                                <div class="gae-callout gae-callout--danger" style="margin-top: 12px;" role="alert">{{ $errors->first('sms') }}</div>
                            @endif

                            <div class="gae-callout gae-callout--info">
                                <strong>Porque pode diferir de “Frequência iEducar” no admin:</strong>
                                esta página reflete <strong>um POST</strong> do webhook (<span class="mono">gestor_access_event_deliveries</span>).
                                O POST de preview ao iEducar só ocorre se (1) a integração <span class="mono">ieducar</span> existir e estiver <strong>habilitada</strong>
                                (<span class="mono">enabled=true</span> agora: {{ $ieducarEnabled ? 'sim' : 'não' }}),
                                (2) o motor de presença devolver <span class="mono">action=mark_presence</span>
                                (neste evento: <span class="mono">{{ $analysisAction !== null && $analysisAction !== '' ? $analysisAction : '—' }}</span>),
                                (3) existir <span class="mono">cod_aluno</span> válido após o mapeamento do payload.
                                Por padrão, o motor considera presença <strong>permitida</strong> quando <span class="mono">action.mark_presence</span> não vem; ele só bloqueia quando <span class="mono">action.mark_presence=false</span> é declarado.
                                A rota <span class="mono">/admin/frequencia-ieducar</span> lista outra tabela (<span class="mono">ieducar_frequencia_registro_deliveries</span>) — fluxos que enfileiram registo diretamente; sucesso lá não implica que este evento tenha enviado JSON ao iEducar.
                            </div>

                            <div class="gae-callout" style="margin-top: 12px;">
                                <strong>Modo técnico:</strong> quando há POST ao iEducar, usa-se <span class="mono">meta.preview</span> conforme o setup em <span class="mono">/integracoes/gestor</span> (Presença). Aqui: <span class="mono">{{ $delivery->ieducar_preview_only ? 'true' : 'false' }}</span>.
                            </div>

                            @php $enrichment = data_get($delivery->analysis_json, 'enrichment'); @endphp
                            @if (is_array($enrichment) && ($enrichment['nome'] ?? $enrichment['turma'] ?? $enrichment['serie'] ?? null))
                                <div class="gae-card" style="margin-top: 14px; border-color: color-mix(in srgb, var(--accent-a) 25%, var(--border)); background: color-mix(in srgb, var(--accent-a) 4%, var(--card-strong));">
                                    <div class="gae-card__head">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        <div>
                                            <h2 class="gae-card__title">Dados do aluno (cache iEducar)</h2>
                                            <p class="gae-card__hint">Enriquecido automaticamente via consulta ao iEducar.</p>
                                        </div>
                                    </div>
                                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 8px; font-size: 13px;">
                                        @foreach (['nome' => 'Nome', 'curso' => 'Curso', 'turma' => 'Turma', 'serie' => 'Série', 'etapa' => 'Etapa', 'situacao' => 'Situação', 'matricula_id' => 'Matrícula'] as $key => $label)
                                            @if ($enrichment[$key] ?? null)
                                                <div>
                                                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--muted);">{{ $label }}</div>
                                                    <div style="font-weight: 600;">{{ $enrichment[$key] }}</div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @php
                                $tsInfo = data_get($delivery->analysis_json, 'timestamp_info');
                                $interpretedUtc = is_array($tsInfo) && ($tsInfo['interpreted_as_utc'] ?? false);
                                $tzDeclared = is_array($tsInfo) ? ($tsInfo['tz_declared'] ?? true) : true;
                                $tsCardToneOk = $interpretedUtc || $tzDeclared;
                            @endphp
                            @if (is_array($tsInfo) && ($tsInfo['raw'] ?? null))
                                <div class="gae-card" style="margin-top: 14px; border-color: color-mix(in srgb, {{ $tsCardToneOk ? '#0891b2' : '#d97706' }} 25%, var(--border)); background: color-mix(in srgb, {{ $tsCardToneOk ? '#0891b2' : '#d97706' }} 4%, var(--card-strong));">
                                    <div class="gae-card__head">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        <div>
                                            <h2 class="gae-card__title">Horário do evento</h2>
                                            <p class="gae-card__hint">Timestamp original da catraca normalizado para o fuso da aplicação.</p>
                                        </div>
                                    </div>
                                    @if ($interpretedUtc)
                                        <div style="margin-bottom: 10px; padding: 8px 10px; border-radius: 8px; background: color-mix(in srgb, #0891b2 10%, transparent); border: 1px solid color-mix(in srgb, #0891b2 30%, var(--border)); font-size: 12px; color: #0e7490; line-height: 1.5;">
                                            <strong>Fuso não declarado no JSON da catraca.</strong>
                                            O valor de <span class="mono">creationDate</span> (ou equivalente) não traz sufixo <span class="mono">Z</span> nem offset (<span class="mono">±HH:MM</span>).
                                            O sistema <strong>assume UTC (fuso 0)</strong> e converte para <span class="mono">{{ config('app.timezone', 'America/Sao_Paulo') }}</span>.
                                        </div>
                                    @elseif (! $tzDeclared)
                                        <div style="margin-bottom: 10px; padding: 8px 10px; border-radius: 8px; background: color-mix(in srgb, #d97706 10%, transparent); border: 1px solid color-mix(in srgb, #d97706 30%, var(--border)); font-size: 12px; color: #92400e; line-height: 1.5;">
                                            <strong>Fuso horário não declarado no payload original.</strong>
                                            O valor não contém indicador de timezone (ex: <span class="mono">+00:00</span>, <span class="mono">Z</span>, <span class="mono">-03:00</span>).
                                            O sistema assumiu que o horário já está em <span class="mono">{{ config('app.timezone', 'America/Sao_Paulo') }}</span>.
                                            Se a origem opera noutro fuso, o horário normalizado pode estar incorreto.
                                        </div>
                                    @endif
                                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px; font-size: 13px;">
                                        <div>
                                            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--muted);">Valor original</div>
                                            <div class="mono" style="font-weight: 600;">{{ $tsInfo['raw'] }}</div>
                                        </div>
                                        <div>
                                            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--muted);">Fuso / interpretação</div>
                                            <div class="mono" style="font-weight: 600;">
                                                @if ($tzDeclared)
                                                    {{ $tsInfo['original_tz'] }}
                                                @elseif ($interpretedUtc)
                                                    <span class="mono">+00:00</span> <span style="font-size: 12px; color: var(--muted); font-weight: 600;">(UTC assumido)</span>
                                                @else
                                                    <span style="color: #d97706;">n/d (sem fuso declarado)</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div>
                                            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--muted);">Normalizado ({{ config('app.timezone', 'America/Sao_Paulo') }})</div>
                                            <div class="mono" style="font-weight: 600;">{{ $tsInfo['normalized_br'] ?? '—' }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if (! $smsIntegrationEnabled)
                                <div class="gae-callout" style="margin-top: 12px;">
                                    <strong>SMS:</strong> integração desligada — ative em <a href="{{ route('integrations.sms') }}">Integrações → SMS</a> para ver opções de reenvio aqui.
                                </div>
                            @elseif (! $smsTemplateCatracaEnabled && ! $smsTemplateIeducarEnabled)
                                <div class="gae-callout gae-callout--warn" style="margin-top: 12px;">
                                    <strong>SMS:</strong> integração ligada, mas nenhum template de presença está ativo. Ative o template desejado em <a href="{{ route('integrations.sms') }}">Integrações → SMS</a>.
                                </div>
                            @else
                                <div class="gae-sms-panel" style="margin-top: 14px;">
                                    <div class="gae-sms-panel__head">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                        <div>
                                            <h2 class="gae-sms-panel__title">SMS — reenvio manual</h2>
                                            <p class="gae-sms-panel__hint">Usa o <strong>payload</strong> e a <strong>análise</strong> desta entrega. O envio é <strong>imediato</strong> (não passa pela fila).</p>
                                        </div>
                                    </div>

                                    <div class="gae-sms-action gae-sms-action--config">
                                        <div class="gae-sms-action__label">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 1v6m0 6v6M4.22 4.22l4.24 4.24m5.08 5.08l4.24 4.24M1 12h6m6 0h6M4.22 19.78l4.24-4.24m5.08-5.08l4.24-4.24"/></svg>
                                            <span>Conforme a integração</span>
                                        </div>
                                        <p class="bridge-muted" style="margin: 0 0 12px; font-size: 13px; line-height: 1.5;">Respeita modo alunos vs testes e a chave de telefone em <span class="mono">/integracoes/sms</span>. Template <span class="mono">presence_ieducar_sync</span> usa o HTTP registado nesta entrega (ou <span class="mono">—</span> se ausente).</p>
                                        <form method="post" action="{{ route('admin.gestor-access-events.sms-resend-config', ['id' => $delivery->id]) }}" class="gae-sms-action__row" onsubmit="return confirm('Reenviar SMS conforme a integração atual?');">
                                            @csrf
                                            <label class="bridge-muted" style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;">Template</label>
                                            <select name="template" class="bridge-input" style="height: 40px; min-width: 220px;">
                                                @if ($smsTemplateCatracaEnabled)
                                                    <option value="presence_catraca">Presença na catraca</option>
                                                @endif
                                                @if ($smsTemplateIeducarEnabled)
                                                    <option value="presence_ieducar_sync">Confirmação no iEducar</option>
                                                @endif
                                            </select>
                                            <button type="submit" class="gae-btn gae-btn--primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" aria-hidden="true"><path d="M22 2 11 13"/><path d="M22 2 15 22 11 13 2 9 22 2z"/></svg>
                                                Enviar (configuração)
                                            </button>
                                        </form>
                                    </div>

                                    @if (count($smsGuardianMasked ?? []) > 0)
                                        <div class="gae-sms-action gae-sms-action--guardians">
                                            <div class="gae-sms-action__label">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                                <span>Para responsáveis (telefone no payload)</span>
                                            </div>
                                            <p class="gae-sms-action__phones">Números detectados (mascarados): <span class="mono">{{ implode(', ', $smsGuardianMasked) }}</span></p>
                                            <p class="bridge-muted" style="margin: 0 0 12px; font-size: 12px; line-height: 1.45;">Ignora o modo testes/alunos da integração e envia para <strong>todos</strong> os números listados acima.</p>
                                            <form method="post" action="{{ route('admin.gestor-access-events.sms-resend-guardians', ['id' => $delivery->id]) }}" class="gae-sms-action__row" onsubmit="return confirm('Enviar SMS para todos os telefones de responsável listados?');">
                                                @csrf
                                                <label class="bridge-muted" style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;">Template</label>
                                                <select name="template" class="bridge-input" style="height: 40px; min-width: 220px;">
                                                    @if ($smsTemplateCatracaEnabled)
                                                        <option value="presence_catraca">Presença na catraca</option>
                                                    @endif
                                                    @if ($smsTemplateIeducarEnabled)
                                                        <option value="presence_ieducar_sync">Confirmação no iEducar</option>
                                                    @endif
                                                </select>
                                                <button type="submit" class="gae-btn gae-btn--ok">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" aria-hidden="true"><path d="M22 2 11 13"/><path d="M22 2 15 22 11 13 2 9 22 2z"/></svg>
                                                    Enviar (responsáveis)
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <div class="gae-grid">
                                <div class="gae-card">
                                    <div class="gae-card__head">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        <div>
                                            <h2 class="gae-card__title">Resumo</h2>
                                            <p class="gae-card__hint">Identificação e ligação a <span class="mono">access_events</span>.</p>
                                        </div>
                                    </div>
                                    <div class="mono" style="line-height:1.65;">
                                        <div><span class="bridge-muted">event_id</span> {{ $delivery->event_id }}</div>
                                        <div><span class="bridge-muted">access_event novo neste POST</span> {{ $delivery->access_event_was_created ? 'sim' : 'não' }}</div>
                                        @if ($delivery->accessEvent)
                                            <div><span class="bridge-muted">access_events.id</span> {{ $delivery->accessEvent->id }}</div>
                                        @endif
                                        <div><span class="bridge-muted">processado em</span> {{ $delivery->processed_at?->timezone(config('app.timezone'))->format('d/m/Y H:i:s') ?? '—' }}</div>
                                    </div>
                                </div>
                                <div class="gae-card">
                                    <div class="gae-card__head">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                        <div>
                                            <h2 class="gae-card__title">Resultado iEducar (preview)</h2>
                                            <p class="gae-card__hint">Marker interno após tentativa ou skip.</p>
                                        </div>
                                    </div>
                                    <pre class="gae-json mono">{{ json_encode($marker, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>
                            </div>

                            <div class="gae-json-grid">
                                <div class="gae-card">
                                    <div class="gae-card__head">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        <div>
                                            <h2 class="gae-card__title">JSON recebido (payload bruto)</h2>
                                            <p class="gae-card__hint">Corpo do webhook guardado para auditoria.</p>
                                        </div>
                                    </div>
                                    <pre class="gae-json mono">{{ json_encode($delivery->inbound_payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>

                                <div class="gae-card">
                                    <div class="gae-card__head">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                        <div>
                                            <h2 class="gae-card__title">Análise (motor de presença)</h2>
                                            <p class="gae-card__hint">Inclui metadados de canal e decisão do motor.</p>
                                        </div>
                                    </div>
                                    <pre class="gae-json mono">{{ json_encode(\Illuminate\Support\Arr::except(is_array($delivery->analysis_json) ? $delivery->analysis_json : [], ['enrichment']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>

                                <div class="gae-card">
                                    <div class="gae-card__head">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                        <div>
                                            <h2 class="gae-card__title">JSON enviado ao iEducar (catraca-frequência)</h2>
                                            <p class="gae-card__hint">Só existe quando foi montado o body Plan B e feito POST em preview.</p>
                                        </div>
                                    </div>
                                    @if ($delivery->ieducar_frequencia_request_json)
                                        <pre class="gae-json mono">{{ json_encode($delivery->ieducar_frequencia_request_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                        @if ($delivery->ieducar_frequencia_error)
                                            <div class="gae-callout gae-callout--danger" style="margin-top: 12px;">
                                                <strong>Erro ou resposta não OK:</strong> {{ $delivery->ieducar_frequencia_error }}
                                            </div>
                                        @endif
                                    @elseif ($delivery->ieducar_frequencia_error)
                                        <div class="gae-callout gae-callout--danger" style="margin-top: 0;">
                                            <strong>Sem corpo de envio ou chamada falhou:</strong> {{ $delivery->ieducar_frequencia_error }}
                                        </div>
                                    @elseif ($markerStatus === 'skipped' && $markerReason)
                                        <div class="gae-callout gae-callout--warn" style="margin-top: 0;">
                                            <strong>Não houve POST ao iEducar neste processamento.</strong> {{ $markerReason }}
                                        </div>
                                    @else
                                        <p class="bridge-muted" style="margin:0;">Sem payload de envio registado. Consulte o resumo acima e a análise do motor.</p>
                                    @endif
                                </div>

                                <div class="gae-card">
                                    <div class="gae-card__head">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                                        <div>
                                            <h2 class="gae-card__title">Resposta HTTP / corpo iEducar</h2>
                                            <p class="gae-card__hint">Resposta JSON ou excerto de erro.</p>
                                        </div>
                                    </div>
                                    @if ($delivery->ieducar_frequencia_response_json)
                                        <pre class="gae-json mono">{{ json_encode($delivery->ieducar_frequencia_response_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                    @else
                                        <p class="bridge-muted" style="margin:0;">—</p>
                                    @endif
                                </div>
                            </div>

                            @php $reprocLog = is_array($delivery->reprocessing_log) ? $delivery->reprocessing_log : []; @endphp
                            @if (count($reprocLog) > 0)
                            <div class="gae-card" style="margin-top: 14px;">
                                <div class="gae-card__head">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                                    <div>
                                        <h2 class="gae-card__title">Histórico de reprocessamentos ({{ count($reprocLog) }})</h2>
                                        <p class="gae-card__hint">Ações administrativas aplicadas a esta entrega.</p>
                                    </div>
                                </div>
                                <table class="gae-reproc-table mono">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Ação</th>
                                            <th>Quando</th>
                                            <th>Usuário</th>
                                            <th>Detalhes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($reprocLog as $idx => $entry)
                                            @php
                                                $actionLabel = match ($entry['action'] ?? '?') {
                                                    'reevaluate_presence' => 'Reavaliar presença',
                                                    'retry' => 'Reenviar ao iEducar',
                                                    'requeue' => 'Reenfileirar',
                                                    'force_process' => 'Forçar processamento',
                                                    'sms_resend_config' => 'SMS (configuração)',
                                                    'sms_resend_guardians' => 'SMS (responsáveis)',
                                                    default => $entry['action'] ?? '?',
                                                };
                                                $actionCls = match ($entry['action'] ?? '') {
                                                    'reevaluate_presence' => 'gae-reproc-action--reevaluate',
                                                    'retry' => 'gae-reproc-action--retry',
                                                    'requeue' => 'gae-reproc-action--requeue',
                                                    'force_process' => 'gae-reproc-action--force',
                                                    'sms_resend_config', 'sms_resend_guardians' => 'gae-reproc-action--requeue',
                                                    default => '',
                                                };
                                                $details = collect($entry)->except(['action', 'at', 'user'])->filter(fn($v) => $v !== null)->toArray();
                                            @endphp
                                            <tr>
                                                <td>{{ $idx + 1 }}</td>
                                                <td><span class="gae-reproc-action {{ $actionCls }}">{{ $actionLabel }}</span></td>
                                                <td>{{ isset($entry['at']) ? \Carbon\Carbon::parse($entry['at'])->timezone(config('app.timezone'))->format('d/m/Y H:i:s') : '—' }}</td>
                                                <td>{{ $entry['user'] ?? '—' }}</td>
                                                <td>
                                                    @if (count($details) > 0)
                                                        @foreach ($details as $k => $v)
                                                            <span class="bridge-muted">{{ $k }}:</span> {{ is_bool($v) ? ($v ? 'true' : 'false') : $v }}@if (! $loop->last), @endif
                                                        @endforeach
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif

                            @php
                                $smsTriggerLabels = [
                                    'automated' => 'Disparo automático (fila)',
                                    'admin_resend_config' => 'Reenvio manual — conforme integração',
                                    'admin_resend_guardians' => 'Reenvio manual — responsáveis',
                                    'registry_snapshot' => 'Registo atual (sem linhas em send_log)',
                                ];
                                $smsTemplateLabels = [
                                    'presence_catraca' => 'Presença na catraca',
                                    'presence_ieducar_sync' => 'Confirmação no iEducar',
                                    'presence_notification' => 'Presença (legado)',
                                ];
                            @endphp
                            <div class="gae-card" style="margin-top: 14px;">
                                <div class="gae-card__head">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                    <div>
                                        <h2 class="gae-card__title">SMS enviados neste evento</h2>
                                        <p class="gae-card__hint">Ligação por <span class="mono">event_id</span> igual a <span class="mono">{{ $delivery->event_id }}</span> na tabela <span class="mono">sms_deliveries</span>. Inclui envios pela <strong>fila</strong> (<span class="mono">SendPresenceSms</span>) e <strong>reenvios</strong> do painel nesta página. Uma única tabela: histórico em <span class="mono">context.send_log</span> (até 50 entradas por destino) e, quando não há histórico gravado, uma linha de «registo atual». Na coluna <strong>Resultado</strong>, o estado (<span class="mono">sent</span>, etc.) e os dois ícones API → entrega ficam na mesma linha e altura (~28px); o texto longo dos ícones está no <em>tooltip</em> e na <strong>legenda</strong>. A entrega SMS baseia-se na última resposta HTTP de criação ao provedor (ex. Twilio), não em webhooks de entrega ao telemóvel.</p>
                                    </div>
                                </div>

                                @if ($smsDeliveries->isEmpty())
                                    <p class="bridge-muted" style="margin: 0;">Nenhum SMS registado para este <span class="mono">event_id</span>.</p>
                                @else
                                    <p style="margin: 0 0 10px; font-size: 13px; font-weight: 700;">Histórico e estado (mais recente primeiro)</p>
                                    @if (count($smsSendTimeline) === 0)
                                        <p class="bridge-muted" style="margin: 0;">Sem linhas para mostrar.</p>
                                    @else
                                        <table class="gae-reproc-table" aria-label="SMS deste evento">
                                            <thead>
                                                <tr>
                                                    <th>Quando</th>
                                                    <th>Origem</th>
                                                    <th>Template / destino</th>
                                                    <th>Resultado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($smsSendTimeline as $log)
                                                    @php
                                                        $trg = (string) ($log['trigger'] ?? '');
                                                        $trgLabel = $smsTriggerLabels[$trg] ?? ($trg !== '' ? $trg : '—');
                                                        $ltk = (string) ($log['template_key'] ?? '');
                                                        $ltpl = $smsTemplateLabels[$ltk] ?? $ltk;
                                                        $stt = (string) ($log['status'] ?? '');
                                                        $ui = is_array($log['_ui'] ?? null) ? $log['_ui'] : ['api' => 'neutral', 'api_label' => '', 'delivery' => 'na', 'delivery_label' => '—'];
                                                        $iconApi = match ($ui['api'] ?? 'neutral') {
                                                            'success' => 'success',
                                                            'danger' => 'danger',
                                                            'warn' => 'warn',
                                                            default => 'neutral',
                                                        };
                                                        $iconDel = match ($ui['delivery'] ?? 'na') {
                                                            'success' => 'success',
                                                            'danger' => 'danger',
                                                            'warn' => 'warn',
                                                            'neutral' => 'neutral',
                                                            default => 'na',
                                                        };
                                                        $meta = is_array($log['_delivery_meta'] ?? null) ? $log['_delivery_meta'] : [];
                                                        $prov = (string) ($meta['provider'] ?? '');
                                                        $provIdLog = (string) ($log['provider_message_id'] ?? '');
                                                        $provIdMeta = (string) ($meta['provider_message_id'] ?? '');
                                                        $provId = $provIdLog !== '' ? $provIdLog : $provIdMeta;
                                                        $pv = (string) ($log['message_preview'] ?? '');
                                                        $fullMsg = (string) ($meta['message'] ?? '');
                                                        $lastErr = (string) ($meta['last_error'] ?? '');
                                                        $smsIeducarBoilerplate = static function (string $s): bool {
                                                            $t = ltrim($s);

                                                            return $t !== '' && str_starts_with($t, 'O iEducar confirmou a frequência') && str_contains($s, 'Aluno:');
                                                        };
                                                        $showPv = $pv !== '' && ! $smsIeducarBoilerplate($pv);
                                                        $showFullExtra = $fullMsg !== '' && $fullMsg !== $pv && ! $smsIeducarBoilerplate($fullMsg);
                                                    @endphp
                                                    <tr>
                                                        <td class="mono" style="font-size: 12px;">{{ $log['at_display'] ?? '—' }}</td>
                                                        <td>{{ $trgLabel }}</td>
                                                        <td><span class="mono">{{ $ltk }}</span> · <span class="mono">{{ $log['to_masked'] ?? '—' }}</span><br /><span style="font-size: 12px; color: var(--muted);">{{ $ltpl }}</span></td>
                                                        <td>
                                                            <div class="gae-sms-result-row">
                                                                @if ($stt === 'sent')
                                                                    <span class="gae-badge gae-badge--success mono">sent</span>
                                                                @elseif ($stt === 'error')
                                                                    <span class="gae-badge gae-badge--danger mono">error</span>
                                                                @elseif ($stt === 'pending')
                                                                    <span class="gae-badge gae-badge--warn mono">pending</span>
                                                                @else
                                                                    <span class="gae-badge gae-badge--neutral mono">{{ $stt !== '' ? $stt : '—' }}</span>
                                                                @endif
                                                                <div class="gae-sms-flow gae-sms-flow--inline" role="group" aria-label="Aceite na API e entrega SMS">
                                                                    <span class="gae-sms-flow__icon gae-sms-flow__icon--{{ $iconApi }}" title="{{ e($ui['api_label'] ?? '') }}" aria-label="{{ e($ui['api_label'] ?? 'Aceite na API') }}">
                                                                        @switch($iconApi)
                                                                            @case('success')
                                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                                                                @break
                                                                            @case('danger')
                                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                                                                @break
                                                                            @case('warn')
                                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                                                                @break
                                                                            @default
                                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                                                                                @break
                                                                        @endswitch
                                                                    </span>
                                                                    <span class="gae-sms-flow__sep" aria-hidden="true">→</span>
                                                                    <span class="gae-sms-flow__icon gae-sms-flow__icon--{{ $iconDel === 'na' ? 'na' : $iconDel }}" title="{{ e($ui['delivery_label'] ?? '') }}" aria-label="{{ e($ui['delivery_label'] ?? 'Entrega SMS') }}">
                                                                        @switch($iconDel)
                                                                            @case('success')
                                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="m9 9.5 2.5 2.5L15 8.5"/></svg>
                                                                                @break
                                                                            @case('danger')
                                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/></svg>
                                                                                @break
                                                                            @case('warn')
                                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                                                                @break
                                                                            @case('neutral')
                                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                                                                @break
                                                                            @default
                                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                                                                @break
                                                                        @endswitch
                                                                    </span>
                                                                </div>
                                                                @if (! empty($log['http_status']))
                                                                    <span class="gae-sms-result-row__http bridge-muted mono">HTTP {{ $log['http_status'] }}</span>
                                                                @endif
                                                            </div>
                                                            @if ($prov !== '' || $provId !== '')
                                                                <div class="mono" style="font-size: 11px; color: var(--muted); margin-top: 4px;">@if ($prov !== ''){{ $prov }}@endif @if ($provId !== '')· {{ \Illuminate\Support\Str::limit($provId, 42) }}@endif</div>
                                                            @endif
                                                            @if (! empty($log['error_snippet']))
                                                                <div style="font-size: 12px; color: var(--gae-bad); margin-top: 8px;">{{ $log['error_snippet'] }}</div>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @if ($showPv || $showFullExtra || $lastErr !== '')
                                                        <tr>
                                                            <td colspan="4" style="padding-top: 0;">
                                                                @if ($showPv)
                                                                    <div style="font-size: 12px; line-height: 1.45; color: var(--muted);">{{ \Illuminate\Support\Str::limit($pv, 360) }}</div>
                                                                @endif
                                                                @if ($showFullExtra)
                                                                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); margin-top: {{ $showPv ? '10px' : '0' }}; margin-bottom: 4px;">Texto completo no registo</div>
                                                                    <div style="font-size: 13px; line-height: 1.45;">{{ \Illuminate\Support\Str::limit($fullMsg, 400) }}</div>
                                                                @endif
                                                                @if ($lastErr !== '')
                                                                    <div style="margin-top: 10px; font-size: 12px; color: var(--gae-bad);"><strong>Último erro no registo:</strong> {{ \Illuminate\Support\Str::limit($lastErr, 280) }}</div>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                        <div class="gae-sms-legend">
                                            <p style="margin: 0 0 8px;"><strong>Legenda — API → entrega</strong> (na coluna «Resultado», o estado <span class="mono">sent</span>/<span class="mono">error</span>/… vem primeiro; a seguir os dois ícones na mesma altura; detalhe no <em>tooltip</em> de cada ícone).</p>
                                            <p style="margin: 0;"><strong>Aceite na API</strong> (antes da seta): <span class="gae-sms-flow__icon gae-sms-flow__icon--success" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span> verde — HTTP aceite; <span class="gae-sms-flow__icon gae-sms-flow__icon--danger" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></span> vermelho — falha; <span class="gae-sms-flow__icon gae-sms-flow__icon--warn" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg></span> âmbar — aviso/inconsistência; <span class="gae-sms-flow__icon gae-sms-flow__icon--neutral" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg></span> cinza — pendente ou intermédio.</p>
                                            <p style="margin: 10px 0 0;"><strong>Entrega SMS</strong> (depois da seta; estado na resposta de criação, não webhook): <span class="gae-sms-flow__icon gae-sms-flow__icon--success" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="m9 9.5 2.5 2.5L15 8.5"/></svg></span> entregue (provedor); <span class="gae-sms-flow__icon gae-sms-flow__icon--warn" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span> em trânsito ou sem confirmação; <span class="gae-sms-flow__icon gae-sms-flow__icon--danger" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/></svg></span> falha no provedor; <span class="gae-sms-flow__icon gae-sms-flow__icon--neutral" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></span> estado pouco claro; <span class="gae-sms-flow__icon gae-sms-flow__icon--na" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/></svg></span> não aplicável.</p>
                                        </div>
                                    @endif
                                @endif
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
