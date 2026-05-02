<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Frequência #{{ $delivery->id }} • {{ config('app.name', 'Bridge ERP') }}</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        <script>
            (function () {
                try {
                    const stored = localStorage.getItem('theme');
                    const theme = stored === 'light' || stored === 'dark' ? stored : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                    document.documentElement.classList.toggle('dark', theme === 'dark');
                    document.documentElement.dataset.theme = theme;
                } catch (_) {}
            })();
        </script>
        <link rel="stylesheet" href="/home.css">
        <script defer src="/home.js"></script>
        <style>
            .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12px; white-space: pre-wrap; word-break: break-word; }
            .box { margin-top: 12px; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--border); background: var(--surface-1); }
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
                                <div class="bridge-brand__tagline">Frequência • registro #{{ $delivery->id }}</div>
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
                            @if (session('status'))
                                <p class="bridge-muted" style="margin-top: 4px;"><strong>{{ session('status') }}</strong></p>
                            @endif

                            <div class="bridge-form__actions" style="margin-top: 8px;">
                                @if ($delivery->status === \App\Models\IeducarFrequenciaRegistroDelivery::STATUS_PENDING)
                                    <form method="POST" action="{{ route('integrations.ieducar.frequencia-registro.force-send', ['id' => $delivery->id]) }}" style="display: inline; margin: 0;">
                                        @csrf
                                        <button type="submit" class="bridge-btn bridge-btn--primary">Forçar envio agora</button>
                                    </form>
                                @endif
                                <a class="bridge-btn" href="{{ route('admin.ieducar-frequencia-deliveries.index') }}">Voltar à lista</a>
                                <a class="bridge-btn" href="{{ route('admin.ieducar-frequencia-deliveries.show', ['id' => $delivery->id]) }}">Admin</a>
                            </div>

                            <div class="box" style="margin-top: 14px;">
                                <div class="bridge-muted" style="font-size: 12px;">Modo</div>
                                <div style="font-weight: 700;">{{ $delivery->mode }}</div>
                                <div class="bridge-muted" style="font-size: 12px; margin-top: 10px;">Status</div>
                                <div style="font-weight: 700;">{{ $delivery->status }}</div>
                                <div class="bridge-muted" style="font-size: 12px; margin-top: 10px;">HTTP</div>
                                <div class="mono">{{ $delivery->http_status ?? '—' }}</div>
                                @if ($delivery->error_message)
                                    <div class="bridge-muted" style="font-size: 12px; margin-top: 10px;">Erro</div>
                                    <div class="mono" style="color: #ef4444;">{{ $delivery->error_message }}</div>
                                @endif
                                @if (in_array($delivery->mode, [\App\Models\IeducarFrequenciaRegistroDelivery::MODE_PREVIEW, \App\Models\IeducarFrequenciaRegistroDelivery::MODE_APPLY], true)
                                    && in_array($delivery->status, [\App\Models\IeducarFrequenciaRegistroDelivery::STATUS_PENDING, \App\Models\IeducarFrequenciaRegistroDelivery::STATUS_PROCESSING], true))
                                    <p class="bridge-muted" style="margin-top: 12px;">Aguardando worker da fila. Atualize a página após alguns segundos.</p>
                                @endif
                            </div>

                            <div class="box">
                                <div style="font-weight: 800; margin-bottom: 8px;">Resposta do i-Educar (JSON)</div>
                                @if (is_array($delivery->response_json))
                                    <pre class="mono">{{ json_encode($delivery->response_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                @else
                                    <span class="bridge-muted">(ainda sem resposta)</span>
                                @endif
                            </div>

                            <div class="box">
                                <div style="font-weight: 800; margin-bottom: 8px;">Payload enviado</div>
                                <pre class="mono">{{ json_encode($delivery->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
