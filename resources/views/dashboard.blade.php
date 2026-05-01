<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Dashboard • {{ config('app.name', 'Bridge ERP') }}</title>

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
            .bridge-container { max-width: 1240px; }

            .dash-grid { display: grid; gap: 16px; }
            @media (min-width: 1040px) { .dash-grid { grid-template-columns: 1.35fr 0.65fr; align-items: start; } }

            .dash-card { border: 1px solid var(--border); border-radius: 18px; background: var(--surface-1); padding: 16px; }
            .dash-card--accent {
                background:
                    radial-gradient(1100px 420px at 10% 0%, color-mix(in srgb, var(--accent-a) 16%, transparent), transparent 60%),
                    radial-gradient(900px 380px at 100% 20%, color-mix(in srgb, var(--accent-c) 10%, transparent), transparent 60%),
                    var(--surface-1);
            }

            .dash-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
            .dash-head__left { display: flex; align-items: center; gap: 10px; min-width: 0; }
            .dash-ico {
                width: 34px; height: 34px; border-radius: 12px;
                display: grid; place-items: center;
                border: 1px solid var(--border);
                background: var(--surface-2);
                color: var(--text);
                flex: 0 0 auto;
            }
            .dash-ico svg { width: 18px; height: 18px; }
            .dash-badge { display: inline-flex; align-items: center; gap: 8px; padding: 4px 10px; border-radius: 999px; border: 1px solid var(--border); background: var(--surface-2); color: var(--muted); font-size: 12px; white-space: nowrap; }
            .dash-dot { width: 8px; height: 8px; border-radius: 999px; background: var(--accent-a); box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent-a) 18%, transparent); }

            .dash-flow { display: grid; gap: 12px; }
            .dash-node { border: 1px solid var(--border); border-radius: 16px; padding: 14px; background: var(--surface-2); }
            .dash-node__k { font-size: 12px; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); }
            .dash-node__t { font-size: 14px; font-weight: 600; margin-top: 4px; }
            .dash-node__d { margin-top: 6px; color: var(--muted); font-size: 13px; line-height: 1.35; }
            .dash-node__meta { margin-top: 10px; display: flex; flex-wrap: wrap; gap: 8px; }
            .dash-chip { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; border: 1px solid var(--border); background: color-mix(in oklab, var(--surface-1) 70%, transparent); font-size: 12px; color: var(--muted); }
            .dash-chip svg { width: 14px; height: 14px; }

            .dash-quick { display: flex; flex-direction: column; gap: 14px; margin-top: 4px; }
            .dash-quick__group {
                position: relative;
                border: 1px solid var(--border);
                border-radius: 16px;
                padding: 12px 12px 12px 16px;
                background: color-mix(in srgb, var(--surface-2) 92%, var(--surface-1));
            }
            .dash-quick__group::before {
                content: "";
                position: absolute;
                left: 0;
                top: 10px;
                bottom: 10px;
                width: 3px;
                border-radius: 0 4px 4px 0;
                background: var(--dash-group-line, var(--accent-a));
            }
            .dash-quick__group--hub { --dash-group-line: var(--accent-a); }
            .dash-quick__group--connect { --dash-group-line: var(--accent-c); }
            .dash-quick__group--audit { --dash-group-line: #d97706; }
            html.dark .dash-quick__group--audit { --dash-group-line: #fbbf24; }

            .dash-quick__label {
                margin: 0 0 10px 2px;
                font-size: 11px;
                font-weight: 750;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                color: var(--muted);
            }
            .dash-quick__btns { display: flex; flex-direction: column; gap: 8px; }

            .dash-qbtn.bridge-btn {
                justify-content: flex-start;
                white-space: nowrap;
                transition: background 0.14s ease, border-color 0.14s ease, box-shadow 0.14s ease, transform 0.08s ease;
            }
            .dash-qbtn:hover { text-decoration: none; }
            .dash-qbtn:active { transform: translateY(1px); }
            .dash-qbtn svg { width: 18px; height: 18px; flex-shrink: 0; }

            .dash-qbtn--hub {
                border-color: color-mix(in srgb, var(--accent-a) 42%, var(--border));
                background: color-mix(in srgb, var(--accent-a) 11%, var(--surface-1));
                color: color-mix(in srgb, var(--text) 88%, var(--accent-a));
                box-shadow: 0 0 0 1px color-mix(in srgb, var(--accent-a) 6%, transparent);
            }
            .dash-qbtn--hub:hover {
                background: color-mix(in srgb, var(--accent-a) 18%, var(--surface-1));
                border-color: color-mix(in srgb, var(--accent-a) 55%, var(--border));
            }

            .dash-qbtn--ieducar {
                border-color: color-mix(in srgb, #166534 45%, var(--border));
                background: color-mix(in srgb, #166534 10%, var(--surface-1));
                color: color-mix(in srgb, var(--text) 82%, #166534);
                box-shadow: 0 0 0 1px color-mix(in srgb, #166534 8%, transparent);
            }
            .dash-qbtn--ieducar:hover {
                background: color-mix(in srgb, #166534 16%, var(--surface-1));
                border-color: color-mix(in srgb, #166534 58%, var(--border));
            }

            .dash-qbtn--gestor {
                border-color: color-mix(in srgb, #4f46e5 42%, var(--border));
                background: color-mix(in srgb, #4f46e5 10%, var(--surface-1));
                color: color-mix(in srgb, var(--text) 85%, #4f46e5);
                box-shadow: 0 0 0 1px color-mix(in srgb, #4f46e5 7%, transparent);
            }
            .dash-qbtn--gestor:hover {
                background: color-mix(in srgb, #4f46e5 16%, var(--surface-1));
                border-color: color-mix(in srgb, #4f46e5 55%, var(--border));
            }

            .dash-qbtn--sms {
                border-color: color-mix(in srgb, #0284c7 42%, var(--border));
                background: color-mix(in srgb, #0284c7 10%, var(--surface-1));
                color: color-mix(in srgb, var(--text) 85%, #0284c7);
                box-shadow: 0 0 0 1px color-mix(in srgb, #0284c7 7%, transparent);
            }
            .dash-qbtn--sms:hover {
                background: color-mix(in srgb, #0284c7 16%, var(--surface-1));
                border-color: color-mix(in srgb, #0284c7 55%, var(--border));
            }

            .dash-qbtn--facial {
                border-color: color-mix(in srgb, #c2410c 38%, var(--border));
                background: color-mix(in srgb, #c2410c 9%, var(--surface-1));
                color: color-mix(in srgb, var(--text) 88%, #c2410c);
                box-shadow: 0 0 0 1px color-mix(in srgb, #c2410c 6%, transparent);
            }
            .dash-qbtn--facial:hover {
                background: color-mix(in srgb, #c2410c 14%, var(--surface-1));
                border-color: color-mix(in srgb, #c2410c 50%, var(--border));
            }

            .dash-qbtn--smslog {
                border-color: color-mix(in srgb, #0d9488 40%, var(--border));
                background: color-mix(in srgb, #0d9488 9%, var(--surface-1));
                color: color-mix(in srgb, var(--text) 86%, #0d9488);
                box-shadow: 0 0 0 1px color-mix(in srgb, #0d9488 6%, transparent);
            }
            .dash-qbtn--smslog:hover {
                background: color-mix(in srgb, #0d9488 15%, var(--surface-1));
                border-color: color-mix(in srgb, #0d9488 52%, var(--border));
            }
        </style>
    </head>
    <body>
        <div class="bridge-shell">
            <header class="bridge-header">
                <div class="bridge-container">
                    <div class="bridge-header__inner">
                        <a class="bridge-brand" href="/">
                            <img src="/favicon.svg" alt="" class="bridge-brand__logo" />
                            <div class="bridge-brand__text">
                                <div class="bridge-brand__name">{{ config('app.name', 'Bridge ERP') }}</div>
                                <div class="bridge-brand__tagline">Bridge de integração entre ERPs</div>
                            </div>
                        </a>

                        <div class="bridge-actions">
                            <button type="button" class="bridge-btn bridge-iconbtn" data-theme-toggle aria-pressed="false" title="Mudar tema">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 18a6 6 0 1 0 0-12 6 6 0 0 0 0 12Z" stroke="currentColor" stroke-width="2"/>
                                    <path d="M12 2v2M12 20v2M4 12H2M22 12h-2M5 5l1.5 1.5M17.5 17.5 19 19M19 5l-1.5 1.5M6.5 17.5 5 19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="bridge-btn">Sair</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="bridge-main">
                <div class="bridge-container">
                    <div class="bridge-auth">
                        <div class="bridge-panel">
                            <div class="bridge-panel__head">
                                <div class="bridge-panel__title">Dashboard</div>
                                <div class="bridge-panel__meta">área autenticada</div>
                            </div>

                            <div class="bridge-dashboard__body">
                                @if (session('status'))
                                    <p class="bridge-muted" style="margin-bottom: 12px;">
                                        <strong>{{ session('status') }}</strong>
                                    </p>
                                @endif

                                <p class="bridge-muted">
                                    Você está logado como <strong>{{ auth()->user()->username ?? auth()->user()->email }}</strong>.
                                </p>
                                <p class="bridge-muted" style="margin-top: 8px;">
                                    Este dashboard resume o <strong>fluxo ponta‑a‑ponta</strong> e dá acesso rápido às configurações e auditoria.
                                </p>

                                <div class="dash-grid" style="margin-top: 14px;">
                                    <section class="dash-card dash-card--accent">
                                        <div class="dash-head">
                                            <div class="dash-head__left">
                                                <div class="dash-ico" aria-hidden="true">
                                                    <svg viewBox="0 0 24 24" fill="none">
                                                        <path d="M7 7h10M7 12h10M7 17h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        <path d="M5 3h14a2 2 0 0 1 2 2v14l-4-2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                                    </svg>
                                                </div>
                                                <div style="min-width: 0;">
                                                    <div class="bridge-panel__title">Fluxo de dados</div>
                                                    <div class="bridge-panel__meta">iEducar ↔ GIDE ↔ Catraca</div>
                                                </div>
                                            </div>
                                            <div class="dash-badge" title="Visão geral do fluxo">
                                                <span class="dash-dot" aria-hidden="true"></span>
                                                ponta‑a‑ponta
                                            </div>
                                        </div>

                                        <div class="dash-flow" style="margin-top: 12px;">
                                            <div class="dash-node">
                                                <div class="dash-node__k">iEducar → GIDE</div>
                                                <div class="dash-node__t">Solicitações de facial + eventos/matrículas</div>
                                                <div class="dash-node__d">
                                                    O iEducar chama as APIs inbound do GIDE e recebe a <strong>URL com token</strong> para abrir a coleta.
                                                </div>
                                                <div class="dash-node__meta">
                                                    <span class="dash-chip">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M4 12h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                            <path d="M14 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                        inbound
                                                    </span>
                                                    <span class="dash-chip">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M12 2v4M12 18v4M4.9 4.9l2.8 2.8M16.3 16.3l2.8 2.8M2 12h4M18 12h4M4.9 19.1l2.8-2.8M16.3 7.7l2.8-2.8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        </svg>
                                                        HMAC/Bearer
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="dash-node">
                                                <div class="dash-node__k">GIDE → Catraca (Gestor)</div>
                                                <div class="dash-node__t">Coleta do rosto + envio para enroll</div>
                                                <div class="dash-node__d">
                                                    A tela de coleta captura a foto na hora e envia ao GIDE, que encaminha ao sistema da catraca.
                                                </div>
                                                <div class="dash-node__meta">
                                                    <span class="dash-chip">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M4 7h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                            <path d="M7 4v16M17 4v16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        </svg>
                                                        câmera
                                                    </span>
                                                    <span class="dash-chip">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M4 6h16v12H4z" stroke="currentColor" stroke-width="2"/>
                                                            <path d="M7 9h10M7 12h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        </svg>
                                                        enroll
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="dash-node">
                                                <div class="dash-node__k">GIDE → iEducar</div>
                                                <div class="dash-node__t">Confirmação + consulta de status da matrícula</div>
                                                <div class="dash-node__d">
                                                    Após o enroll, o GIDE confirma a coleta e permite consultar status (ano letivo/situação da matrícula).
                                                </div>
                                                <div class="dash-node__meta">
                                                    <span class="dash-chip">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                        confirmação
                                                    </span>
                                                    <span class="dash-chip">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M21 21l-4.2-4.2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                            <path d="M11 18a7 7 0 1 1 0-14 7 7 0 0 1 0 14Z" stroke="currentColor" stroke-width="2"/>
                                                        </svg>
                                                        consulta
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </section>

                                    <aside class="dash-card">
                                        <div class="dash-head">
                                            <div class="dash-head__left">
                                                <div class="dash-ico" aria-hidden="true">
                                                    <svg viewBox="0 0 24 24" fill="none">
                                                        <path d="M12 3v3M12 18v3M4.2 7.8l2.1 2.1M17.7 17.7l2.1 2.1M3 12h3M18 12h3M4.2 16.2l2.1-2.1M17.7 6.3l2.1-2.1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        <path d="M12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10Z" stroke="currentColor" stroke-width="2"/>
                                                    </svg>
                                                </div>
                                                <div style="min-width: 0;">
                                                    <div class="bridge-panel__title">Ações rápidas</div>
                                                    <div class="bridge-panel__meta">agrupado por tema</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="dash-quick">
                                            <div class="dash-quick__group dash-quick__group--hub">
                                                <div class="dash-quick__label">Ponte e monitoramento</div>
                                                <div class="dash-quick__btns">
                                                    <a class="bridge-btn dash-qbtn dash-qbtn--hub" href="{{ route('integrations.overview') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M4 19V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                            <path d="M7 14h3v5H7zM11 10h3v9h-3zM15 12h3v7h-3z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                                        </svg>
                                                        Visão geral das integrações
                                                    </a>
                                                </div>
                                            </div>

                                            <div class="dash-quick__group dash-quick__group--connect">
                                                <div class="dash-quick__label">Conectores e canais</div>
                                                <div class="dash-quick__btns">
                                                    <a class="bridge-btn dash-qbtn dash-qbtn--ieducar" href="{{ route('integrations.ieducar') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M4 4h16v6H4z" stroke="currentColor" stroke-width="2"/>
                                                            <path d="M4 14h16v6H4z" stroke="currentColor" stroke-width="2"/>
                                                            <path d="M8 7h8M8 17h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        </svg>
                                                        iEducar (ERP)
                                                    </a>
                                                    <a class="bridge-btn dash-qbtn dash-qbtn--gestor" href="{{ route('integrations.gestor') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M7 21h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                            <path d="M12 17a5 5 0 1 0-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                            <path d="M7 12v3a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                        Gestor / catraca
                                                    </a>
                                                    <a class="bridge-btn dash-qbtn dash-qbtn--sms" href="{{ route('integrations.sms') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M4 4h16v12H7l-3 3V4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                                            <path d="M7 8h10M7 12h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        </svg>
                                                        SMS (provedor)
                                                    </a>
                                                </div>
                                            </div>

                                            <div class="dash-quick__group dash-quick__group--audit">
                                                <div class="dash-quick__label">Auditoria e entregas</div>
                                                <div class="dash-quick__btns">
                                                    <a class="bridge-btn dash-qbtn dash-qbtn--facial" href="{{ route('admin.facial-requests.index') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M4 5h16v14H4z" stroke="currentColor" stroke-width="2"/>
                                                            <path d="M8 9h8M8 13h8M8 17h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        </svg>
                                                        Solicitações faciais (admin)
                                                    </a>
                                                    <a class="bridge-btn dash-qbtn dash-qbtn--smslog" href="{{ route('sms.index') }}">
                                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M6 3h12v18H6z" stroke="currentColor" stroke-width="2"/>
                                                            <path d="M9 7h6M9 11h6M9 15h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        </svg>
                                                        Histórico de envios SMS
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                        <p class="bridge-muted" style="margin-top: 12px;">
                                            A coleta facial <strong>não</strong> é iniciada por aqui — ela deve abrir somente via URL com token gerada pelo iEducar.
                                        </p>
                                    </aside>
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

