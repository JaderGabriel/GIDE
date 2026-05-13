<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Timeline • Aluno {{ $codAluno }} • {{ config('app.name', 'Bridge ERP') }}</title>
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
        <link rel="stylesheet" href="/student-timeline.css">
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
                                <div class="bridge-brand__tagline">Admin • Timeline</div>
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

                            @if (session('status'))
                                @php
                                    $lvl = session('status_level') ?: 'info';
                                    $stColor = match ($lvl) {
                                        'success' => '#059669',
                                        'error' => '#dc2626',
                                        'warning' => '#d97706',
                                        default => '#0284c7',
                                    };
                                @endphp
                                <div class="tl-flash" style="border: 1px solid color-mix(in srgb, {{ $stColor }} 35%, var(--border)); background: color-mix(in srgb, {{ $stColor }} 10%, transparent); color: {{ $stColor }};">
                                    @if ($lvl === 'success')
                                        <svg class="tl-flash__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                    @elseif ($lvl === 'error')
                                        <svg class="tl-flash__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                    @elseif ($lvl === 'warning')
                                        <svg class="tl-flash__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                    @else
                                        <svg class="tl-flash__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                    @endif
                                    <div><strong>{{ session('status') }}</strong></div>
                                </div>
                            @endif

                            <div class="tl-hero">
                                <div class="tl-hero__icon" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                </div>
                                <div class="tl-hero__text">
                                    <h1 class="tl-hero__title">Timeline — Aluno #{{ $codAluno }}</h1>
                                    <div class="tl-hero__meta">Eventos de acesso, SMS e reconhecimento facial rastreados pelo GIDE para este aluno.</div>
                                </div>
                                <div class="tl-hero__actions">
                                    <form method="POST" action="{{ route('admin.student-timeline.refresh', ['cod_aluno' => $codAluno]) }}" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="tl-btn tl-btn--primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                                            Atualizar dados do iEducar
                                        </button>
                                    </form>
                                    <a class="tl-btn" href="{{ route('admin.gestor-access-events.index') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                        Access-events
                                    </a>
                                    <a class="tl-btn" href="{{ url('/dashboard') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                                        Dashboard
                                    </a>
                                </div>
                            </div>

                            @if ($studentData)
                                @php
                                    $hasVisibleData = collect($studentData)->except('cod_aluno')->filter()->isNotEmpty();
                                @endphp
                                @if ($hasVisibleData)
                                    <div class="tl-card tl-card--student">
                                        <div class="tl-card__head">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                            <span class="tl-card__title">Dados do aluno (cache iEducar)</span>
                                        </div>
                                        <div class="tl-student-grid">
                                            @foreach (['nome' => 'Nome', 'curso' => 'Curso', 'turma' => 'Turma', 'serie' => 'Série', 'etapa' => 'Etapa', 'situacao' => 'Situação', 'matricula_id' => 'Matrícula'] as $key => $label)
                                                @if ($studentData[$key] ?? null)
                                                    <div class="tl-student-field">
                                                        <div class="tl-student-field__label">{{ $label }}</div>
                                                        <div class="tl-student-field__value">{{ $studentData[$key] }}</div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <div class="tl-callout tl-callout--warn">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                        <div>
                                            Cache atualizado, mas o iEducar não retornou campos esperados (nome, turma, etc.) para <strong>cod_aluno={{ $codAluno }}</strong>.
                                            Verifique se o aluno possui matrícula ativa no iEducar.
                                        </div>
                                    </div>
                                @endif
                            @else
                                <div class="tl-callout tl-callout--muted">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                    <div>Dados do aluno ainda não estão em cache. Use <strong>Atualizar dados do iEducar</strong> para buscar no ERP.</div>
                                </div>
                            @endif

                            <div class="tl-filters">
                                <span class="tl-filters__label">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                                    Filtrar
                                </span>
                                <a href="{{ route('admin.student-timeline', ['cod_aluno' => $codAluno]) }}" class="tl-filter-btn {{ $typeFilter === 'all' ? 'tl-filter-btn--active' : '' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                    Todos
                                </a>
                                <a href="{{ route('admin.student-timeline', ['cod_aluno' => $codAluno, 'type' => 'access_event']) }}" class="tl-filter-btn {{ $typeFilter === 'access_event' ? 'tl-filter-btn--active' : '' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                    Acesso
                                </a>
                                <a href="{{ route('admin.student-timeline', ['cod_aluno' => $codAluno, 'type' => 'sms']) }}" class="tl-filter-btn {{ $typeFilter === 'sms' ? 'tl-filter-btn--active' : '' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                    SMS
                                </a>
                                <a href="{{ route('admin.student-timeline', ['cod_aluno' => $codAluno, 'type' => 'facial']) }}" class="tl-filter-btn {{ $typeFilter === 'facial' ? 'tl-filter-btn--active' : '' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                    Facial
                                </a>
                            </div>

                            @if ($timeline->isEmpty())
                                <div class="tl-empty">
                                    <div class="tl-empty__icon" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                                    </div>
                                    <p class="tl-empty__text">Nenhum evento encontrado para este aluno{{ $typeFilter !== 'all' ? ' com o filtro selecionado' : '' }}.</p>
                                </div>
                            @else
                                <div class="tl-list">
                                    @foreach ($timeline as $item)
                                        @php
                                            $typeLabel = match ($item['type']) {
                                                'access_event' => 'Acesso',
                                                'sms' => 'SMS',
                                                'facial' => 'Facial',
                                                default => $item['type'],
                                            };
                                        @endphp
                                        <div class="tl-item tl-item--{{ $item['type'] }}">
                                            <div class="tl-item__marker" aria-hidden="true">
                                                @if ($item['type'] === 'access_event')
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                                @elseif ($item['type'] === 'sms')
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                                @endif
                                            </div>
                                            <div class="tl-item__body">
                                                <div class="tl-item__head">
                                                    <span class="tl-item__badge tl-item__badge--{{ $item['type'] }}">
                                                        @if ($item['type'] === 'access_event')
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="14" height="10" rx="2"/><path d="M7 11V7a4 4 0 0 1 7.9-1"/></svg>
                                                        @elseif ($item['type'] === 'sms')
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                                        @else
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                                        @endif
                                                        {{ $typeLabel }}
                                                    </span>
                                                    <span class="tl-item__time">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                                        {{ $item['at'] ? \Carbon\Carbon::parse($item['at'])->format('d/m/Y H:i:s') : '—' }}
                                                    </span>
                                                </div>
                                                <div class="tl-item__summary">{{ $item['summary'] }}</div>
                                                @if ($item['detail_url'])
                                                    <div class="tl-item__link">
                                                        <a href="{{ $item['detail_url'] }}">
                                                            Ver detalhes
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                        </div>
                    </div>
                </div>
            </main>

            <footer class="bridge-footer">
                <div class="bridge-container">
                    <div class="bridge-footer__inner">
                        <div>&copy; {{ now()->year }} {{ config('app.name', 'Bridge ERP') }}</div>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
