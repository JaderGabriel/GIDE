<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>SMS • Detalhe #{{ $delivery->id }} • {{ config('app.name', 'Bridge ERP') }}</title>

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
    </head>
    <body>
        <div class="bridge-shell">
            <header class="bridge-header">
                <div class="bridge-container">
                    <div class="bridge-header__inner">
                        <a class="bridge-brand" href="{{ route('sms.index') }}">
                            <img src="/favicon.svg" alt="" class="bridge-brand__logo" />
                            <div class="bridge-brand__text">
                                <div class="bridge-brand__name">{{ config('app.name', 'Bridge ERP') }}</div>
                                <div class="bridge-brand__tagline">SMS • Detalhe</div>
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
                                <div class="bridge-panel__title">Mensagem #{{ $delivery->id }}</div>
                                <div class="bridge-panel__meta">{{ $delivery->status }} • {{ $delivery->provider }}</div>
                            </div>

                            <div style="display:grid; gap: 10px; margin-top: 12px;">
                                <div class="bridge-muted"><strong>Telefone:</strong> {{ $delivery->to }}</div>
                                <div class="bridge-muted"><strong>From:</strong> {{ $delivery->from ?? '-' }}</div>
                                <div class="bridge-muted"><strong>Aluno:</strong> {{ $delivery->aluno_id ?? '-' }}</div>
                                <div class="bridge-muted"><strong>Matrícula:</strong> {{ $delivery->matricula_id ?? '-' }}</div>
                                <div class="bridge-muted"><strong>Janela:</strong> {{ $delivery->window ?? '-' }}</div>
                                <div class="bridge-muted"><strong>Tipo evento:</strong> {{ $delivery->event_type ?? '-' }}</div>
                                <div class="bridge-muted"><strong>Event ID:</strong> {{ $delivery->event_id }}</div>
                                <div class="bridge-muted"><strong>Provider message id:</strong> {{ $delivery->provider_message_id ?? '-' }}</div>
                                <div class="bridge-muted"><strong>Tentativas:</strong> {{ $delivery->attempts }}</div>
                                <div class="bridge-muted"><strong>HTTP:</strong> {{ $delivery->last_http_status ?? '-' }}</div>
                                <div class="bridge-muted"><strong>Criado:</strong> {{ $delivery->created_at ? \App\Support\DateDisplay::formatHuman($delivery->created_at, true) : '—' }}</div>
                                <div class="bridge-muted"><strong>Enviado:</strong> {{ $delivery->sent_at ? \App\Support\DateDisplay::formatHuman($delivery->sent_at, true) : '-' }}</div>
                            </div>

                            @if ($delivery->last_error)
                                <div class="bridge-field" style="margin-top: 14px;">
                                    <label class="bridge-label">Erro</label>
                                    <textarea class="bridge-input" rows="4" readonly style="resize: vertical;">{{ $delivery->last_error }}</textarea>
                                </div>
                            @endif

                            <div class="bridge-field" style="margin-top: 14px;">
                                <label class="bridge-label">Mensagem</label>
                                <textarea class="bridge-input" rows="5" readonly style="resize: vertical;">{{ $delivery->message }}</textarea>
                            </div>

                            <div class="bridge-field" style="margin-top: 14px;">
                                <label class="bridge-label">Contexto (tags)</label>
                                <textarea class="bridge-input" rows="6" readonly style="resize: vertical;">{{ json_encode($delivery->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</textarea>
                            </div>

                            <div class="bridge-field" style="margin-top: 14px;">
                                <label class="bridge-label">Resposta do provedor</label>
                                <textarea class="bridge-input" rows="8" readonly style="resize: vertical;">{{ json_encode($delivery->provider_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</textarea>
                            </div>

                            <div class="bridge-form__actions" style="margin-top: 12px;">
                                <a class="bridge-btn bridge-btn--primary" href="{{ route('sms.index') }}">Voltar</a>
                                <a class="bridge-btn" href="{{ route('integrations.sms') }}">Configurar SMS</a>
                                <a class="bridge-btn" href="/dashboard">Dashboard</a>
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

