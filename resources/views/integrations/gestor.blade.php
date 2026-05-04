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
        <style>
            .gestor-section {
                margin-top: 22px;
                padding-top: 18px;
                border-top: 1px solid var(--border);
            }
            .gestor-section--first {
                margin-top: 14px;
                padding-top: 0;
                border-top: none;
            }
            .gestor-section__title {
                font-weight: 750;
                font-size: 15px;
                margin: 0 0 8px;
                letter-spacing: 0.02em;
            }
            .gestor-section__lead {
                margin: 0 0 12px;
                color: var(--muted);
                font-size: 14px;
                line-height: 1.5;
            }
            .gestor-save-note {
                margin-top: 14px;
                padding: 10px 12px;
                border-radius: 12px;
                border: 1px solid var(--border);
                background: color-mix(in srgb, var(--bg0) 60%, transparent);
                font-size: 13px;
                color: var(--muted);
                line-height: 1.45;
            }
            .gestor-inbound-card {
                margin-top: 12px;
                padding: 14px;
                border-radius: 14px;
                border: 1px solid var(--border);
                background: color-mix(in srgb, var(--surface-2) 88%, transparent);
            }
            .gestor-inbound-card__k {
                font-size: 12px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                color: var(--muted);
            }
            .gestor-inbound-card__t {
                font-weight: 700;
                margin-top: 6px;
                font-size: 14px;
            }
            .gestor-inbound-card__p {
                margin-top: 8px;
                font-size: 13px;
                color: var(--muted);
                line-height: 1.45;
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
                                <div class="bridge-panel__meta">tudo nesta página grava na linha <span class="mono">integrations</span> (<span class="mono">key=gestor</span>)</div>
                            </div>

                            @if ($errors->any())
                                <div class="bridge-error" style="margin-top: 12px;">
                                    @foreach ($errors->all() as $err)
                                        <div>{{ $err }}</div>
                                    @endforeach
                                </div>
                            @endif

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

                            <form method="POST" action="{{ route('integrations.gestor.update') }}" class="bridge-form" id="gestor-main-form">
                                @csrf

                                <div class="gestor-section gestor-section--first">
                                    <div class="gestor-section__title">1. SDK — saída GIDE → Gestor</div>
                                    <p class="gestor-section__lead">
                                        Credenciais e URL usadas pelo GIDE para <strong>Signin</strong> e chamadas autenticadas ao SDK (matrícula, convite, etc.).
                                    </p>

                                    <label class="bridge-check">
                                        <input type="checkbox" name="enabled" value="1" {{ $integration->enabled ? 'checked' : '' }} />
                                        <span>Habilitar integração Gestor (outbound + validação de eventos recebidos)</span>
                                    </label>

                                    <div class="bridge-field">
                                        <label class="bridge-label" for="base_url">Base URL do SDK</label>
                                        <input class="bridge-input" id="base_url" name="base_url" type="text" value="{{ old('base_url', $integration->base_url ?? '') }}" placeholder="https://… (coluna integrations.base_url)" />
                                        @error('base_url')
                                            <div class="bridge-error">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="bridge-field">
                                        <label class="bridge-label" for="application_key">ApplicationKey</label>
                                        <input class="bridge-input" id="application_key" name="application_key" type="text" value="{{ old('application_key', data_get($integration->extra, 'application_key') ?? '') }}" placeholder="Header obrigatório do SDK" />
                                        @error('application_key')
                                            <div class="bridge-error">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="bridge-field">
                                        <label class="bridge-label" for="auth_username">Username (Signin)</label>
                                        <input class="bridge-input" id="auth_username" name="auth_username" type="text" value="{{ old('auth_username', data_get($integration->extra, 'auth.username') ?? '') }}" />
                                        @error('auth_username')
                                            <div class="bridge-error">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="bridge-field">
                                        <label class="bridge-label" for="auth_password">Password (Signin)</label>
                                        <input class="bridge-input" id="auth_password" name="auth_password" type="password" value="{{ old('auth_password', data_get($integration->extra, 'auth.password') ?? '') }}" placeholder="deixe em branco para manter a senha já salva" />
                                        @error('auth_password')
                                            <div class="bridge-error">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="bridge-field">
                                        <label class="bridge-label" for="signature_ttl_seconds">TTL da assinatura HMAC (segundos)</label>
                                        <input class="bridge-input" id="signature_ttl_seconds" name="signature_ttl_seconds" type="number" min="30" max="3600" value="{{ old('signature_ttl_seconds', $integration->signature_ttl_seconds) }}" />
                                        @error('signature_ttl_seconds')
                                            <div class="bridge-error">{{ $message }}</div>
                                        @enderror
                                        <div class="bridge-muted" style="margin-top: 6px;">Usado na validação de pedidos assinados que o Gestor envia ao GIDE.</div>
                                    </div>
                                </div>

                                <div class="gestor-section">
                                    <div class="gestor-section__title">2. Outbound — matrícula → convite no Gestor</div>
                                    <p class="gestor-section__lead">
                                        Quando o iEducar envia matrícula ao GIDE, o job usa estes valores para montar o JSON do <strong>Invite</strong> no Gestor.
                                    </p>

                                    <div class="bridge-field">
                                        <label class="bridge-label" for="outbound_enrollment_path">Path do POST (enrollment / convite)</label>
                                        <input class="bridge-input mono" id="outbound_enrollment_path" name="outbound_enrollment_path" type="text" value="{{ old('outbound_enrollment_path', data_get($integration->extra, 'endpoints.enrollment_sync_path') ?? '') }}" placeholder="/SDK/Invite" />
                                        @error('outbound_enrollment_path')
                                            <div class="bridge-error">{{ $message }}</div>
                                        @enderror
                                        <div class="bridge-muted" style="margin-top: 6px;">Gravado em <span class="mono">extra.endpoints.enrollment_sync_path</span>.</div>
                                    </div>

                                    <div class="bridge-field">
                                        <label class="bridge-label" for="unity_id">unityId</label>
                                        <input class="bridge-input mono" id="unity_id" name="unity_id" type="text" inputmode="numeric" pattern="[0-9]*" value="{{ \App\Support\GestorStoredIds::stringForNumericInput(old('unity_id', data_get($integration->extra, 'defaults.unity_id') ?? data_get($integration->extra, 'onboarding.unity_id'))) }}" placeholder="inteiro &gt; 0" />
                                        @error('unity_id')
                                            <div class="bridge-error">{{ $message }}</div>
                                        @enderror
                                        <div class="bridge-muted" style="margin-top: 6px;">
                                            Resolução no envio: primeiro <span class="mono">extra.onboarding.unity_id</span> &gt; 0, senão <span class="mono">extra.defaults.unity_id</span> &gt; 0. Vazio ou <strong>0</strong> ignora esse nível.
                                        </div>
                                    </div>

                                    <div class="bridge-field">
                                        <label class="bridge-label" for="access_profile_id">accessProfileId</label>
                                        <input class="bridge-input mono" id="access_profile_id" name="access_profile_id" type="text" inputmode="numeric" pattern="[0-9]*" value="{{ \App\Support\GestorStoredIds::stringForNumericInput(old('access_profile_id', data_get($integration->extra, 'defaults.access_profile_id') ?? data_get($integration->extra, 'onboarding.access_profile_id'))) }}" placeholder="&gt;0 ou vazio / 0 → null no JSON" />
                                        @error('access_profile_id')
                                            <div class="bridge-error">{{ $message }}</div>
                                        @enderror
                                        <div class="bridge-muted" style="margin-top: 6px;">
                                            Mesma ordem: <span class="mono">onboarding.access_profile_id</span>, depois <span class="mono">defaults.access_profile_id</span>. Só valores inteiro &gt; 0 viram número no convite; vazio ou <strong>0</strong> → <span class="mono">accessProfileId: null</span> no JSON enviado ao Gestor.
                                        </div>
                                    </div>
                                </div>

                                <div class="gestor-section">
                                    <div class="gestor-section__title">3. Presença — após <span class="mono">POST /api/v1/gestor/access-events</span></div>
                                    <p class="gestor-section__lead">
                                        O GIDE aplica janelas e mapeamento de payload usando a integração <strong>iEducar</strong> em <span class="mono">/integracoes/ieducar</span> (API do Diário: mesma <span class="mono">base_url</span> e <span class="mono">access_key</span> para preview e homologação). Abaixo só indica qual <strong>rótulo de ambiente</strong> fica registrado para auditoria e alinhamento com o iEducar.
                                    </p>
                                    <div class="bridge-field">
                                        <div class="bridge-label">Ambiente iEducar (registro)</div>
                                        <div style="margin-top: 10px; display: grid; gap: 10px;">
                                            <label class="bridge-check">
                                                <input type="radio" name="ieducar_processing_environment" value="preview" {{ old('ieducar_processing_environment', data_get($integration->extra, 'ieducar_processing.environment', 'homolog')) === 'preview' ? 'checked' : '' }} />
                                                <span>Preview</span>
                                            </label>
                                            <label class="bridge-check">
                                                <input type="radio" name="ieducar_processing_environment" value="homolog" {{ old('ieducar_processing_environment', data_get($integration->extra, 'ieducar_processing.environment', 'homolog')) === 'homolog' ? 'checked' : '' }} />
                                                <span>Homologação</span>
                                            </label>
                                        </div>
                                        @error('ieducar_processing_environment')
                                            <div class="bridge-error">{{ $message }}</div>
                                        @enderror
                                        <div class="bridge-muted" style="margin-top: 8px;">Gravado em <span class="mono">extra.ieducar_processing.environment</span>.</div>
                                    </div>
                                </div>

                                <div class="gestor-save-note">
                                    <strong>Salvar</strong> grava de uma vez: URL e credenciais do SDK, path de convite, <span class="mono">unityId</span>/<span class="mono">accessProfileId</span> em <span class="mono">defaults</span>, TTL HMAC, opção de ambiente acima e estado habilitado. A coluna <span class="mono">auth_token</span> (Bearer do Signin) é <strong>limpa</strong> ao salvar para forçar novo Signin na próxima chamada ao Gestor.
                                </div>

                                <div class="bridge-form__actions" style="margin-top: 16px;">
                                    <button type="submit" class="bridge-btn bridge-btn--primary">Salvar configuração completa</button>
                                    <a class="bridge-btn" href="{{ url('/dashboard') }}">Voltar</a>
                                </div>
                            </form>

                            <div class="gestor-section">
                                <div class="gestor-section__title">4. Bearer Signin (GIDE → Gestor)</div>
                                <p class="gestor-section__lead">
                                    Após Signin bem-sucedido, o token fica em <span class="mono">integrations.auth_token</span> (criptografado). Use “Testar auth” depois de salvar credenciais novas.
                                </p>
                                <div class="bridge-field">
                                    <label class="bridge-label">Estado do token</label>
                                    <input class="bridge-input" type="text" readonly value="{{ $integration->auth_token ? '•••••••••••••••• (há token salvo)' : '(não configurado — rode Signin)' }}" />
                                </div>
                                <form method="POST" action="{{ route('integrations.gestor.test-auth') }}" class="bridge-form" style="margin-top: 8px;">
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
                            </div>

                            <div class="gestor-section">
                                <div class="gestor-section__title">5. Gestor → GIDE — eventos de acesso</div>
                                <p class="gestor-section__lead">
                                    Dois canais: <strong>HMAC</strong> em <span class="mono">POST /api/v1/gestor/access-events</span> (ver <span class="mono">README.md</span> e <span class="mono">VerifyHmacSignature</span>) ou <strong>token de acesso</strong> em <span class="mono">POST /api/v1/catraca/access-events</span> com <span class="mono">Authorization: Bearer</span> — documentação do contrato: <code>docs/CATRACA_WEBHOOK.md</code>. Auditoria: <span class="mono">/admin/gestor-access-events</span>.
                                </p>

                                <div class="gestor-inbound-card">
                                    <div class="gestor-inbound-card__k">Gestor (HMAC)</div>
                                    <div class="gestor-inbound-card__t"><span class="mono">POST /api/v1/gestor/access-events</span></div>
                                    <p class="gestor-inbound-card__p">
                                        Gere ou rotacione o segredo abaixo e configure o remetente para assinar o corpo bruto exatamente como enviado, com os cabeçalhos exigidos pelo middleware.
                                    </p>
                                    <div class="bridge-field">
                                        <label class="bridge-label">Segredo HMAC</label>
                                        <input class="bridge-input" type="text" readonly value="{{ $integration->hmac_secret ? '•••••••••••••••• (configurado)' : '(não configurado)' }}" />
                                    </div>
                                    <form method="POST" action="{{ route('integrations.gestor.rotate-hmac') }}" class="bridge-form" style="margin-top: 8px;">
                                        @csrf
                                        <div class="bridge-form__actions">
                                            <button type="submit" class="bridge-btn">Gerar ou rotacionar segredo HMAC</button>
                                        </div>
                                    </form>
                                </div>

                                <div class="gestor-inbound-card">
                                    <div class="gestor-inbound-card__k">Catraca (token)</div>
                                    <div class="gestor-inbound-card__t"><span class="mono">POST {{ $catracaWebhookUrl ?? url('/api/v1/catraca/access-events') }}</span></div>
                                    <p class="gestor-inbound-card__p">
                                        Autenticação: somente <span class="mono">Authorization: Bearer &lt;token&gt;</span>. O GIDE guarda <strong>só o hash</strong> em <span class="mono">extra.catraca_access_token_hash</span>; o valor em claro aparece <strong>uma vez</strong> após gerar. O JSON recebido e o processamento ficam em <span class="mono">gestor_access_event_deliveries</span> (TI: <span class="mono">/admin/gestor-access-events</span>).
                                    </p>
                                    <div style="margin-top: 8px;">
                                        <span class="bridge-chip" style="{{ ! empty($catracaWebhookBearerConfigured) ? 'border-color: color-mix(in srgb, var(--accent-c) 40%, var(--border));' : '' }}">
                                            {{ ! empty($catracaWebhookBearerConfigured) ? 'Token de acesso da catraca: configurado' : 'Token de acesso da catraca: não configurado' }}
                                        </span>
                                    </div>

                                    @if (session('gestor_catraca_webhook_bearer_plaintext'))
                                        <div style="margin-top: 14px; padding: 12px 14px; border-radius: 14px; border: 2px solid color-mix(in srgb, var(--accent-c) 45%, var(--border)); background: color-mix(in srgb, var(--accent-c) 10%, var(--surface-1));">
                                            <div style="font-weight: 800; margin-bottom: 8px;">Token de acesso — copie agora (não será exibido de novo)</div>
                                            <label class="bridge-label" for="catraca_wh_once">Valor do token</label>
                                            <input class="bridge-input mono" id="catraca_wh_once" type="text" readonly value="{{ session('gestor_catraca_webhook_bearer_plaintext') }}" onclick="this.select()" style="font-size: 13px;" />
                                            <div class="bridge-muted" style="margin-top: 8px; font-size: 12px;">Some após recarregar a página. Guarde na catraca ou em cofre.</div>
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route('integrations.gestor.generate-catraca-webhook-bearer') }}" class="bridge-form" style="margin-top: 12px;" onsubmit="return confirm('Gerar um novo token invalida o anterior na catraca. Continuar?');">
                                        @csrf
                                        <div class="bridge-form__actions">
                                            <button type="submit" class="bridge-btn">{{ ! empty($catracaWebhookBearerConfigured) ? 'Gerar novo token (invalida o atual)' : 'Gerar token de acesso da catraca' }}</button>
                                        </div>
                                    </form>
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
