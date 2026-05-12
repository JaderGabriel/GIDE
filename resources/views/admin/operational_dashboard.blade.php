<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Operacional • {{ config('app.name', 'Bridge ERP') }}</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
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
            .op-grid { display: grid; gap: 14px; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); margin-top: 16px; }
            .op-card { border: 1px solid var(--border); border-radius: 16px; padding: 16px; background: var(--surface-1); position: relative; overflow: hidden; }
            .op-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; border-radius: 16px 16px 0 0; }
            .op-card--blue::before { background: #0284c7; }
            .op-card--green::before { background: #059669; }
            .op-card--amber::before { background: #d97706; }
            .op-card--purple::before { background: #7c3aed; }
            .op-card--red::before { background: #dc2626; }
            .op-card--cyan::before { background: #0891b2; }
            .op-card__label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); margin-bottom: 6px; }
            .op-card__value { font-size: 28px; font-weight: 800; line-height: 1.1; letter-spacing: -0.02em; }
            .op-card__sub { font-size: 12px; color: var(--muted); margin-top: 4px; line-height: 1.4; }

            .op-section { margin-top: 28px; }
            .op-section__title { font-size: 16px; font-weight: 800; margin: 0 0 4px; }
            .op-section__desc { font-size: 13px; color: var(--muted); margin: 0 0 12px; line-height: 1.45; }

            .op-chart-wrap { margin-top: 14px; padding: 16px; border: 1px solid var(--border); border-radius: 16px; background: var(--surface-1); }
            .op-chart { display: flex; align-items: flex-end; gap: 3px; height: 120px; }
            .op-bar { flex: 1; min-width: 0; border-radius: 4px 4px 0 0; background: color-mix(in srgb, var(--accent-a) 65%, var(--surface-2)); position: relative; transition: background .15s; }
            .op-bar:hover { background: var(--accent-a); }
            .op-bar__tip { display: none; position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); margin-bottom: 4px; padding: 3px 8px; border-radius: 6px; background: var(--text); color: var(--bg0); font-size: 10px; font-weight: 700; white-space: nowrap; }
            .op-bar:hover .op-bar__tip { display: block; }
            .op-chart-labels { display: flex; gap: 3px; margin-top: 6px; }
            .op-chart-labels span { flex: 1; text-align: center; font-size: 9px; color: var(--muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

            .op-dist { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 10px; }
            .op-dist__item { display: flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface-1); font-size: 13px; }
            .op-dist__dot { width: 10px; height: 10px; border-radius: 50%; }
            .op-dist__dot--completed { background: #059669; }
            .op-dist__dot--failed { background: #dc2626; }
            .op-dist__dot--skipped { background: #d97706; }
            .op-dist__dot--pending { background: #0284c7; }
            .op-dist__dot--processing { background: #7c3aed; }
            .op-dist__dot--gestor_hmac { background: #0284c7; }
            .op-dist__dot--catraca_bearer { background: #059669; }

            .op-queue-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 10px; }
            .op-queue-table th { text-align: left; font-weight: 700; padding: 8px 10px; border-bottom: 2px solid var(--border); font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); }
            .op-queue-table td { padding: 8px 10px; border-bottom: 1px solid color-mix(in srgb, var(--border) 50%, transparent); }
            .op-queue-table tr:last-child td { border-bottom: none; }

            .op-health { display: flex; gap: 10px; align-items: center; margin-top: 12px; }
            .op-health__indicator { width: 12px; height: 12px; border-radius: 50%; }
            .op-health__indicator--ok { background: #059669; box-shadow: 0 0 0 3px color-mix(in srgb, #059669 20%, transparent); }
            .op-health__indicator--warn { background: #d97706; box-shadow: 0 0 0 3px color-mix(in srgb, #d97706 20%, transparent); }
            .op-health__indicator--bad { background: #dc2626; box-shadow: 0 0 0 3px color-mix(in srgb, #dc2626 20%, transparent); }
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
                                <div class="bridge-brand__tagline">Dashboard Operacional</div>
                            </div>
                        </a>
                        @include('partials.bridge-user-menu')
                    </div>
                </div>
            </header>

            <main class="bridge-main">
                <div class="bridge-container" style="max-width: 1280px;">

                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                        <div>
                            <h1 style="font-size: 22px; font-weight: 800; margin: 0;">Dashboard Operacional</h1>
                            <p style="font-size: 14px; color: var(--muted); margin: 4px 0 0;">Visão consolidada de fluxo de dados, filas, entregas e saúde das integrações.</p>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <a class="bridge-btn" href="{{ url('/dashboard') }}">Dashboard principal</a>
                            <a class="bridge-btn" href="{{ route('admin.gestor-access-events.index') }}">Access-events</a>
                        </div>
                    </div>

                    {{-- ═══ Saúde geral ═══ --}}
                    @php
                        $healthOk = ($accessEvents['pending'] + $accessEvents['processing']) < 20 && $accessEvents['failed'] < 5;
                        $healthWarn = ! $healthOk && $accessEvents['failed'] < 20;
                    @endphp
                    <div class="op-health">
                        <div class="op-health__indicator {{ $healthOk ? 'op-health__indicator--ok' : ($healthWarn ? 'op-health__indicator--warn' : 'op-health__indicator--bad') }}"></div>
                        <span style="font-size: 14px; font-weight: 600;">
                            @if ($healthOk)
                                Sistema operando normalmente
                            @elseif ($healthWarn)
                                Atenção: {{ $accessEvents['pending'] + $accessEvents['processing'] }} eventos na fila, {{ $accessEvents['failed'] }} falhas
                            @else
                                Alerta: {{ $accessEvents['failed'] }} falhas detectadas — verificar filas
                            @endif
                        </span>
                    </div>

                    {{-- ═══ KPIs de Eventos de Acesso ═══ --}}
                    <div class="op-section">
                        <div class="op-section__title">Eventos de acesso (catraca → GIDE)</div>
                        <div class="op-section__desc">Cada evento representa um POST da catraca ou do Gestor. O motor de presença analisa e decide se marca frequência no iEducar.</div>
                    </div>

                    <div class="op-grid">
                        <div class="op-card op-card--blue">
                            <div class="op-card__label">Hoje</div>
                            <div class="op-card__value">{{ number_format($accessEvents['today']) }}</div>
                            <div class="op-card__sub">eventos recebidos</div>
                        </div>
                        <div class="op-card op-card--blue">
                            <div class="op-card__label">Últimos 7 dias</div>
                            <div class="op-card__value">{{ number_format($accessEvents['last_7d']) }}</div>
                            <div class="op-card__sub">total de events</div>
                        </div>
                        <div class="op-card op-card--green">
                            <div class="op-card__label">Presença marcada (hoje)</div>
                            <div class="op-card__value">{{ number_format($accessEvents['mark_presence_today']) }}</div>
                            <div class="op-card__sub">action=mark_presence</div>
                        </div>
                        <div class="op-card op-card--amber">
                            <div class="op-card__label">Na fila</div>
                            <div class="op-card__value">{{ number_format($accessEvents['pending'] + $accessEvents['processing']) }}</div>
                            <div class="op-card__sub">pending + processing</div>
                        </div>
                        <div class="op-card op-card--red">
                            <div class="op-card__label">Falhas</div>
                            <div class="op-card__value">{{ number_format($accessEvents['failed']) }}</div>
                            <div class="op-card__sub">total acumulado</div>
                        </div>
                        <div class="op-card op-card--cyan">
                            <div class="op-card__label">Total geral</div>
                            <div class="op-card__value">{{ number_format($accessEvents['total']) }}</div>
                            <div class="op-card__sub">desde o início</div>
                        </div>
                    </div>

                    {{-- ═══ Gráfico de volume diário ═══ --}}
                    <div class="op-chart-wrap">
                        <div style="font-weight: 700; font-size: 14px; margin-bottom: 10px;">Volume diário de eventos (últimos 14 dias)</div>
                        @php
                            $maxCount = max(array_column($dailyChart, 'count'), 1);
                        @endphp
                        <div class="op-chart">
                            @foreach ($dailyChart as $day)
                                @php $h = $maxCount > 0 ? max(2, ($day['count'] / $maxCount) * 100) : 2; @endphp
                                <div class="op-bar" style="height: {{ $h }}%;">
                                    <div class="op-bar__tip">{{ $day['count'] }} — {{ \Carbon\Carbon::parse($day['date'])->format('d/m') }}</div>
                                </div>
                            @endforeach
                        </div>
                        <div class="op-chart-labels">
                            @foreach ($dailyChart as $day)
                                <span>{{ \Carbon\Carbon::parse($day['date'])->format('d') }}</span>
                            @endforeach
                        </div>
                    </div>

                    {{-- ═══ Distribuição por status e canal ═══ --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 14px;">
                        <div class="op-chart-wrap">
                            <div style="font-weight: 700; font-size: 14px; margin-bottom: 6px;">Distribuição por status</div>
                            <div class="op-dist">
                                @foreach ($statusDistribution as $status => $count)
                                    <div class="op-dist__item">
                                        <div class="op-dist__dot op-dist__dot--{{ $status }}"></div>
                                        <span style="font-weight: 700;">{{ $count }}</span>
                                        <span style="color: var(--muted);">{{ $status }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="op-chart-wrap">
                            <div style="font-weight: 700; font-size: 14px; margin-bottom: 6px;">Distribuição por canal</div>
                            <div class="op-dist">
                                @foreach ($channelDistribution as $channel => $count)
                                    <div class="op-dist__item">
                                        <div class="op-dist__dot op-dist__dot--{{ $channel }}"></div>
                                        <span style="font-weight: 700;">{{ $count }}</span>
                                        <span style="color: var(--muted);">{{ $channel }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- ═══ Filas e entregas ═══ --}}
                    <div class="op-section">
                        <div class="op-section__title">Filas e entregas</div>
                        <div class="op-section__desc">Estado atual das filas outbound (Gestor, SMS, frequência iEducar). Itens em retry ou pendentes podem indicar problemas de conectividade.</div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 14px;">
                        {{-- SMS --}}
                        <div class="op-chart-wrap">
                            <div style="font-weight: 700; font-size: 14px; margin-bottom: 6px;">SMS</div>
                            <table class="op-queue-table">
                                <tr><td>Total</td><td style="text-align:right; font-weight:700;">{{ $sms['total'] }}</td></tr>
                                <tr><td>Hoje</td><td style="text-align:right; font-weight:700;">{{ $sms['today'] }}</td></tr>
                                <tr><td>Enviados (sent)</td><td style="text-align:right; font-weight:700; color:#059669;">{{ $sms['sent'] }}</td></tr>
                                <tr><td>Falhas</td><td style="text-align:right; font-weight:700; color:#dc2626;">{{ $sms['failed'] }}</td></tr>
                                <tr><td>Na fila</td><td style="text-align:right; font-weight:700; color:#0284c7;">{{ $sms['pending'] }}</td></tr>
                            </table>
                        </div>

                        {{-- Outbound Gestor --}}
                        <div class="op-chart-wrap">
                            <div style="font-weight: 700; font-size: 14px; margin-bottom: 6px;">Outbound (matrícula → Gestor)</div>
                            <table class="op-queue-table">
                                <tr><td>Total</td><td style="text-align:right; font-weight:700;">{{ $outbound['total'] }}</td></tr>
                                <tr><td>Hoje</td><td style="text-align:right; font-weight:700;">{{ $outbound['today'] }}</td></tr>
                                <tr><td>Entregues</td><td style="text-align:right; font-weight:700; color:#059669;">{{ $outbound['delivered'] }}</td></tr>
                                <tr><td>Falhas</td><td style="text-align:right; font-weight:700; color:#dc2626;">{{ $outbound['failed'] }}</td></tr>
                                <tr><td>Retry agendado</td><td style="text-align:right; font-weight:700; color:#d97706;">{{ $outbound['retry_scheduled'] }}</td></tr>
                            </table>
                        </div>

                        {{-- Frequência iEducar --}}
                        <div class="op-chart-wrap">
                            <div style="font-weight: 700; font-size: 14px; margin-bottom: 6px;">Frequência iEducar (registro)</div>
                            <table class="op-queue-table">
                                <tr><td>Total</td><td style="text-align:right; font-weight:700;">{{ $frequencia['total'] }}</td></tr>
                                <tr><td>Hoje</td><td style="text-align:right; font-weight:700;">{{ $frequencia['today'] }}</td></tr>
                                <tr><td>Concluídos</td><td style="text-align:right; font-weight:700; color:#059669;">{{ $frequencia['completed'] }}</td></tr>
                                <tr><td>Falhas</td><td style="text-align:right; font-weight:700; color:#dc2626;">{{ $frequencia['failed'] }}</td></tr>
                            </table>
                        </div>
                    </div>

                    {{-- ═══ Facial + Enrichment ═══ --}}
                    <div class="op-section">
                        <div class="op-section__title">Coleta facial e enriquecimento</div>
                        <div class="op-section__desc">Solicitações de captura facial (tokens gerados pelo iEducar) e cache de dados de alunos.</div>
                    </div>

                    <div class="op-grid">
                        <div class="op-card op-card--purple">
                            <div class="op-card__label">Solicitações faciais</div>
                            <div class="op-card__value">{{ number_format($facial['total']) }}</div>
                            <div class="op-card__sub">{{ $facial['used'] }} usadas · {{ $facial['expired'] }} expiradas</div>
                        </div>
                        <div class="op-card op-card--purple">
                            <div class="op-card__label">Faciais (7d)</div>
                            <div class="op-card__value">{{ number_format($facial['last_7d']) }}</div>
                            <div class="op-card__sub">últimos 7 dias</div>
                        </div>
                        <div class="op-card op-card--cyan">
                            <div class="op-card__label">Alunos cacheados</div>
                            <div class="op-card__value">{{ number_format($enrichment['cached_students']) }}</div>
                            <div class="op-card__sub">{{ $enrichment['fresh'] }} frescos · {{ $enrichment['expired'] }} expirados</div>
                        </div>
                    </div>

                    {{-- ═══ Detalhes técnicos ═══ --}}
                    <div class="op-section">
                        <div class="op-section__title">Detalhes técnicos</div>
                        <div class="op-section__desc">Informações do sistema, middleware ativo, configurações relevantes para diagnóstico.</div>
                    </div>

                    <div class="op-chart-wrap" style="font-size: 13px; line-height: 1.6;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px 24px;">
                            <div><span style="color: var(--muted); font-weight: 600;">Laravel</span> v{{ app()->version() }}</div>
                            <div><span style="color: var(--muted); font-weight: 600;">PHP</span> {{ PHP_VERSION }}</div>
                            <div><span style="color: var(--muted); font-weight: 600;">Timezone</span> {{ config('app.timezone', 'UTC') }}</div>
                            <div><span style="color: var(--muted); font-weight: 600;">Queue driver</span> {{ config('queue.default', 'sync') }}</div>
                            <div><span style="color: var(--muted); font-weight: 600;">DB</span> {{ config('database.default', '?') }}</div>
                            <div><span style="color: var(--muted); font-weight: 600;">Request ID</span> middleware AssignRequestId (API)</div>
                            <div><span style="color: var(--muted); font-weight: 600;">Motor presença</span> PresenceRuleEngine (4 modos)</div>
                            <div><span style="color: var(--muted); font-weight: 600;">Enrichment TTL</span> 24h (student_enrichment_cache)</div>
                        </div>
                    </div>

                    <div style="margin-top: 20px; text-align: center;">
                        <a class="bridge-btn" href="{{ url('/dashboard') }}">Voltar ao dashboard</a>
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
    </body>
</html>
