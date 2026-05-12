<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Operacional • {{ config('app.name', 'Bridge ERP') }}</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
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
        <link rel="stylesheet" href="/operational-dashboard.css">
        <script defer src="/home.js"></script>
        <script defer src="/operational-dashboard.js"></script>
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
                <div class="od-container">

                    {{-- ═══ Hero ═══ --}}
                    <div class="od-hero">
                        <div>
                            <h1 class="od-hero__title">Dashboard Operacional</h1>
                            <p class="od-hero__sub">Monitoramento em tempo real de fluxo de dados, filas, entregas e saúde das integrações GIDE.</p>
                        </div>
                        <div class="od-hero__actions">
                            <a class="bridge-btn" href="{{ url('/dashboard') }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                                Dashboard
                            </a>
                            <a class="bridge-btn" href="{{ route('admin.gestor-access-events.index') }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                Access-events
                            </a>
                        </div>
                    </div>

                    {{-- ═══ Medidor de saúde ═══ --}}
                    @php
                        $queueSize = $accessEvents['pending'] + $accessEvents['processing'];
                        $gScore = $health['global'];
                        $gStatus = $health['status'];
                        $arcLen = 188.5; // half-circle for r=60
                        $arcOffset = $arcLen - ($arcLen * $gScore / 100);
                    @endphp
                    <div class="od-meter">
                        <div class="od-meter__top">
                            <div class="od-gauge">
                                <svg class="od-gauge__svg" viewBox="0 0 160 90">
                                    <path class="od-gauge__bg" d="M 16 80 A 60 60 0 0 1 144 80" />
                                    <path class="od-gauge__fill od-gauge__fill--{{ $gStatus }}" d="M 16 80 A 60 60 0 0 1 144 80"
                                        stroke-dasharray="{{ $arcLen }}"
                                        stroke-dashoffset="{{ $arcOffset }}" />
                                </svg>
                                <div class="od-gauge__value od-gauge__value--{{ $gStatus }}">{{ $gScore }}</div>
                                <div class="od-gauge__label">Health Score</div>
                            </div>
                            <div class="od-meter__summary">
                                <div class="od-meter__title">
                                    @if ($gStatus === 'ok')
                                        Sistema saudável
                                    @elseif ($gStatus === 'warn')
                                        Atenção necessária
                                    @else
                                        Estado crítico
                                    @endif
                                </div>
                                <p class="od-meter__desc">
                                    @if ($gStatus === 'ok')
                                        Todos os subsistemas operam dentro dos parâmetros normais. Filas processando, sem acúmulo relevante de falhas.
                                    @elseif ($gStatus === 'warn')
                                        Um ou mais subsistemas apresentam falhas ou filas acima do esperado. Verifique os detalhes abaixo e monitore a evolução.
                                    @else
                                        Falhas significativas detectadas em subsistemas críticos. Ação imediata recomendada — verifique conectividade, workers e logs.
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="od-subsystems">
                            @foreach ($health['subsystems'] as $sub)
                                <div class="od-sub">
                                    <div class="od-sub__score od-sub__score--{{ $sub['status'] }}">{{ $sub['score'] }}</div>
                                    <div class="od-sub__info">
                                        <div class="od-sub__name">{{ $sub['name'] }}</div>
                                        <div class="od-sub__detail">{{ $sub['detail'] }}</div>
                                        <div class="od-sub__bar-track">
                                            <div class="od-sub__bar-fill od-sub__bar-fill--{{ $sub['status'] }}" style="width: {{ $sub['score'] }}%;"></div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- ═══ Eventos de acesso ═══ --}}
                    <div class="od-section">
                        <div class="od-section__icon od-section__icon--blue">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        </div>
                        <div class="od-section__text">
                            <div class="od-section__title">Eventos de acesso</div>
                            <div class="od-section__desc">POST da catraca/Gestor → motor de presença → marcação no iEducar</div>
                        </div>
                    </div>

                    <div class="od-kpi-grid">
                        {{-- Hoje --}}
                        <div class="od-kpi">
                            <div class="od-kpi__top">
                                <div class="od-kpi__label">Hoje</div>
                                <div class="od-kpi__icon od-kpi__icon--blue">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                </div>
                            </div>
                            <div class="od-kpi__value">{{ number_format($accessEvents['today']) }}</div>
                            <div class="od-kpi__sub">eventos recebidos</div>
                            <div class="od-kpi__bar od-kpi__bar--blue"></div>
                        </div>
                        {{-- 7 dias --}}
                        <div class="od-kpi">
                            <div class="od-kpi__top">
                                <div class="od-kpi__label">Últimos 7 dias</div>
                                <div class="od-kpi__icon od-kpi__icon--blue">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                                </div>
                            </div>
                            <div class="od-kpi__value">{{ number_format($accessEvents['last_7d']) }}</div>
                            <div class="od-kpi__sub">total acumulado</div>
                            <div class="od-kpi__bar od-kpi__bar--blue"></div>
                        </div>
                        {{-- Presença --}}
                        <div class="od-kpi">
                            <div class="od-kpi__top">
                                <div class="od-kpi__label">Presença (hoje)</div>
                                <div class="od-kpi__icon od-kpi__icon--green">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                </div>
                            </div>
                            <div class="od-kpi__value">{{ number_format($accessEvents['mark_presence_today']) }}</div>
                            <div class="od-kpi__sub">mark_presence</div>
                            <div class="od-kpi__bar od-kpi__bar--green"></div>
                        </div>
                        {{-- Fila --}}
                        <div class="od-kpi">
                            <div class="od-kpi__top">
                                <div class="od-kpi__label">Na fila</div>
                                <div class="od-kpi__icon od-kpi__icon--amber">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                </div>
                            </div>
                            <div class="od-kpi__value">{{ number_format($queueSize) }}</div>
                            <div class="od-kpi__sub">pending + processing</div>
                            <div class="od-kpi__bar od-kpi__bar--amber"></div>
                        </div>
                        {{-- Falhas --}}
                        <div class="od-kpi">
                            <div class="od-kpi__top">
                                <div class="od-kpi__label">Falhas</div>
                                <div class="od-kpi__icon od-kpi__icon--red">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                </div>
                            </div>
                            <div class="od-kpi__value">{{ number_format($accessEvents['failed']) }}</div>
                            <div class="od-kpi__sub">total acumulado</div>
                            <div class="od-kpi__bar od-kpi__bar--red"></div>
                        </div>
                        {{-- Total --}}
                        <div class="od-kpi">
                            <div class="od-kpi__top">
                                <div class="od-kpi__label">Total geral</div>
                                <div class="od-kpi__icon od-kpi__icon--cyan">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                                </div>
                            </div>
                            <div class="od-kpi__value">{{ number_format($accessEvents['total']) }}</div>
                            <div class="od-kpi__sub">desde o início</div>
                            <div class="od-kpi__bar od-kpi__bar--cyan"></div>
                        </div>
                    </div>

                    {{-- ═══ Gráfico + Distribuição ═══ --}}
                    <div class="od-2col" style="margin-top: 16px;">
                        {{-- Gráfico --}}
                        <div class="od-panel">
                            <div class="od-panel__head">
                                <div class="od-panel__head-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                                </div>
                                <div class="od-panel__title">Volume diário (14 dias)</div>
                            </div>
                            @php
                                $counts = array_column($dailyChart, 'count');
                                $maxCount = max(1, ...$counts);
                            @endphp
                            <div class="od-chart">
                                @foreach ($dailyChart as $day)
                                    @php $h = max(3, ($day['count'] / $maxCount) * 100); @endphp
                                    <div class="od-bar" style="height: {{ $h }}%;">
                                        <div class="od-bar__tip">{{ $day['count'] }} — {{ \Carbon\Carbon::parse($day['date'])->format('d/m') }}</div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="od-chart-labels">
                                @foreach ($dailyChart as $day)
                                    <span>{{ \Carbon\Carbon::parse($day['date'])->format('d') }}</span>
                                @endforeach
                            </div>
                        </div>

                        {{-- Distribuição --}}
                        <div style="display: flex; flex-direction: column; gap: 14px;">
                            <div class="od-panel" style="flex: 1;">
                                <div class="od-panel__head">
                                    <div class="od-panel__head-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.21 15.89A10 10 0 118 2.83"/><path d="M22 12A10 10 0 0012 2v10z"/></svg>
                                    </div>
                                    <div class="od-panel__title">Por status</div>
                                </div>
                                <div class="od-chips">
                                    @foreach ($statusDistribution as $status => $count)
                                        <div class="od-chip">
                                            <div class="od-chip__dot od-dot--{{ $status }}"></div>
                                            <span class="od-chip__val">{{ number_format($count) }}</span>
                                            <span class="od-chip__lbl">{{ $status }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="od-panel" style="flex: 1;">
                                <div class="od-panel__head">
                                    <div class="od-panel__head-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5.52 19c.64-2.2 1.84-3 3.22-3h6.52c1.38 0 2.58.8 3.22 3"/><circle cx="12" cy="10" r="3"/><circle cx="12" cy="12" r="10"/></svg>
                                    </div>
                                    <div class="od-panel__title">Por canal</div>
                                </div>
                                <div class="od-chips">
                                    @foreach ($channelDistribution as $channel => $count)
                                        <div class="od-chip">
                                            <div class="od-chip__dot od-dot--{{ $channel }}"></div>
                                            <span class="od-chip__val">{{ number_format($count) }}</span>
                                            <span class="od-chip__lbl">{{ str_replace('_', ' ', $channel) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ═══ Filas e entregas ═══ --}}
                    <div class="od-section">
                        <div class="od-section__icon od-section__icon--amber">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                        </div>
                        <div class="od-section__text">
                            <div class="od-section__title">Filas e entregas</div>
                            <div class="od-section__desc">Estado atual das filas outbound — itens em retry ou pendentes podem indicar problemas</div>
                        </div>
                    </div>

                    <div class="od-3col">
                        {{-- SMS --}}
                        <div class="od-queue">
                            <div class="od-queue__head">
                                <div class="od-queue__head-icon od-queue__head-icon--sms">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                                </div>
                                <div class="od-queue__title">SMS</div>
                            </div>
                            <div class="od-queue__body">
                                <div class="od-queue__row">
                                    <span class="od-queue__row-label">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                                        Total
                                    </span>
                                    <span class="od-queue__row-val">{{ number_format($sms['total']) }}</span>
                                </div>
                                <div class="od-queue__row">
                                    <span class="od-queue__row-label">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                        Hoje
                                    </span>
                                    <span class="od-queue__row-val">{{ number_format($sms['today']) }}</span>
                                </div>
                                <div class="od-queue__row">
                                    <span class="od-queue__row-label">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                        Enviados
                                    </span>
                                    <span class="od-queue__row-val od-queue__row-val--ok">{{ number_format($sms['sent']) }}</span>
                                </div>
                                <div class="od-queue__row">
                                    <span class="od-queue__row-label">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                        Falhas
                                    </span>
                                    <span class="od-queue__row-val od-queue__row-val--fail">{{ number_format($sms['failed']) }}</span>
                                </div>
                                <div class="od-queue__row">
                                    <span class="od-queue__row-label">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        Na fila
                                    </span>
                                    <span class="od-queue__row-val od-queue__row-val--info">{{ number_format($sms['pending']) }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Outbound --}}
                        <div class="od-queue">
                            <div class="od-queue__head">
                                <div class="od-queue__head-icon od-queue__head-icon--outbound">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                                </div>
                                <div class="od-queue__title">Outbound (matrícula → Gestor)</div>
                            </div>
                            <div class="od-queue__body">
                                <div class="od-queue__row">
                                    <span class="od-queue__row-label">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                                        Total
                                    </span>
                                    <span class="od-queue__row-val">{{ number_format($outbound['total']) }}</span>
                                </div>
                                <div class="od-queue__row">
                                    <span class="od-queue__row-label">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                        Hoje
                                    </span>
                                    <span class="od-queue__row-val">{{ number_format($outbound['today']) }}</span>
                                </div>
                                <div class="od-queue__row">
                                    <span class="od-queue__row-label">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                        Entregues
                                    </span>
                                    <span class="od-queue__row-val od-queue__row-val--ok">{{ number_format($outbound['delivered']) }}</span>
                                </div>
                                <div class="od-queue__row">
                                    <span class="od-queue__row-label">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                        Falhas
                                    </span>
                                    <span class="od-queue__row-val od-queue__row-val--fail">{{ number_format($outbound['failed']) }}</span>
                                </div>
                                <div class="od-queue__row">
                                    <span class="od-queue__row-label">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
                                        Retry agendado
                                    </span>
                                    <span class="od-queue__row-val od-queue__row-val--warn">{{ number_format($outbound['retry_scheduled']) }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Frequência --}}
                        <div class="od-queue">
                            <div class="od-queue__head">
                                <div class="od-queue__head-icon od-queue__head-icon--freq">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>
                                </div>
                                <div class="od-queue__title">Frequência iEducar</div>
                            </div>
                            <div class="od-queue__body">
                                <div class="od-queue__row">
                                    <span class="od-queue__row-label">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                                        Total
                                    </span>
                                    <span class="od-queue__row-val">{{ number_format($frequencia['total']) }}</span>
                                </div>
                                <div class="od-queue__row">
                                    <span class="od-queue__row-label">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                        Hoje
                                    </span>
                                    <span class="od-queue__row-val">{{ number_format($frequencia['today']) }}</span>
                                </div>
                                <div class="od-queue__row">
                                    <span class="od-queue__row-label">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                        Concluídos
                                    </span>
                                    <span class="od-queue__row-val od-queue__row-val--ok">{{ number_format($frequencia['completed']) }}</span>
                                </div>
                                <div class="od-queue__row">
                                    <span class="od-queue__row-label">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                        Falhas
                                    </span>
                                    <span class="od-queue__row-val od-queue__row-val--fail">{{ number_format($frequencia['failed']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ═══ Filas em tempo real ═══ --}}
                    <div class="od-section">
                        <div class="od-section__icon od-section__icon--green">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        </div>
                        <div class="od-section__text">
                            <div class="od-section__title">Filas em tempo real</div>
                            <div class="od-section__desc">Monitoramento ao vivo com atualização a cada 10 segundos</div>
                        </div>
                    </div>

                    <div class="od-live" id="od-live" data-poll-url="{{ route('admin.operational-dashboard.queue-live') }}">
                        <div class="od-live__header">
                            <div class="od-live__header-left">
                                <div class="od-live__header-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                                </div>
                                <div class="od-live__header-text">
                                    <div class="od-live__header-title">Monitor de filas</div>
                                    <div class="od-live__header-sub">Atualizado às <span id="od-live-ts">--:--:--</span></div>
                                </div>
                            </div>
                            <div class="od-live__badge od-live__badge--live">
                                <div class="od-live__badge-dot"></div>
                                LIVE
                            </div>
                        </div>

                        <div class="od-live__totals">
                            <div class="od-live__total">
                                <div class="od-live__total-val od-live__total-val--pending" id="od-total-pending">-</div>
                                <div class="od-live__total-label">Na fila</div>
                            </div>
                            <div class="od-live__total">
                                <div class="od-live__total-val od-live__total-val--failed" id="od-total-failed">-</div>
                                <div class="od-live__total-label">Falhas acumuladas</div>
                            </div>
                            <div class="od-live__total">
                                <div class="od-live__total-val od-live__total-val--throughput" id="od-total-throughput">-</div>
                                <div class="od-live__total-label">Processados (5 min)</div>
                            </div>
                            <div class="od-live__total">
                                <div class="od-live__total-val od-live__total-val--ingress" id="od-total-ingress">-</div>
                                <div class="od-live__total-label">Entrada (5 min)</div>
                            </div>
                        </div>

                        <div class="od-live__rows">
                            <div class="od-live__row od-live__row--head">
                                <span>Fila</span>
                                <span style="text-align:center;">Pendentes</span>
                                <span style="text-align:center;">Falhas</span>
                                <span style="text-align:center;">Entrada (5m)</span>
                                <span style="text-align:center;">Processados (5m)</span>
                            </div>
                            <div id="od-live-rows"></div>
                        </div>
                    </div>

                    {{-- ═══ Facial + Enrichment ═══ --}}
                    <div class="od-section">
                        <div class="od-section__icon od-section__icon--purple">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <div class="od-section__text">
                            <div class="od-section__title">Coleta facial e enriquecimento</div>
                            <div class="od-section__desc">Tokens de captura facial (iEducar) e cache de dados dos alunos</div>
                        </div>
                    </div>

                    <div class="od-kpi-grid">
                        <div class="od-kpi">
                            <div class="od-kpi__top">
                                <div class="od-kpi__label">Solicitações faciais</div>
                                <div class="od-kpi__icon od-kpi__icon--purple">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                </div>
                            </div>
                            <div class="od-kpi__value">{{ number_format($facial['total']) }}</div>
                            <div class="od-kpi__sub">{{ $facial['used'] }} usadas · {{ $facial['expired'] }} expiradas</div>
                            <div class="od-kpi__bar od-kpi__bar--purple"></div>
                        </div>
                        <div class="od-kpi">
                            <div class="od-kpi__top">
                                <div class="od-kpi__label">Faciais (7 dias)</div>
                                <div class="od-kpi__icon od-kpi__icon--purple">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                                </div>
                            </div>
                            <div class="od-kpi__value">{{ number_format($facial['last_7d']) }}</div>
                            <div class="od-kpi__sub">últimos 7 dias</div>
                            <div class="od-kpi__bar od-kpi__bar--purple"></div>
                        </div>
                        <div class="od-kpi">
                            <div class="od-kpi__top">
                                <div class="od-kpi__label">Alunos cacheados</div>
                                <div class="od-kpi__icon od-kpi__icon--cyan">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                                </div>
                            </div>
                            <div class="od-kpi__value">{{ number_format($enrichment['cached_students']) }}</div>
                            <div class="od-kpi__sub">{{ $enrichment['fresh'] }} frescos · {{ $enrichment['expired'] }} expirados</div>
                            <div class="od-kpi__bar od-kpi__bar--cyan"></div>
                        </div>
                    </div>

                    {{-- ═══ Detalhes técnicos ═══ --}}
                    <div class="od-section">
                        <div class="od-section__icon od-section__icon--slate">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                        </div>
                        <div class="od-section__text">
                            <div class="od-section__title">Detalhes técnicos</div>
                            <div class="od-section__desc">Informações do runtime, middleware e configurações relevantes</div>
                        </div>
                    </div>

                    <div class="od-tech-grid">
                        <div class="od-tech-item">
                            <div class="od-tech-item__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                            </div>
                            <div>
                                <div class="od-tech-item__label">Laravel</div>
                                <div class="od-tech-item__value">v{{ app()->version() }}</div>
                            </div>
                        </div>
                        <div class="od-tech-item">
                            <div class="od-tech-item__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/></svg>
                            </div>
                            <div>
                                <div class="od-tech-item__label">PHP</div>
                                <div class="od-tech-item__value">{{ PHP_VERSION }}</div>
                            </div>
                        </div>
                        <div class="od-tech-item">
                            <div class="od-tech-item__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
                            </div>
                            <div>
                                <div class="od-tech-item__label">Timezone</div>
                                <div class="od-tech-item__value">{{ config('app.timezone', 'UTC') }}</div>
                            </div>
                        </div>
                        <div class="od-tech-item">
                            <div class="od-tech-item__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                            </div>
                            <div>
                                <div class="od-tech-item__label">Queue driver</div>
                                <div class="od-tech-item__value">{{ config('queue.default', 'sync') }}</div>
                            </div>
                        </div>
                        <div class="od-tech-item">
                            <div class="od-tech-item__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                            </div>
                            <div>
                                <div class="od-tech-item__label">Database</div>
                                <div class="od-tech-item__value">{{ config('database.default', '?') }}</div>
                            </div>
                        </div>
                        <div class="od-tech-item">
                            <div class="od-tech-item__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            </div>
                            <div>
                                <div class="od-tech-item__label">Request ID</div>
                                <div class="od-tech-item__value">AssignRequestId (API)</div>
                            </div>
                        </div>
                        <div class="od-tech-item">
                            <div class="od-tech-item__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                            </div>
                            <div>
                                <div class="od-tech-item__label">Motor presença</div>
                                <div class="od-tech-item__value">PresenceRuleEngine (4 modos)</div>
                            </div>
                        </div>
                        <div class="od-tech-item">
                            <div class="od-tech-item__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </div>
                            <div>
                                <div class="od-tech-item__label">Enrichment TTL</div>
                                <div class="od-tech-item__value">24h (student cache)</div>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 32px; text-align: center;">
                        <a class="bridge-btn" href="{{ url('/dashboard') }}">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                            Voltar ao dashboard
                        </a>
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
