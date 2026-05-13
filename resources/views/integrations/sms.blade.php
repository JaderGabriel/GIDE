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

                            <x-audit-toolbar style="margin-top: 12px;" />

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

                                <div class="bridge-field" style="margin-top: 14px;">
                                    <label class="bridge-label" for="provider">Provedor SMS</label>
                                    <select class="bridge-input" id="provider" name="provider" style="height: 44px;">
                                        @php $pv = old('provider', data_get($integration->extra, 'provider') ?? 'twilio'); @endphp
                                        <option value="twilio" {{ $pv === 'twilio' ? 'selected' : '' }}>Twilio (REST 2010-04-01, Basic Auth)</option>
                                        <option value="zenvia" {{ $pv === 'zenvia' ? 'selected' : '' }}>Zenvia (API v2, X-API-TOKEN)</option>
                                    </select>
                                    @error('provider')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                    <div class="bridge-muted sms-hint-twilio" style="margin-top: 8px; line-height: 1.55;">
                                        <strong>Twilio:</strong> envio via <code>POST …/2010-04-01/Accounts/&lt;AC…&gt;/Messages.json</code> com <code>To</code>, <code>From</code>, <code>Body</code>.
                                        <a href="https://www.twilio.com/docs/sms/api/message-resource#create-a-message-resource" target="_blank" rel="noreferrer">Criar mensagem (doc oficial)</a>
                                        · <a href="https://console.twilio.com/" target="_blank" rel="noreferrer">Console Twilio</a> (Account SID e Auth Token em “Account Info”).
                                        Contas trial só enviam para números verificados — ver <a href="https://www.twilio.com/docs/messaging/guides/how-to-use-your-free-trial-account" target="_blank" rel="noreferrer">trial</a>.
                                    </div>
                                    <div class="bridge-muted sms-hint-zenvia" style="margin-top: 8px; line-height: 1.55;">
                                        <strong>Zenvia:</strong> token no header <code>X-API-TOKEN</code>; remetente conforme canal SMS contratado.
                                        <a href="https://developers.zenvia.com/" target="_blank" rel="noreferrer">Portal de desenvolvedores Zenvia</a>
                                        · <a href="https://developers.zenvia.com/docs/channels/sms-channel" target="_blank" rel="noreferrer">Canal SMS</a>.
                                        O campo “Account SID” da Twilio <strong>não é usado</strong> com Zenvia.
                                    </div>
                                </div>

                                <div class="bridge-field sms-field-twilio">
                                    <label class="bridge-label" for="account_sid">Twilio Account SID</label>
                                    <input class="bridge-input" id="account_sid" name="account_sid" type="text" value="{{ old('account_sid', data_get($integration->extra, 'account_sid') ?? '') }}" placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" autocomplete="off" />
                                    @error('account_sid')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                    <div class="bridge-muted" style="margin-top: 6px;">
                                        No <a href="https://console.twilio.com/" target="_blank" rel="noreferrer">console</a>: copie o <strong>Account SID</strong> (começa por <code>AC</code>). Não use o SID como remetente (<code>From</code>).
                                    </div>
                                </div>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="base_url">Base URL (opcional)</label>
                                    <input class="bridge-input" id="base_url" name="base_url" type="text" value="{{ old('base_url', $integration->base_url ?? '') }}" placeholder="{{ (string) config('integrations.sms.twilio_api_root') }} (Twilio) ou {{ (string) config('integrations.sms.default_base_url') }} (Zenvia)" />
                                    @error('base_url')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                    <div class="bridge-muted sms-hint-twilio" style="margin-top: 6px;">
                                        Em geral deixe vazio: o sistema usa a raiz pública padrão da API Twilio em <code>config/integrations.php</code>. Preencha aqui se usar subconta ou endpoint personalizado (valor gravado no banco).
                                    </div>
                                    <div class="bridge-muted sms-hint-zenvia" style="margin-top: 6px;">
                                        Padrão: URL v2 da Zenvia em config. Altere apenas se a Zenvia indicar outro host para a sua conta.
                                    </div>
                                </div>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="api_token">Credencial secreta</label>
                                    <input class="bridge-input" id="api_token" name="api_token" type="password" value="{{ old('api_token', '') }}" placeholder="{{ $integration->auth_token ? '•••••••••• (já configurado)' : 'Twilio: Auth Token · Zenvia: X-API-TOKEN' }}" autocomplete="new-password" />
                                    @error('api_token')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                    <div class="bridge-muted sms-hint-twilio" style="margin-top: 6px;">
                                        No console Twilio, em Account Info: <strong>Auth Token</strong> (o mesmo usado em <code>curl -u AC…:TOKEN</code>). Se deixar em branco, o valor já gravado não é alterado.
                                    </div>
                                    <div class="bridge-muted sms-hint-zenvia" style="margin-top: 6px;">
                                        Crie ou copie o token em <a href="https://app.zenvia.com/" target="_blank" rel="noreferrer">Zenvia</a> (API / integrações). Cabeçalho enviado: <code>X-API-TOKEN</code>. Se deixar em branco, o valor atual não é alterado.
                                    </div>
                                </div>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="from">From (remetente)</label>
                                    <input class="bridge-input" id="from" name="from" type="text" value="{{ old('from', data_get($integration->extra, 'from') ?? '') }}" placeholder="+14155552671 ou identificador Zenvia" />
                                    @error('from')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                    <div class="bridge-muted sms-hint-twilio" style="margin-top: 6px;">
                                        Número ou sender ID comprado na conta Twilio, em <strong>E.164</strong> (ex. <code>+5511999998888</code>). Ver números em <a href="https://console.twilio.com/us1/develop/phone-numbers/manage/incoming" target="_blank" rel="noreferrer">Phone Numbers</a>.
                                    </div>
                                    <div class="bridge-muted sms-hint-zenvia" style="margin-top: 6px;">
                                        Identificador do remetente autorizado no contrato Zenvia (SMS). Confirme no painel o valor exato exigido pela API do canal.
                                    </div>
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
        <script>
            (function () {
                var sel = document.getElementById('provider');
                if (!sel) return;
                function sync() {
                    var v = sel.value;
                    document.querySelectorAll('.sms-hint-twilio').forEach(function (el) {
                        el.style.display = v === 'twilio' ? '' : 'none';
                    });
                    document.querySelectorAll('.sms-hint-zenvia').forEach(function (el) {
                        el.style.display = v === 'zenvia' ? '' : 'none';
                    });
                    document.querySelectorAll('.sms-field-twilio').forEach(function (el) {
                        el.style.display = v === 'twilio' ? '' : 'none';
                    });
                }
                sel.addEventListener('change', sync);
                sync();
            })();
        </script>
    </body>
</html>

