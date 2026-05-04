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
                        <a class="bridge-brand" href="{{ url('/dashboard') }}">
                            <img src="/favicon.svg" alt="" class="bridge-brand__logo" />
                            <div class="bridge-brand__text">
                                <div class="bridge-brand__name">{{ config('app.name', 'Bridge ERP') }}</div>
                                <div class="bridge-brand__tagline">Configuração • SMS</div>
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
                                        Ex.: <code>phone</code>, <code>responsavel.phone</code>, etc. Usada quando o destino for <strong>alunos</strong>.
                                    </div>
                                </div>

                                <div class="bridge-field" style="margin-top: 14px;">
                                    <div class="bridge-label">Destino das notificações SMS</div>
                                    <label class="bridge-check" style="margin-top: 8px;">
                                        <input type="radio" name="sms_recipient_mode" value="alunos" {{ old('sms_recipient_mode', data_get($integration->extra, 'sms_recipient_mode', 'alunos')) === 'alunos' ? 'checked' : '' }} />
                                        <span>Enviar para o telefone indicado no payload (contatos dos alunos / responsáveis)</span>
                                    </label>
                                    <label class="bridge-check" style="margin-top: 8px;">
                                        <input type="radio" name="sms_recipient_mode" value="test_numbers" {{ old('sms_recipient_mode', data_get($integration->extra, 'sms_recipient_mode')) === 'test_numbers' ? 'checked' : '' }} />
                                        <span>Modo testes: enviar <strong>todas</strong> as notificações apenas para os números abaixo</span>
                                    </label>
                                    @error('sms_recipient_mode')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="test_phone_numbers">Números de teste (um por linha, DDI+DDD+número)</label>
                                    <textarea class="bridge-input" id="test_phone_numbers" name="test_phone_numbers" rows="4" style="resize: vertical;" placeholder="5511999998888&#10;5511888887777">{{ old('test_phone_numbers', $testPhoneNumbersDisplay ?? '') }}</textarea>
                                    @error('test_phone_numbers')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                    <div class="bridge-muted" style="margin-top: 6px;">
                                        Obrigatório quando o modo testes estiver ativo. Em produção com alunos, estes números são ignorados.
                                    </div>
                                </div>

                                <hr style="margin: 18px 0; border: none; border-top: 1px solid var(--border);" />

                                <div class="bridge-panel__head" style="margin-top: 8px;">
                                    <div class="bridge-panel__title">Templates de mensagem</div>
                                    <div class="bridge-panel__meta">dois eventos: catraca e confirmação no iEducar</div>
                                </div>

                                <p class="bridge-muted" style="margin-top: 10px; line-height: 1.55;">
                                    <strong>1) Presença na catraca</strong> — enviado quando o evento de acesso marca presença (novo <code>access_event</code>).
                                    <strong>2) Confirmação no iEducar</strong> — enviado após o GIDE receber resposta HTTP de sucesso da API catraca-frequência (preview ou gravação conforme o fluxo).
                                </p>

                                <div class="bridge-field" style="margin-top: 14px;">
                                    <div class="bridge-label">Evento: presença na catraca (<span class="mono">presence_catraca</span>)</div>
                                    <label class="bridge-check" style="margin-top: 8px;">
                                        <input type="checkbox" name="template_catraca_enabled" value="1" @checked(old('template_catraca_enabled', $templateCatraca->enabled)) />
                                        <span>Template ativo</span>
                                    </label>
                                    <textarea class="bridge-input" id="template_catraca_body" name="template_catraca_body" rows="5" style="resize: vertical; margin-top: 8px;">{{ old('template_catraca_body', $templateCatraca->body) }}</textarea>
                                    @error('template_catraca_body')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="bridge-field" style="margin-top: 16px;">
                                    <div class="bridge-label">Evento: confirmação no iEducar (<span class="mono">presence_ieducar_sync</span>)</div>
                                    <label class="bridge-check" style="margin-top: 8px;">
                                        <input type="checkbox" name="template_ieducar_enabled" value="1" @checked(old('template_ieducar_enabled', $templateIeducar->enabled)) />
                                        <span>Template ativo</span>
                                    </label>
                                    <textarea class="bridge-input" id="template_ieducar_body" name="template_ieducar_body" rows="5" style="resize: vertical; margin-top: 8px;">{{ old('template_ieducar_body', $templateIeducar->body) }}</textarea>
                                    @error('template_ieducar_body')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                    <div class="bridge-muted" style="margin-top: 10px;">
                                        Tags comuns: <code>{!! '{{aluno_id}}' !!}</code>, <code>{!! '{{matricula_id}}' !!}</code>, <code>{!! '{{date}}' !!}</code>, <code>{!! '{{time}}' !!}</code>, <code>{!! '{{window}}' !!}</code>, <code>{!! '{{event_type}}' !!}</code>, <code>{!! '{{event_id}}' !!}</code>.
                                        <br />Extra neste template: <code>{!! '{{ieducar_http_status}}' !!}</code> (código HTTP da resposta ao iEducar).
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

