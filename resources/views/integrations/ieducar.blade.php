<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Integrações • iEducar • {{ config('app.name', 'Bridge ERP') }}</title>

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
                                <div class="bridge-brand__tagline">Configuração • iEducar</div>
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
                                <div class="bridge-panel__title">Integração iEducar</div>
                                <div class="bridge-panel__meta">tokens • autenticação • inbound</div>
                            </div>

                            <x-audit-toolbar style="margin-top: 12px;" />

                            @if (session('status'))
                                <p class="bridge-muted" style="margin-top: 12px;">
                                    <strong>{{ session('status') }}</strong>
                                </p>
                            @endif

                            <form method="POST" action="{{ route('integrations.ieducar.update') }}" class="bridge-form">
                                @csrf

                                <hr style="margin: 6px 0 18px; border: none; border-top: 1px solid var(--border);" />

                                <div class="bridge-panel__head" style="margin-top: 8px;">
                                    <div class="bridge-panel__title">iEducar → GIDE</div>
                                    <div class="bridge-panel__meta">Catraca/Frequência • Bearer inbound</div>
                                </div>

                                <p class="bridge-muted" style="margin-top: 12px;">
                                    <strong>Fluxo de dados (resumo):</strong> o iEducar chama o GIDE (endpoints fixos) para criar um <strong>token de coleta</strong> (abrir <code>/facial/enviar</code>) e para registar operações de exclusão.
                                    Em seguida, a coleta/“enroll” é feita pelo GIDE junto do Gestor; o GIDE pode ainda chamar o iEducar para <strong>consulta</strong> e <strong>confirmação</strong>.
                                </p>

                                <div class="bridge-field" style="margin-top: 12px;">
                                    <label class="bridge-label">Endpoints (iEducar → GIDE)</label>
                                    <div class="bridge-muted" style="margin-top: 6px;">
                                        - <code>POST {{ url('/api/v1/catraca-frequencia/gide/facial/nova') }}</code>
                                        <br />- <code>POST {{ url('/api/v1/catraca-frequencia/gide/facial/excluir') }}</code>
                                    </div>
                                </div>

                                <label class="bridge-check" style="margin-top: 10px;">
                                    <input type="checkbox" name="catraca_enabled" value="1" {{ ($catraca_integration->enabled ?? false) ? 'checked' : '' }} />
                                    <span>Habilitar recebimento (Bearer) do iEducar → GIDE</span>
                                </label>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="catraca_inbound_token">Token (Bearer) do iEducar → GIDE</label>
                                    <input class="bridge-input" id="catraca_inbound_token" name="catraca_inbound_token" type="password" value="{{ old('catraca_inbound_token', '') }}" placeholder="{{ ($catraca_integration->auth_token ?? null) ? '•••••••••• (já configurado)' : 'cole aqui o token' }}" />
                                    @error('catraca_inbound_token')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                    <div class="bridge-muted" style="margin-top: 6px;">
                                        Se deixar em branco, o token atual não é alterado.
                                    </div>
                                </div>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="base_url">Base URL do iEducar</label>
                                    <input class="bridge-input" id="base_url" name="base_url" type="text" value="{{ old('base_url', $integration->base_url ?? '') }}" placeholder="URL base HTTPS da sua instância iEducar" />
                                    @error('base_url')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="access_key">iEducar `access_key` (para o GIDE chamar o iEducar)</label>
                                    <input class="bridge-input" id="access_key" name="access_key" type="text" value="{{ old('access_key', data_get($integration->extra, 'access_key') ?? '') }}" placeholder="access_key do ieducar.ini" />
                                    @error('access_key')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="api_token">Token (Bearer) da API do iEducar</label>
                                    <input class="bridge-input" id="api_token" name="api_token" type="password" value="{{ old('api_token', '') }}" placeholder="{{ $integration->auth_token ? '•••••••••• (já configurado)' : 'cole aqui o token' }}" />
                                    @error('api_token')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                    <div class="bridge-muted" style="margin-top: 6px;">
                                        Se deixar em branco, o token atual não é alterado.
                                    </div>
                                </div>

                                <hr style="margin: 18px 0; border: none; border-top: 1px solid var(--border);" />

                                <div class="bridge-panel__head" style="margin-top: 8px;">
                                    <div class="bridge-panel__title">GIDE → iEducar</div>
                                    <div class="bridge-panel__meta">confirmação/consulta • endpoints fixos</div>
                                </div>

                                <p class="bridge-muted" style="margin-top: 12px;">
                                    Para testes do package de catraca/frequência, o GIDE chama o iEducar em endpoints <strong>fixos</strong>:
                                    <br />- <code>/api/catraca-frequencia/gide/facial/confirmacao</code>
                                    <br />- <code>/api/catraca-frequencia/gide/aluno/consulta</code>
                                </p>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="catraca_frequencia_confirmacao_token">Token (Bearer) para confirmação/consulta</label>
                                    <input class="bridge-input" id="catraca_frequencia_confirmacao_token" name="catraca_frequencia_confirmacao_token" type="password" value="{{ old('catraca_frequencia_confirmacao_token', '') }}" placeholder="{{ data_get($integration->extra, 'catraca_frequencia.confirmacao_token') ? '•••••••••• (já configurado)' : 'cole aqui o token' }}" />
                                    @error('catraca_frequencia_confirmacao_token')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                    <div class="bridge-muted" style="margin-top: 6px;">
                                        Se deixar em branco, o token atual não é alterado. Se não houver token dedicado, o GIDE usa o token principal acima como fallback.
                                    </div>
                                </div>

                                <div class="bridge-field" style="margin-top: 12px;">
                                    <label class="bridge-label">Registro de frequência (lotes GIDE → iEducar)</label>
                                    <div class="bridge-muted" style="margin-top: 6px;">
                                        Endpoint no iEducar: <code class="mono">POST /api/catraca-frequencia/gide/frequencia/registro</code> (Bearer acima).
                                        Contrato <strong>por aluno</strong> (<code class="mono">cod_aluno</code> + <code class="mono">data_ref</code>). Preview e gravação entram na <strong>fila</strong> e podem ser acompanhados pelo ID da entrega.
                                    </div>
                                    <div class="bridge-form__actions" style="margin-top: 10px;">
                                        <a class="bridge-btn" href="{{ route('integrations.ieducar.frequencia-registro') }}">Abrir envio de frequência</a>
                                        <a class="bridge-btn bridge-btn--primary" href="{{ route('integrations.docs.ieducar-frequencia-registro') }}" target="_blank" rel="noreferrer">Documentação</a>
                                    </div>
                                </div>

                                <hr style="margin: 18px 0; border: none; border-top: 1px solid var(--border);" />

                                <div class="bridge-panel__head" style="margin-top: 8px;">
                                    <div class="bridge-panel__title">iEducar → GIDE (legado)</div>
                                    <div class="bridge-panel__meta">HMAC inbound</div>
                                </div>

                                <label class="bridge-check" style="margin-top: 10px;">
                                    <input type="checkbox" name="enabled" value="1" {{ $integration->enabled ? 'checked' : '' }} />
                                    <span>Habilitar validação HMAC nas rotas inbound do iEducar (enrollments/facial-requests)</span>
                                </label>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="signature_ttl_seconds">TTL da assinatura (segundos)</label>
                                    <input class="bridge-input" id="signature_ttl_seconds" name="signature_ttl_seconds" type="number" min="30" max="3600" value="{{ old('signature_ttl_seconds', $integration->signature_ttl_seconds) }}" />
                                    @error('signature_ttl_seconds')
                                        <div class="bridge-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="bridge-form__actions">
                                    <button type="submit" class="bridge-btn bridge-btn--primary">Salvar</button>
                                    <a class="bridge-btn" href="/dashboard">Voltar</a>
                                </div>
                            </form>

                            <hr style="margin: 18px 0; border: none; border-top: 1px solid var(--border);" />

                            <div class="bridge-panel__head" style="margin-top: 8px;">
                                <div class="bridge-panel__title">Token de envio (iEducar → GIDE)</div>
                                <div class="bridge-panel__meta">HMAC</div>
                            </div>

                            <p class="bridge-muted" style="margin-top: 12px;">
                                O iEducar deve chamar o GIDE em <strong>/api/v1/ieducar/enrollments</strong> enviando JSON e assinando o corpo com HMAC.
                                O segredo abaixo é o “token” compartilhado para gerar a assinatura.
                            </p>

                            <div class="bridge-field">
                                <label class="bridge-label">Segredo HMAC atual</label>
                                <input class="bridge-input" type="text" readonly value="{{ $integration->hmac_secret ? '•••••••••••••••••••••••••••••••• (configurado)' : '(não configurado)' }}" />
                            </div>

                            <form method="POST" action="{{ route('integrations.ieducar.rotate-hmac') }}" class="bridge-form">
                                @csrf
                                <div class="bridge-form__actions">
                                    <button type="submit" class="bridge-btn">Gerar/rotacionar segredo HMAC</button>
                                </div>
                            </form>

                            <p class="bridge-muted" style="margin-top: 12px;">
                                Headers esperados pelo GIDE:
                                <br />- <strong>X-Event-Id</strong>: id único do evento
                                <br />- <strong>X-Timestamp</strong>: epoch seconds
                                <br />- <strong>X-Signature</strong>: <code>HMAC_SHA256(secret, \"{timestamp}.{eventId}.{rawBody}\")</code>
                            </p>
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

