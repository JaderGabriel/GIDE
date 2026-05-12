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
            /* ─── Tabs ─── */
            .gt-tabs {
                display: flex;
                gap: 0;
                border-bottom: 2px solid var(--border);
                margin-bottom: 0;
            }
            .gt-tab {
                appearance: none;
                border: none;
                background: none;
                font-family: inherit;
                font-size: 14px;
                font-weight: 600;
                color: var(--muted);
                padding: 10px 18px;
                cursor: pointer;
                border-bottom: 2px solid transparent;
                margin-bottom: -2px;
                transition: color .15s, border-color .15s;
                white-space: nowrap;
            }
            .gt-tab:hover { color: var(--text); }
            .gt-tab--active {
                color: var(--accent-a);
                border-bottom-color: var(--accent-a);
            }
            .gt-tab__count {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 20px;
                height: 20px;
                padding: 0 6px;
                border-radius: 10px;
                font-size: 11px;
                font-weight: 700;
                margin-left: 6px;
                background: color-mix(in srgb, var(--muted) 15%, transparent);
                color: var(--muted);
            }
            .gt-tab--active .gt-tab__count {
                background: color-mix(in srgb, var(--accent-a) 15%, transparent);
                color: var(--accent-a);
            }
            .gt-pane { display: none; }
            .gt-pane--active { display: block; }

            /* ─── Sections ─── */
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
                margin: 0 0 6px;
                letter-spacing: 0.02em;
            }
            .gestor-section__lead {
                margin: 0 0 14px;
                color: var(--muted);
                font-size: 14px;
                line-height: 1.55;
            }

            /* ─── Callout info boxes ─── */
            .gt-callout {
                display: flex;
                gap: 10px;
                padding: 12px 14px;
                border-radius: 12px;
                font-size: 13px;
                line-height: 1.5;
                margin-bottom: 16px;
            }
            .gt-callout--info {
                background: color-mix(in srgb, var(--accent-a) 8%, transparent);
                border: 1px solid color-mix(in srgb, var(--accent-a) 25%, var(--border));
                color: var(--text);
            }
            .gt-callout--warn {
                background: color-mix(in srgb, #f59e0b 8%, transparent);
                border: 1px solid color-mix(in srgb, #f59e0b 30%, var(--border));
                color: var(--text);
            }
            .gt-callout__icon {
                flex-shrink: 0;
                font-size: 16px;
                line-height: 1.5;
            }
            .gt-callout__text { flex: 1; }
            .gt-callout__text strong { font-weight: 700; }

            /* ─── Inbound cards ─── */
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

            /* ─── Save bar ─── */
            .gt-save-bar {
                margin-top: 20px;
                padding-top: 16px;
                border-top: 1px solid var(--border);
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .gt-save-hint {
                flex: 1;
                font-size: 12px;
                color: var(--muted);
                line-height: 1.4;
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

                        <x-audit-toolbar style="margin-bottom: 16px;" />

                        @if ($errors->any())
                            <div class="bridge-error" style="margin-bottom: 12px;">
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
                            <div style="margin-bottom: 12px; padding: 10px 12px; border-radius: 14px; border: 1px solid {{ $border }}; background: {{ $bg }}; color: {{ $color }};">
                                <strong>{{ session('status') }}</strong>
                            </div>
                        @endif

                        <div class="bridge-panel" style="padding-bottom: 0;">
                            <div class="bridge-panel__head" style="margin-bottom: 14px;">
                                <div class="bridge-panel__title">Integração Gestor</div>
                                <div class="bridge-panel__meta">configuração completa da integração com a catraca (Porter/Kiper SDK) e do motor de presença GIDE</div>
                            </div>

                            {{-- ═══════ Abas ═══════ --}}
                            <div class="gt-tabs" id="gt-tabs">
                                <button type="button" class="gt-tab gt-tab--active" data-tab="sdk">
                                    Conexão e Convite
                                </button>
                                <button type="button" class="gt-tab" data-tab="presenca">
                                    Motor de presença
                                </button>
                                <button type="button" class="gt-tab" data-tab="canais">
                                    Canais e Testes
                                </button>
                            </div>

                            {{-- ═══════════════════════════════════════════════
                                 ABA 1 — Conexão SDK + Outbound (Convite)
                                 ═══════════════════════════════════════════════ --}}
                            <div class="gt-pane gt-pane--active" id="pane-sdk" style="padding: 18px 0 20px;">
                                <form method="POST" action="{{ route('integrations.gestor.update-sdk') }}" class="bridge-form">
                                    @csrf

                                    <div class="gt-callout gt-callout--info">
                                        <div class="gt-callout__icon">&#9432;</div>
                                        <div class="gt-callout__text">
                                            <strong>O que é esta aba?</strong> Aqui ficam as credenciais que permitem ao GIDE se comunicar com o SDK da catraca (Porter/Kiper).
                                            Quando uma matrícula chega do iEducar, o GIDE usa estes dados para fazer <em>Signin</em> no SDK e criar o <em>Invite</em> (convite) do aluno na catraca.
                                            <br>Ao salvar, o token Bearer atual é <strong>limpo</strong> e será renovado automaticamente na próxima chamada.
                                        </div>
                                    </div>

                                    <div class="gestor-section gestor-section--first">
                                        <div class="gestor-section__title">Credenciais do SDK</div>
                                        <p class="gestor-section__lead">
                                            URL base, chave de aplicação e credenciais de autenticação para o GIDE chamar a API do Gestor.
                                        </p>

                                        <label class="bridge-check">
                                            <input type="checkbox" name="enabled" value="1" {{ $integration->enabled ? 'checked' : '' }} />
                                            <span>Habilitar integração Gestor (habilita chamadas outbound e validação de eventos recebidos)</span>
                                        </label>

                                        <div class="bridge-field">
                                            <label class="bridge-label" for="base_url">Base URL do SDK</label>
                                            <input class="bridge-input" id="base_url" name="base_url" type="text" value="{{ old('base_url', $integration->base_url ?? '') }}" placeholder="https://sdk.exemplo.com.br" />
                                            @error('base_url') <div class="bridge-error">{{ $message }}</div> @enderror
                                            <div class="bridge-muted" style="margin-top: 4px;">Endereço raiz da API. Ex: <span class="mono">https://sdk.porterkiper.com.br</span></div>
                                        </div>

                                        <div class="bridge-field">
                                            <label class="bridge-label" for="application_key">ApplicationKey</label>
                                            <input class="bridge-input" id="application_key" name="application_key" type="text" value="{{ old('application_key', data_get($integration->extra, 'application_key') ?? '') }}" placeholder="Chave fornecida pelo Gestor" />
                                            @error('application_key') <div class="bridge-error">{{ $message }}</div> @enderror
                                            <div class="bridge-muted" style="margin-top: 4px;">Header obrigatório em todas as chamadas ao SDK.</div>
                                        </div>

                                        <div class="bridge-field">
                                            <label class="bridge-label" for="auth_username">Username (Signin)</label>
                                            <input class="bridge-input" id="auth_username" name="auth_username" type="text" value="{{ old('auth_username', data_get($integration->extra, 'auth.username') ?? '') }}" />
                                            @error('auth_username') <div class="bridge-error">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="bridge-field">
                                            <label class="bridge-label" for="auth_password">Password (Signin)</label>
                                            <input class="bridge-input" id="auth_password" name="auth_password" type="password" value="{{ old('auth_password', data_get($integration->extra, 'auth.password') ?? '') }}" placeholder="deixe em branco para manter a atual" />
                                            @error('auth_password') <div class="bridge-error">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="bridge-field">
                                            <label class="bridge-label" for="signature_ttl_seconds">TTL da assinatura HMAC (segundos)</label>
                                            <input class="bridge-input" id="signature_ttl_seconds" name="signature_ttl_seconds" type="number" min="30" max="3600" value="{{ old('signature_ttl_seconds', $integration->signature_ttl_seconds) }}" />
                                            @error('signature_ttl_seconds') <div class="bridge-error">{{ $message }}</div> @enderror
                                            <div class="bridge-muted" style="margin-top: 4px;">Tempo de validade das assinaturas HMAC nos webhooks recebidos do Gestor. Padrão: 300s.</div>
                                        </div>
                                    </div>

                                    <div class="gestor-section">
                                        <div class="gestor-section__title">Convite (Outbound)</div>
                                        <p class="gestor-section__lead">
                                            Quando o iEducar envia uma matrícula ao GIDE, o sistema monta um JSON de <strong>Invite</strong> e faz <span class="mono">POST</span> ao SDK.
                                            Configure abaixo o path do endpoint e os IDs padrão que serão usados no payload.
                                        </p>

                                        <div class="bridge-field">
                                            <label class="bridge-label" for="outbound_enrollment_path">Path do POST (enrollment / convite)</label>
                                            <input class="bridge-input mono" id="outbound_enrollment_path" name="outbound_enrollment_path" type="text" value="{{ old('outbound_enrollment_path', data_get($integration->extra, 'endpoints.enrollment_sync_path') ?? '') }}" placeholder="/SDK/Invite" />
                                            @error('outbound_enrollment_path') <div class="bridge-error">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="bridge-field">
                                            <label class="bridge-label" for="unity_id">unityId</label>
                                            <input class="bridge-input mono" id="unity_id" name="unity_id" type="text" inputmode="numeric" pattern="[0-9]*" value="{{ \App\Support\GestorStoredIds::stringForNumericInput(old('unity_id', data_get($integration->extra, 'defaults.unity_id') ?? data_get($integration->extra, 'onboarding.unity_id'))) }}" placeholder="inteiro > 0 ou vazio" />
                                            @error('unity_id') <div class="bridge-error">{{ $message }}</div> @enderror
                                            <div class="bridge-muted" style="margin-top: 4px;">
                                                ID da unidade no Gestor. Prioridade: <span class="mono">onboarding</span> > <span class="mono">defaults</span>. Vazio ou 0 = omitido no JSON.
                                            </div>
                                        </div>

                                        <div class="bridge-field">
                                            <label class="bridge-label" for="access_profile_id">accessProfileId</label>
                                            <input class="bridge-input mono" id="access_profile_id" name="access_profile_id" type="text" inputmode="numeric" pattern="[0-9]*" value="{{ \App\Support\GestorStoredIds::stringForNumericInput(old('access_profile_id', data_get($integration->extra, 'defaults.access_profile_id') ?? data_get($integration->extra, 'onboarding.access_profile_id'))) }}" placeholder="> 0 ou vazio → null no JSON" />
                                            @error('access_profile_id') <div class="bridge-error">{{ $message }}</div> @enderror
                                            <div class="bridge-muted" style="margin-top: 4px;">
                                                Perfil de acesso no convite. Mesma regra de prioridade. Vazio ou 0 envia <span class="mono">null</span>.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="gt-save-bar">
                                        <div class="gt-save-hint">Grava credenciais SDK, path de convite, IDs padrão e TTL. O token Bearer atual é limpo.</div>
                                        <button type="submit" class="bridge-btn bridge-btn--primary">Salvar conexão</button>
                                    </div>
                                </form>
                            </div>

                            {{-- ═══════════════════════════════════════════════
                                 ABA 2 — Motor de presença GIDE
                                 ═══════════════════════════════════════════════ --}}
                            <div class="gt-pane" id="pane-presenca" style="padding: 18px 0 20px;">
                                <form method="POST" action="{{ route('integrations.gestor.update-presence') }}" class="bridge-form">
                                    @csrf

                                    <div class="gt-callout gt-callout--info">
                                        <div class="gt-callout__icon">&#9432;</div>
                                        <div class="gt-callout__text">
                                            <strong>O que é o motor de presença?</strong> Quando a catraca envia um evento de acesso (<span class="mono">POST /api/v1/…/access-events</span>),
                                            o GIDE decide se marca frequência no iEducar. Esta decisão é feita pelo <em>PresenceRuleEngine</em>, que pode operar em 4 modos.
                                            <br>Documentação completa: <code>docs/MOTOR_PRESENCA.md</code>.
                                        </div>
                                    </div>

                                    <div class="gestor-section gestor-section--first">
                                        <div class="gestor-section__title">Modo do motor</div>
                                        <p class="gestor-section__lead">
                                            Escolha como o motor decide se marca presença. Cada modo atende a um cenário diferente — leia a descrição para escolher o mais adequado.
                                        </p>
                                        @php $curMode = old('presence_mode', $presenceMode ?? 'auto'); @endphp
                                        <div style="display: grid; gap: 10px;">
                                            <label class="bridge-check">
                                                <input type="radio" name="presence_mode" value="auto" @checked($curMode === 'auto') />
                                                <span><strong>Automático (janelas de horário)</strong> — marca presença apenas nos horários configurados abaixo. Se o payload enviar <span class="mono">action.mark_presence=true</span>, marca mesmo fora das janelas. Ideal para escolas com turnos definidos.</span>
                                            </label>
                                            <label class="bridge-check">
                                                <input type="radio" name="presence_mode" value="always_mark" @checked($curMode === 'always_mark') />
                                                <span><strong>Sempre marcar</strong> — marca presença para todo evento com aluno identificado, independente de horário. Útil para testes ou escolas sem controle de turno.</span>
                                            </label>
                                            <label class="bridge-check">
                                                <input type="radio" name="presence_mode" value="explicit_only" @checked($curMode === 'explicit_only') />
                                                <span><strong>Somente explícito</strong> — só marca se o payload da catraca incluir <span class="mono">action.mark_presence=true</span>. O GIDE não decide sozinho; a decisão vem do sistema emissor.</span>
                                            </label>
                                            <label class="bridge-check">
                                                <input type="radio" name="presence_mode" value="disabled" @checked($curMode === 'disabled') />
                                                <span><strong>Desabilitado</strong> — o motor nunca marca presença. Eventos ainda são recebidos e armazenados, mas nada é enviado ao iEducar.</span>
                                            </label>
                                        </div>
                                        @error('presence_mode') <div class="bridge-error">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="gestor-section">
                                        <div class="gestor-section__title">Filtros e janelas de horário</div>
                                        <p class="gestor-section__lead">
                                            As janelas definem os períodos em que o modo <strong>Automático</strong> marca presença. Fora delas (incluindo a tolerância), o motor ignora o evento.
                                            A <strong>tolerância</strong> (±min) expande a janela: se a janela é 07:00–09:30 com ±15min, eventos entre 06:45 e 09:45 são aceites.
                                        </p>

                                        <label class="bridge-check" style="margin-bottom: 14px;">
                                            <input type="checkbox" name="presence_ignore_exit" value="1" @checked(old('presence_ignore_exit', $presenceIgnoreExit ?? true)) />
                                            <span>Ignorar eventos de saída (quando <span class="mono">type</span> contém "saida" ou "exit", o motor não marca presença)</span>
                                        </label>

                                        <div class="bridge-field">
                                            <div class="bridge-label">Janelas de horário</div>
                                            <div id="presence-windows-editor" style="margin-top: 10px;"></div>
                                            <input type="hidden" name="presence_windows" id="presence_windows_json" value="" />
                                            @error('presence_windows') <div class="bridge-error">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div class="gestor-section">
                                        <div class="gestor-section__title">Mapeamento de campos do payload</div>
                                        <p class="gestor-section__lead">
                                            O JSON que a catraca envia pode usar nomes de campos diferentes do padrão.
                                            Configure aqui qual campo corresponde a cada informação que o motor precisa.
                                        </p>
                                        <div style="display: grid; gap: 10px; grid-template-columns: 1fr 1fr 1fr;">
                                            <div>
                                                <label class="bridge-label" for="presence_map_aluno_id" style="font-size: 12px;">Campo para aluno_id</label>
                                                <input class="bridge-input mono" id="presence_map_aluno_id" name="presence_map_aluno_id" type="text" value="{{ old('presence_map_aluno_id', $presencePayloadMap['aluno_id'] ?? 'aluno_id') }}" placeholder="aluno_id" />
                                            </div>
                                            <div>
                                                <label class="bridge-label" for="presence_map_matricula_id" style="font-size: 12px;">Campo para matricula_id</label>
                                                <input class="bridge-input mono" id="presence_map_matricula_id" name="presence_map_matricula_id" type="text" value="{{ old('presence_map_matricula_id', $presencePayloadMap['matricula_id'] ?? 'matricula_id') }}" placeholder="matricula_id" />
                                            </div>
                                            <div>
                                                <label class="bridge-label" for="presence_map_event_type" style="font-size: 12px;">Campo para tipo do evento</label>
                                                <input class="bridge-input mono" id="presence_map_event_type" name="presence_map_event_type" type="text" value="{{ old('presence_map_event_type', $presencePayloadMap['event_type'] ?? 'type') }}" placeholder="type" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="gestor-section">
                                        <div class="gestor-section__title">Ambiente iEducar</div>
                                        <p class="gestor-section__lead">
                                            Quando o motor decide marcar presença, o GIDE envia a frequência ao iEducar.
                                            Em <strong>Preview</strong>, o iEducar recebe mas <em>não grava</em> — útil para validar a integração.
                                            Em <strong>Homologação</strong>, a frequência é efetivamente registrada.
                                        </p>

                                        <div class="gt-callout gt-callout--warn" style="margin-top: 4px;">
                                            <div class="gt-callout__icon">&#9888;</div>
                                            <div class="gt-callout__text">
                                                <strong>Atenção:</strong> mudar para Homologação faz o iEducar gravar frequência de verdade. Confirme que as janelas e o modo estão corretos antes.
                                            </div>
                                        </div>

                                        <div style="display: grid; gap: 10px; margin-top: 10px;">
                                            <label class="bridge-check">
                                                <input type="radio" name="ieducar_processing_environment" value="preview" {{ old('ieducar_processing_environment', data_get($integration->extra, 'ieducar_processing.environment', 'homolog')) === 'preview' ? 'checked' : '' }} />
                                                <span><strong>Preview</strong> (<span class="mono">meta.preview=true</span>) — iEducar recebe mas não grava</span>
                                            </label>
                                            <label class="bridge-check">
                                                <input type="radio" name="ieducar_processing_environment" value="homolog" {{ old('ieducar_processing_environment', data_get($integration->extra, 'ieducar_processing.environment', 'homolog')) === 'homolog' ? 'checked' : '' }} />
                                                <span><strong>Homologação</strong> (<span class="mono">meta.preview=false</span>) — iEducar grava frequência</span>
                                            </label>
                                        </div>
                                        @error('ieducar_processing_environment') <div class="bridge-error">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="gt-save-bar">
                                        <div class="gt-save-hint">Grava modo, janelas, mapeamento e ambiente. Não altera credenciais SDK nem token Bearer.</div>
                                        <button type="submit" class="bridge-btn bridge-btn--primary">Salvar motor</button>
                                    </div>
                                </form>
                            </div>

                            {{-- ═══════════════════════════════════════════════
                                 ABA 3 — Canais de recebimento e testes
                                 ═══════════════════════════════════════════════ --}}
                            <div class="gt-pane" id="pane-canais" style="padding: 18px 0 20px;">

                                <div class="gt-callout gt-callout--info">
                                    <div class="gt-callout__icon">&#9432;</div>
                                    <div class="gt-callout__text">
                                        <strong>O que é esta aba?</strong> Aqui você testa a conexão com o SDK (Signin),
                                        gerencia o segredo HMAC do canal Gestor e o token Bearer do canal da catraca.
                                        Estes são os dois canais por onde eventos de acesso chegam ao GIDE.
                                        <br>Auditoria dos eventos recebidos: <a href="{{ url('/admin/gestor-access-events') }}" style="color: var(--accent-a); font-weight: 600;">/admin/gestor-access-events</a>.
                                    </div>
                                </div>

                                <div class="gestor-section gestor-section--first">
                                    <div class="gestor-section__title">Testar conexão com o SDK</div>
                                    <p class="gestor-section__lead">
                                        Use estes botões para verificar se as credenciais da aba "Conexão e Convite" estão funcionando.
                                        O "Testar auth" faz Signin no SDK; o "Testar Unities" lista unidades para confirmar o acesso.
                                    </p>
                                    <div class="bridge-field">
                                        <label class="bridge-label">Estado do token Bearer</label>
                                        <input class="bridge-input" type="text" readonly value="{{ $integration->auth_token ? '•••••••••••••••• (há token salvo)' : '(nenhum — rode Signin após salvar credenciais)' }}" />
                                    </div>
                                    <div style="display: flex; gap: 8px; margin-top: 10px;">
                                        <form method="POST" action="{{ route('integrations.gestor.test-auth') }}">
                                            @csrf
                                            <button type="submit" class="bridge-btn">Testar auth (Signin)</button>
                                        </form>
                                        <form method="POST" action="{{ route('integrations.gestor.test-unities') }}">
                                            @csrf
                                            <button type="submit" class="bridge-btn">Testar listagem de Unities</button>
                                        </form>
                                    </div>
                                </div>

                                <div class="gestor-section">
                                    <div class="gestor-section__title">Canal: Gestor → GIDE (HMAC)</div>
                                    <p class="gestor-section__lead">
                                        Rota: <span class="mono">POST /api/v1/gestor/access-events</span>.
                                        O Gestor assina o corpo do webhook com HMAC e envia nos headers.
                                        O GIDE valida a assinatura usando o segredo abaixo.
                                    </p>
                                    <div class="gestor-inbound-card">
                                        <div class="bridge-field">
                                            <label class="bridge-label">Segredo HMAC</label>
                                            <input class="bridge-input" type="text" readonly value="{{ $integration->hmac_secret ? '•••••••••••••••• (configurado)' : '(não configurado)' }}" />
                                        </div>
                                        <form method="POST" action="{{ route('integrations.gestor.rotate-hmac') }}" style="margin-top: 8px;">
                                            @csrf
                                            <button type="submit" class="bridge-btn">Gerar ou rotacionar segredo HMAC</button>
                                        </form>
                                    </div>
                                </div>

                                <div class="gestor-section">
                                    <div class="gestor-section__title">Canal: Catraca → GIDE (Bearer Token)</div>
                                    <p class="gestor-section__lead">
                                        Rota: <span class="mono">POST {{ $catracaWebhookUrl ?? url('/api/v1/catraca/access-events') }}</span>.
                                        A catraca envia eventos com <span class="mono">Authorization: Bearer &lt;token&gt;</span>.
                                        O GIDE guarda <strong>somente o hash</strong> do token; o valor em claro aparece <strong>uma única vez</strong> após a geração.
                                    </p>
                                    <div class="gestor-inbound-card">
                                        <div style="margin-bottom: 10px;">
                                            <span class="bridge-chip" style="{{ ! empty($catracaWebhookBearerConfigured) ? 'border-color: color-mix(in srgb, var(--accent-c) 40%, var(--border));' : '' }}">
                                                {{ ! empty($catracaWebhookBearerConfigured) ? 'Token configurado' : 'Token não configurado' }}
                                            </span>
                                        </div>

                                        @if (session('gestor_catraca_webhook_bearer_plaintext'))
                                            <div style="margin-bottom: 12px; padding: 12px 14px; border-radius: 14px; border: 2px solid color-mix(in srgb, var(--accent-c) 45%, var(--border)); background: color-mix(in srgb, var(--accent-c) 10%, var(--surface-1));">
                                                <div style="font-weight: 800; margin-bottom: 8px;">Token gerado — copie agora (não será exibido de novo)</div>
                                                <label class="bridge-label" for="catraca_wh_once">Valor do token</label>
                                                <input class="bridge-input mono" id="catraca_wh_once" type="text" readonly value="{{ session('gestor_catraca_webhook_bearer_plaintext') }}" onclick="this.select()" style="font-size: 13px;" />
                                                <div class="bridge-muted" style="margin-top: 8px; font-size: 12px;">Some ao recarregar a página. Guarde na catraca ou em cofre.</div>
                                            </div>
                                        @endif

                                        <form method="POST" action="{{ route('integrations.gestor.generate-catraca-webhook-bearer') }}" onsubmit="return confirm('Gerar um novo token invalida o anterior na catraca. Continuar?');">
                                            @csrf
                                            <button type="submit" class="bridge-btn">{{ ! empty($catracaWebhookBearerConfigured) ? 'Gerar novo token (invalida o atual)' : 'Gerar token de acesso da catraca' }}</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        </div>{{-- /bridge-panel --}}

                        <div style="margin-top: 16px; text-align: center;">
                            <a class="bridge-btn" href="{{ url('/dashboard') }}">Voltar ao dashboard</a>
                        </div>

                    </div>
                </div>
            </main>

            <footer class="bridge-footer">
                <div class="bridge-container">
                    <div class="bridge-footer__inner">
                        <div>&copy; {{ now()->year }} {{ config('app.name', 'Bridge ERP') }}</div>
                        <div class="bridge-footer__right">
                            <a href="https://github.com/jadergabriel" target="_blank" rel="noreferrer">Powered by Jader Gabriel</a>
                            <span class="bridge-sep">&bull;</span>
                            <span>Laravel v{{ app()->version() }}</span>
                        </div>
                    </div>
                </div>
            </footer>
        </div>

        {{-- ─── Presence windows editor styles ─── --}}
        <style>
            .pw-editor { display: flex; flex-direction: column; gap: 8px; }
            .pw-row { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 12px; border: 1px solid var(--border); background: color-mix(in srgb, var(--surface-2) 60%, transparent); flex-wrap: wrap; }
            .pw-row input { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 13px; padding: 4px 8px; border: 1px solid var(--border); border-radius: 8px; background: var(--surface-1); color: var(--text); }
            .pw-row input[type="text"] { flex: 1; min-width: 80px; }
            .pw-row input[type="time"] { width: 110px; }
            .pw-row input[type="number"] { width: 68px; text-align: center; }
            .pw-lbl { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); white-space: nowrap; }
            .pw-rm { appearance: none; border: none; background: none; color: var(--muted); cursor: pointer; font-size: 18px; padding: 0 4px; line-height: 1; }
            .pw-rm:hover { color: #ef4444; }
            .pw-add { appearance: none; display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 10px; border: 1px dashed var(--border); background: transparent; color: var(--muted); font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; }
            .pw-add:hover { border-color: color-mix(in srgb, var(--accent-a) 40%, var(--border)); color: var(--text); }
            .pw-empty { font-size: 13px; color: var(--muted); padding: 8px 0; }
        </style>

        {{-- ─── Tab switching + URL persistence ─── --}}
        <script>
        (function () {
            var tabs = document.querySelectorAll('.gt-tab');
            var panes = document.querySelectorAll('.gt-pane');

            function activate(name) {
                tabs.forEach(function (t) {
                    t.classList.toggle('gt-tab--active', t.dataset.tab === name);
                });
                panes.forEach(function (p) {
                    p.classList.toggle('gt-pane--active', p.id === 'pane-' + name);
                });
            }

            tabs.forEach(function (t) {
                t.addEventListener('click', function () {
                    var name = this.dataset.tab;
                    activate(name);
                    history.replaceState(null, '', '?tab=' + name);
                });
            });

            var params = new URLSearchParams(window.location.search);
            var initial = params.get('tab');
            if (initial && document.getElementById('pane-' + initial)) {
                activate(initial);
            }
        })();
        </script>

        {{-- ─── Presence windows editor ─── --}}
        <script>
        (function () {
            var initial = @json($presenceWindows ?? []);
            var container = document.getElementById('presence-windows-editor');
            var hidden = document.getElementById('presence_windows_json');
            var windows = Array.isArray(initial) ? initial.map(function (w) {
                return { name: w.name || '', start: w.start || '', end: w.end || '', tolerance_minutes: parseInt(w.tolerance_minutes) || 0 };
            }) : [];

            function render() {
                hidden.value = JSON.stringify(windows);
                var html = '<div class="pw-editor">';
                if (windows.length === 0) {
                    html += '<div class="pw-empty">Nenhuma janela configurada. Clique em "Adicionar janela" para criar.</div>';
                }
                for (var i = 0; i < windows.length; i++) {
                    var tol = windows[i].tolerance_minutes || 0;
                    html += '<div class="pw-row" data-idx="' + i + '">'
                        + '<input type="text" placeholder="Nome (ex: Matutino)" value="' + esc(windows[i].name) + '" data-field="name" />'
                        + '<input type="time" value="' + esc(windows[i].start) + '" data-field="start" title="Início" />'
                        + '<span style="color:var(--muted);font-weight:700;">—</span>'
                        + '<input type="time" value="' + esc(windows[i].end) + '" data-field="end" title="Fim" />'
                        + '<span class="pw-lbl">&plusmn;</span>'
                        + '<input type="number" min="0" max="120" value="' + tol + '" data-field="tolerance_minutes" title="Tolerância em minutos" />'
                        + '<span class="pw-lbl">min</span>'
                        + '<button type="button" class="pw-rm" data-rm="' + i + '" title="Remover">&times;</button>'
                        + '</div>';
                }
                html += '<button type="button" class="pw-add" id="pw-add-btn">+ Adicionar janela</button>';
                html += '</div>';
                container.innerHTML = html;
                bind();
            }

            function bind() {
                container.querySelectorAll('.pw-row input').forEach(function (inp) {
                    inp.addEventListener('input', function () {
                        var idx = parseInt(this.closest('.pw-row').dataset.idx);
                        var field = this.dataset.field;
                        var val = this.value;
                        if (field === 'tolerance_minutes') { val = parseInt(val) || 0; }
                        windows[idx][field] = val;
                        hidden.value = JSON.stringify(windows);
                    });
                });
                container.querySelectorAll('.pw-rm').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        windows.splice(parseInt(this.dataset.rm), 1);
                        render();
                    });
                });
                var addBtn = document.getElementById('pw-add-btn');
                if (addBtn) {
                    addBtn.addEventListener('click', function () {
                        windows.push({ name: '', start: '07:00', end: '09:30', tolerance_minutes: 15 });
                        render();
                    });
                }
            }

            function esc(s) { return (s || '').replace(/"/g, '&quot;').replace(/</g, '&lt;'); }

            render();
        })();
        </script>
    </body>
</html>
