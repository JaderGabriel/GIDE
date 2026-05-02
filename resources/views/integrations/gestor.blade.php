<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Integrações • Gestor • {{ config('app.name', 'Bridge ERP') }}</title>

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
                                <div class="bridge-brand__tagline">Configuração • Gestor</div>
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
                                <div class="bridge-panel__title">Integração Gestor (Porter/Kiper SDK)</div>
                                <div class="bridge-panel__meta">credenciais • token bearer • inbound/outbound</div>
                            </div>

                            @if (session('status'))
                                @php
                                    $level = session('status_level') ?: 'info';
                                    $border = 'color-mix(in srgb, var(--border) 80%, transparent)';
                                    $bg = 'color-mix(in srgb, var(--bg0) 55%, transparent)';
                                    $color = 'var(--text)';
                                    if ($level === 'success') {
                                        $border = 'color-mix(in srgb, var(--accent-c) 35%, var(--border))';
                                        $bg = 'color-mix(in srgb, var(--accent-c) 12%, transparent)';
                                    } elseif ($level === 'error') {
                                        $border = 'color-mix(in srgb, #ef4444 35%, var(--border))';
                                        $bg = 'color-mix(in srgb, #ef4444 10%, transparent)';
                                        $color = '#ef4444';
                                    }
                                @endphp
                                <div style="margin-top: 12px; padding: 10px 12px; border-radius: 14px; border: 1px solid {{ $border }}; background: {{ $bg }}; color: {{ $color }};">
                                    <strong>{{ session('status') }}</strong>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('integrations.gestor.update') }}" class="bridge-form">
                                @csrf

                                <label class="bridge-check">
                                    <input type="checkbox" name="enabled" value="1" {{ $integration->enabled ? 'checked' : '' }} />
                                    <span>Habilitar integração Gestor (validação HMAC inbound + envio outbound)</span>
                                </label>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="base_url">Base URL do SDK</label>
                                    <input class="bridge-input" id="base_url" name="base_url" type="text" value="{{ old('base_url', $integration->base_url ?? '') }}" placeholder="{{ filled(config('integrations.gestor.default_base_url')) ? (string) config('integrations.gestor.default_base_url') : 'URL base HTTPS do SDK (fornecida pelo seu ambiente)' }}" />
                                    @error('base_url')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="application_key">ApplicationKey (header obrigatório do SDK)</label>
                                    <input class="bridge-input" id="application_key" name="application_key" type="text" value="{{ old('application_key', data_get($integration->extra, 'application_key') ?? '') }}" placeholder="ApplicationKey" />
                                    @error('application_key')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="auth_username">Username (Signin)</label>
                                    <input class="bridge-input" id="auth_username" name="auth_username" type="text" value="{{ old('auth_username', data_get($integration->extra, 'auth.username') ?? '') }}" placeholder="usuário do gestor" />
                                    @error('auth_username')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="auth_password">Password (Signin)</label>
                                    <input class="bridge-input" id="auth_password" name="auth_password" type="password" value="{{ old('auth_password', data_get($integration->extra, 'auth.password') ?? '') }}" placeholder="senha do gestor" />
                                    @error('auth_password')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="signature_ttl_seconds">TTL da assinatura (segundos)</label>
                                    <input class="bridge-input" id="signature_ttl_seconds" name="signature_ttl_seconds" type="number" min="30" max="3600" value="{{ old('signature_ttl_seconds', $integration->signature_ttl_seconds) }}" />
                                    @error('signature_ttl_seconds')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="outbound_enrollment_path">Endpoint outbound (sync matrícula/aluno → Gestor)</label>
                                    <input class="bridge-input" id="outbound_enrollment_path" name="outbound_enrollment_path" type="text" value="{{ old('outbound_enrollment_path', data_get($integration->extra, 'endpoints.enrollment_sync_path') ?? '') }}" placeholder="/SDK/Invite (exemplo)" />
                                    @error('outbound_enrollment_path')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                    <div class="bridge-muted" style="margin-top: 6px;">
                                        Este path define para onde o GIDE envia o payload recebido do iEducar (matrícula) para o sistema de controle de acesso.
                                    </div>
                                </div>

                                <div class="bridge-form__actions">
                                    <button type="submit" class="bridge-btn bridge-btn--primary">Salvar</button>
                                    <a class="bridge-btn" href="/dashboard">Voltar</a>
                                </div>
                            </form>

                            <hr style="margin: 18px 0; border: none; border-top: 1px solid var(--border);" />

                            <div class="bridge-panel__head" style="margin-top: 8px;">
                                <div class="bridge-panel__title">Token bearer (GIDE → Gestor)</div>
                                <div class="bridge-panel__meta">Signin</div>
                            </div>

                            @if (session('status'))
                                @php
                                    $level = session('status_level') ?: 'info';
                                    $border = 'color-mix(in srgb, var(--border) 80%, transparent)';
                                    $bg = 'color-mix(in srgb, var(--bg0) 55%, transparent)';
                                    $color = 'var(--text)';
                                    if ($level === 'success') {
                                        $border = 'color-mix(in srgb, var(--accent-c) 35%, var(--border))';
                                        $bg = 'color-mix(in srgb, var(--accent-c) 12%, transparent)';
                                    } elseif ($level === 'error') {
                                        $border = 'color-mix(in srgb, #ef4444 35%, var(--border))';
                                        $bg = 'color-mix(in srgb, #ef4444 10%, transparent)';
                                        $color = '#ef4444';
                                    }
                                @endphp
                                <div style="margin-top: 12px; padding: 10px 12px; border-radius: 14px; border: 1px solid {{ $border }}; background: {{ $bg }}; color: {{ $color }};">
                                    <strong>{{ session('status') }}</strong>
                                </div>
                            @endif

                            <p class="bridge-muted" style="margin-top: 12px;">
                                O GIDE autentica no SDK via <strong>POST /Auth/Signin</strong> e armazena o token bearer em <code>integrations.auth_token</code>.
                            </p>

                            <div class="bridge-field">
                                <label class="bridge-label">Token atual</label>
                                <input class="bridge-input" type="text" readonly value="{{ $integration->auth_token ? '•••••••••••••••••••••••••••••••• (configurado)' : '(não configurado)' }}" />
                            </div>

                            <form method="POST" action="{{ route('integrations.gestor.test-auth') }}" class="bridge-form">
                                @csrf
                                <div class="bridge-form__actions">
                                    <button type="submit" class="bridge-btn">Testar auth (Signin)</button>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('integrations.gestor.test-unities') }}" class="bridge-form" style="margin-top: 8px;">
                                @csrf
                                <div class="bridge-form__actions">
                                    <button type="submit" class="bridge-btn">Testar listagem de Unities</button>
                                </div>
                            </form>

                            <hr style="margin: 18px 0; border: none; border-top: 1px solid var(--border);" />

                            <div class="bridge-panel__head" style="margin-top: 8px;">
                                <div class="bridge-panel__title">Token de envio (Gestor → GIDE)</div>
                                <div class="bridge-panel__meta">HMAC inbound</div>
                            </div>

                            <p class="bridge-muted" style="margin-top: 12px;">
                                O Gestor deve chamar o GIDE em <strong>/api/v1/gestor/access-events</strong> enviando JSON e assinando o corpo com HMAC.
                            </p>

                            <div class="bridge-field">
                                <label class="bridge-label">Segredo HMAC atual</label>
                                <input class="bridge-input" type="text" readonly value="{{ $integration->hmac_secret ? '•••••••••••••••••••••••••••••••• (configurado)' : '(não configurado)' }}" />
                            </div>

                            <form method="POST" action="{{ route('integrations.gestor.rotate-hmac') }}" class="bridge-form">
                                @csrf
                                <div class="bridge-form__actions">
                                    <button type="submit" class="bridge-btn">Gerar/rotacionar segredo HMAC</button>
                                </div>
                            </form>

                            <hr style="margin: 18px 0; border: none; border-top: 1px solid var(--border);" />

                            <div class="bridge-panel__head" style="margin-top: 8px;">
                                <div class="bridge-panel__title">Webhook JSON da catraca (Bearer)</div>
                                <div class="bridge-panel__meta">alternativa ao HMAC</div>
                            </div>

                            <p class="bridge-muted" style="margin-top: 12px;">
                                Endpoint <strong class="mono">POST {{ $catracaWebhookUrl ?? url('/api/v1/catraca/access-events') }}</strong> com cabeçalho
                                <span class="mono">Authorization: Bearer &lt;token&gt;</span> e corpo JSON (ver <code>docs/CATRACA_WEBHOOK.md</code>).
                                O token é guardado apenas como <strong>hash</strong>; depois de gerado, a interface <strong>não mostra</strong> o valor salvo — só é possível ver o texto na hora da geração ou gerar outro (o anterior deixa de valer).
                            </p>

                            <div style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                                <span class="pill {{ ! empty($catracaWebhookBearerConfigured) ? 'pill--ok' : '' }}">
                                    {{ ! empty($catracaWebhookBearerConfigured) ? 'Token webhook configurado' : 'Token webhook não configurado' }}
                                </span>
                            </div>

                            @if (session('gestor_catraca_webhook_bearer_plaintext'))
                                <div style="margin-top: 14px; padding: 12px 14px; border-radius: 14px; border: 2px solid color-mix(in srgb, var(--accent-c) 45%, var(--border)); background: color-mix(in srgb, var(--accent-c) 10%, var(--surface-1));">
                                    <div style="font-weight: 800; margin-bottom: 8px;">Copie o token agora</div>
                                    <label class="bridge-label" for="catraca_wh_once">Bearer (uso único na tela)</label>
                                    <input class="bridge-input mono" id="catraca_wh_once" type="text" readonly value="{{ session('gestor_catraca_webhook_bearer_plaintext') }}" onclick="this.select()" style="font-size: 13px;" />
                                    <div class="bridge-muted" style="margin-top: 8px; font-size: 12px;">Este campo some no próximo carregamento. Guarde em cofre ou na configuração da catraca.</div>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('integrations.gestor.generate-catraca-webhook-bearer') }}" class="bridge-form" style="margin-top: 12px;" onsubmit="return confirm('Gerar novo token invalida o anterior. Continuar?');">
                                @csrf
                                <div class="bridge-form__actions">
                                    <button type="submit" class="bridge-btn">{{ ! empty($catracaWebhookBearerConfigured) ? 'Gerar novo token (invalida o atual)' : 'Gerar token do webhook' }}</button>
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

