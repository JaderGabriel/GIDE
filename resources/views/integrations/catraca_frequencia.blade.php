<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Integrações • Catraca/Frequência • {{ config('app.name', 'Bridge ERP') }}</title>

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
                        <a class="bridge-brand" href="{{ url('/dashboard') }}">
                            <img src="/favicon.svg" alt="" class="bridge-brand__logo" />
                            <div class="bridge-brand__text">
                                <div class="bridge-brand__name">{{ config('app.name', 'Bridge ERP') }}</div>
                                <div class="bridge-brand__tagline">Configuração • Catraca/Frequência</div>
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
                                <div class="bridge-panel__title">Integração Catraca/Frequência (iEducar → GIDE)</div>
                                <div class="bridge-panel__meta">endpoints fixos • token bearer</div>
                            </div>

                            <x-audit-toolbar style="margin-top: 12px;" />

                            @if (session('status'))
                                <p class="bridge-muted" style="margin-top: 12px;">
                                    <strong>{{ session('status') }}</strong>
                                </p>
                            @endif

                            <p class="bridge-muted" style="margin-top: 12px;">
                                Esta integração expõe endpoints <strong>fixos</strong> no GIDE (não há “base_url” para configurar aqui).
                                Configure apenas o token Bearer que o iEducar usará ao chamar o GIDE.
                            </p>

                            <div class="bridge-field" style="margin-top: 12px;">
                                <label class="bridge-label">Endpoints (iEducar → GIDE)</label>
                                <div class="bridge-muted" style="margin-top: 6px;">
                                    - <code>POST {{ url('/api/v1/catraca-frequencia/gide/facial/nova') }}</code>
                                    <br />- <code>POST {{ url('/api/v1/catraca-frequencia/gide/facial/excluir') }}</code>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('integrations.catraca-frequencia.update') }}" class="bridge-form" style="margin-top: 14px;">
                                @csrf

                                <label class="bridge-check">
                                    <input type="checkbox" name="enabled" value="1" {{ $integration->enabled ? 'checked' : '' }} />
                                    <span>Habilitar recebimento de requests do iEducar</span>
                                </label>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="inbound_token">Token (Bearer) do iEducar → GIDE</label>
                                    <input
                                        class="bridge-input"
                                        id="inbound_token"
                                        name="inbound_token"
                                        type="password"
                                        value="{{ old('inbound_token', '') }}"
                                        placeholder="{{ $integration->auth_token ? '•••••••••• (já configurado)' : 'cole aqui o token' }}"
                                    />
                                    @error('inbound_token')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                    <div class="bridge-muted" style="margin-top: 6px;">
                                        Se deixar em branco, o token atual não é alterado.
                                    </div>
                                </div>

                                <div class="bridge-form__actions">
                                    <button type="submit" class="bridge-btn bridge-btn--primary">Salvar</button>
                                    <a class="bridge-btn" href="/dashboard">Voltar</a>
                                </div>
                            </form>
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

