<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin • Access-event #{{ $delivery->id }} • {{ config('app.name', 'Bridge ERP') }}</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        <link rel="stylesheet" href="/home.css">
        <style>
            .bridge-container { max-width: 1100px; }
            .mono { font-family: ui-monospace, monospace; font-size: 12px; white-space: pre-wrap; word-break: break-word; }
            .g-card { margin-top: 14px; padding: 16px; border-radius: 16px; border: 1px solid var(--border); background: var(--card-strong); }
            .g-card h2 { margin: 0 0 10px; font-size: 1rem; }
            .g-banner { padding: 12px 14px; border-radius: 14px; border: 1px solid var(--border); background: color-mix(in srgb, #d97706 12%, var(--surface-1)); margin-top: 12px; font-size: 14px; line-height: 1.5; }
            .g-banner--preview { background: color-mix(in srgb, #0284c7 10%, var(--surface-1)); }
            .g-pre { margin-top: 8px; padding: 12px; border-radius: 12px; border: 1px solid var(--border); background: color-mix(in srgb, var(--bg0) 75%, transparent); max-height: min(55vh, 520px); overflow: auto; }
            .g-actions { margin-top: 16px; display: flex; gap: 10px; flex-wrap: wrap; }
            .bridge-btn { text-decoration: none; }
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
                                <div class="bridge-brand__tagline">Admin • Detalhe access-event</div>
                            </div>
                        </a>
                        @include('partials.bridge-user-menu')
                    </div>
                </div>
            </header>
            <main class="bridge-main">
                <div class="bridge-container" style="padding: 20px 16px 48px;">
                    <div class="g-actions">
                        <a class="bridge-btn" href="{{ route('admin.gestor-access-events.index') }}">← Lista</a>
                        @if ($delivery->accessEvent)
                            <span class="bridge-muted mono">access_events.id = {{ $delivery->accessEvent->id }}</span>
                        @endif
                    </div>

                    @if ($delivery->gestor_ie_environment === 'preview')
                        <div class="g-banner g-banner--preview">
                            <strong>Rótulo Gestor:</strong> <span class="mono">preview</span> (integração Gestor).<br />
                            <strong>Envio iEducar:</strong> sempre <span class="mono">meta.preview=true</span> no POST <span class="mono">/api/catraca-frequencia/gide/frequencia/registro</span> — simulação no i-Educar.
                        </div>
                    @else
                        <div class="g-banner">
                            <strong>Rótulo Gestor:</strong> <span class="mono">homolog</span> (ou equivalente na integração).<br />
                            <strong>Atenção:</strong> apesar do rótulo de homologação no Gestor, <strong>este fluxo não grava</strong> frequência no iEducar: usa apenas modo <span class="mono">preview</span> no contrato catraca-frequência.
                        </div>
                    @endif

                    <div class="g-card">
                        <h2>Resumo</h2>
                        <div class="mono">
                            event_id: {{ $delivery->event_id }}
                            · canal: {{ $delivery->inbound_channel ?? 'gestor_hmac' }}
                            · status: {{ $delivery->processing_status }}
                            · access_event criado neste POST: {{ $delivery->access_event_was_created ? 'sim' : 'não' }}
                            · HTTP iEducar: {{ $delivery->ieducar_frequencia_http_status ?? '—' }}
                        </div>
                    </div>

                    <div class="g-card">
                        <h2>JSON recebido (payload bruto)</h2>
                        <pre class="mono g-pre">{{ json_encode($delivery->inbound_payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>

                    <div class="g-card">
                        <h2>Análise (motor de presença + metadados)</h2>
                        <pre class="mono g-pre">{{ json_encode($delivery->analysis_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>

                    <div class="g-card">
                        <h2>JSON enviado ao iEducar (catraca-frequência, preview)</h2>
                        @if ($delivery->ieducar_frequencia_request_json)
                            <pre class="mono g-pre">{{ json_encode($delivery->ieducar_frequencia_request_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        @else
                            <p class="bridge-muted" style="margin:0;">(não aplicável — integração iEducar inativa, validação falhou ou motor não marcou envio.)</p>
                        @endif
                    </div>

                    <div class="g-card">
                        <h2>Resposta do iEducar</h2>
                        @if ($delivery->ieducar_frequencia_error)
                            <p style="margin:0 0 8px;color:#b91c1c;font-weight:600;">{{ $delivery->ieducar_frequencia_error }}</p>
                        @endif
                        @if ($delivery->ieducar_frequencia_response_json)
                            <pre class="mono g-pre">{{ json_encode($delivery->ieducar_frequencia_response_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        @else
                            <p class="bridge-muted" style="margin:0;">—</p>
                        @endif
                    </div>

                    <div class="g-card">
                        <h2>Resumo marker (iEducar)</h2>
                        <pre class="mono g-pre">{{ json_encode($delivery->ieducar_marker_summary ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
