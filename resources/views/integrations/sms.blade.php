<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Integrações • SMS • {{ config('app.name', 'Bridge ERP') }}</title>

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
                        <a class="bridge-brand" href="/dashboard">
                            <img src="/favicon.svg" alt="" class="bridge-brand__logo" />
                            <div class="bridge-brand__text">
                                <div class="bridge-brand__name">{{ config('app.name', 'Bridge ERP') }}</div>
                                <div class="bridge-brand__tagline">Configuração • SMS</div>
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
                                <div class="bridge-panel__title">Integração SMS</div>
                                <div class="bridge-panel__meta">token • envio • template</div>
                            </div>

                            @if (session('status'))
                                <p class="bridge-muted" style="margin-top: 12px;">
                                    <strong>{{ session('status') }}</strong>
                                </p>
                            @endif

                            <form method="POST" action="{{ route('integrations.sms.update') }}" class="bridge-form">
                                @csrf

                                <label class="bridge-check">
                                    <input type="checkbox" name="enabled" value="1" {{ $integration->enabled ? 'checked' : '' }} />
                                    <span>Habilitar envio de SMS após apontamento de presença</span>
                                </label>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="base_url">Base URL</label>
                                    <input class="bridge-input" id="base_url" name="base_url" type="text" value="{{ old('base_url', $integration->base_url ?? config('integrations.sms.default_base_url')) }}" placeholder="{{ filled(config('integrations.sms.default_base_url')) ? (string) config('integrations.sms.default_base_url') : 'URL base HTTPS da API SMS (ex.: SMS_DEFAULT_BASE_URL no .env)' }}" />
                                    @error('base_url')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="api_token">Token da API SMS — header <code>X-API-TOKEN</code></label>
                                    <input class="bridge-input" id="api_token" name="api_token" type="password" value="{{ old('api_token', '') }}" placeholder="{{ $integration->auth_token ? '•••••••••• (já configurado)' : 'cole aqui o token' }}" />
                                    @error('api_token')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                    <div class="bridge-muted" style="margin-top: 6px;">
                                        Se você deixar em branco, o token atual não é alterado.
                                    </div>
                                </div>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="from">From (conta/identificador do SMS)</label>
                                    <input class="bridge-input" id="from" name="from" type="text" value="{{ old('from', data_get($integration->extra, 'from') ?? '') }}" placeholder="sms-account" />
                                    @error('from')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="payload_phone_key">Chave no payload do Gestor que contém o telefone</label>
                                    <input class="bridge-input" id="payload_phone_key" name="payload_phone_key" type="text" value="{{ old('payload_phone_key', data_get($integration->extra, 'payload_map.phone') ?? 'phone') }}" placeholder="responsavel.phone" />
                                    @error('payload_phone_key')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                    <div class="bridge-muted" style="margin-top: 6px;">
                                        Ex.: <code>phone</code>, <code>responsavel.phone</code>, etc.
                                    </div>
                                </div>

                                <hr style="margin: 18px 0; border: none; border-top: 1px solid var(--border);" />

                                <div class="bridge-panel__head" style="margin-top: 8px;">
                                    <div class="bridge-panel__title">Template de mensagem</div>
                                    <div class="bridge-panel__meta">tags (placeholders)</div>
                                </div>

                                <label class="bridge-check" style="margin-top: 10px;">
                                    <input type="checkbox" name="template_enabled" value="1" {{ $template->enabled ? 'checked' : '' }} />
                                    <span>Template ativo</span>
                                </label>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="template_body">Mensagem (use tags)</label>
                                    <textarea class="bridge-input" id="template_body" name="template_body" rows="6" style="resize: vertical;">{{ old('template_body', $template->body) }}</textarea>
                                    @error('template_body')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                    <div class="bridge-muted" style="margin-top: 10px;">
                                        Tags disponíveis:
                                        <br />- <code>{{'{{aluno_id}}'}}</code>
                                        <br />- <code>{{'{{matricula_id}}'}}</code>
                                        <br />- <code>{{'{{date}}'}}</code> (dd/mm/aaaa)
                                        <br />- <code>{{'{{time}}'}}</code> (hh:mm)
                                        <br />- <code>{{'{{window}}'}}</code>
                                        <br />- <code>{{'{{event_type}}'}}</code>
                                        <br />- <code>{{'{{event_id}}'}}</code>
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

