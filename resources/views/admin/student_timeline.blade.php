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
        <script defer src="/home.js"></script>
        <style>
            .tl-header { display: flex; align-items: flex-start; gap: 16px; flex-wrap: wrap; }
            .tl-header__info { flex: 1; min-width: 200px; }
            .tl-header__actions { display: flex; gap: 8px; flex-shrink: 0; }
            .tl-student-card { margin-top: 14px; padding: 14px; border-radius: 14px; border: 1px solid var(--border); background: color-mix(in srgb, var(--surface-2) 70%, transparent); display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; }
            .tl-student-card__item { font-size: 13px; }
            .tl-student-card__label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); margin-bottom: 2px; }
            .tl-student-card__value { font-weight: 600; }

            .tl-filters { margin-top: 14px; display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
            .tl-filter-btn { appearance: none; border: 1px solid var(--border); background: var(--surface-1); padding: 6px 14px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; color: var(--muted); text-decoration: none; font-family: inherit; }
            .tl-filter-btn:hover { border-color: color-mix(in srgb, var(--accent-a) 30%, var(--border)); color: var(--text); }
            .tl-filter-btn--active { border-color: color-mix(in srgb, var(--accent-a) 45%, var(--border)); background: color-mix(in srgb, var(--accent-a) 10%, var(--surface-1)); color: var(--accent-a); }

            .tl-list { margin-top: 18px; display: flex; flex-direction: column; gap: 0; position: relative; padding-left: 24px; }
            .tl-list::before { content: ''; position: absolute; left: 8px; top: 12px; bottom: 12px; width: 2px; background: var(--border); border-radius: 2px; }
            .tl-item { position: relative; padding: 10px 14px; border-radius: 12px; border: 1px solid var(--border); background: var(--surface-1); margin-bottom: 8px; }
            .tl-item::before { content: ''; position: absolute; left: -20px; top: 16px; width: 10px; height: 10px; border-radius: 50%; border: 2px solid var(--border); background: var(--surface-2); }
            .tl-item--access_event::before { border-color: #0284c7; background: color-mix(in srgb, #0284c7 20%, var(--surface-2)); }
            .tl-item--sms::before { border-color: #059669; background: color-mix(in srgb, #059669 20%, var(--surface-2)); }
            .tl-item--facial::before { border-color: #7c3aed; background: color-mix(in srgb, #7c3aed 20%, var(--surface-2)); }
            .tl-item__head { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
            .tl-item__type { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; padding: 2px 8px; border-radius: 6px; }
            .tl-item__type--access_event { background: color-mix(in srgb, #0284c7 12%, transparent); color: #0284c7; }
            .tl-item__type--sms { background: color-mix(in srgb, #059669 12%, transparent); color: #059669; }
            .tl-item__type--facial { background: color-mix(in srgb, #7c3aed 12%, transparent); color: #7c3aed; }
            .tl-item__time { font-size: 12px; color: var(--muted); font-family: ui-monospace, monospace; }
            .tl-item__summary { margin-top: 4px; font-size: 13px; line-height: 1.4; }
            .tl-item__link { margin-top: 6px; }
            .tl-item__link a { font-size: 12px; color: var(--accent-a); font-weight: 600; text-decoration: none; }
            .tl-item__link a:hover { text-decoration: underline; }
            .tl-empty { margin-top: 20px; padding: 24px; text-align: center; color: var(--muted); font-size: 14px; border: 1px dashed var(--border); border-radius: 14px; }
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
                                        default => '#0284c7',
                                    };
                                @endphp
                                <div style="margin-bottom: 14px; padding: 10px 12px; border-radius: 14px; border: 1px solid color-mix(in srgb, {{ $stColor }} 35%, var(--border)); background: color-mix(in srgb, {{ $stColor }} 10%, transparent); color: {{ $stColor }};">
                                    <strong>{{ session('status') }}</strong>
                                </div>
                            @endif

                            <div class="tl-header">
                                <div class="tl-header__info">
                                    <h1 class="bridge-panel__title">Timeline — Aluno #{{ $codAluno }}</h1>
                                    <div class="bridge-panel__meta">todos os eventos rastreados pelo GIDE para este aluno</div>
                                </div>
                                <div class="tl-header__actions">
                                    <form method="POST" action="{{ route('admin.student-timeline.refresh', ['cod_aluno' => $codAluno]) }}">
                                        @csrf
                                        <button type="submit" class="bridge-btn">Atualizar dados do iEducar</button>
                                    </form>
                                    <a class="bridge-btn" href="{{ route('admin.gestor-access-events.index') }}">Access-events</a>
                                    <a class="bridge-btn" href="{{ url('/dashboard') }}">Dashboard</a>
                                </div>
                            </div>

                            @if ($studentData)
                                <div class="tl-student-card">
                                    @if ($studentData['nome'] ?? null)
                                        <div class="tl-student-card__item">
                                            <div class="tl-student-card__label">Nome</div>
                                            <div class="tl-student-card__value">{{ $studentData['nome'] }}</div>
                                        </div>
                                    @endif
                                    @if ($studentData['turma'] ?? null)
                                        <div class="tl-student-card__item">
                                            <div class="tl-student-card__label">Turma</div>
                                            <div class="tl-student-card__value">{{ $studentData['turma'] }}</div>
                                        </div>
                                    @endif
                                    @if ($studentData['serie'] ?? null)
                                        <div class="tl-student-card__item">
                                            <div class="tl-student-card__label">Série</div>
                                            <div class="tl-student-card__value">{{ $studentData['serie'] }}</div>
                                        </div>
                                    @endif
                                    @if ($studentData['etapa'] ?? null)
                                        <div class="tl-student-card__item">
                                            <div class="tl-student-card__label">Etapa</div>
                                            <div class="tl-student-card__value">{{ $studentData['etapa'] }}</div>
                                        </div>
                                    @endif
                                    @if ($studentData['situacao'] ?? null)
                                        <div class="tl-student-card__item">
                                            <div class="tl-student-card__label">Situação</div>
                                            <div class="tl-student-card__value">{{ $studentData['situacao'] }}</div>
                                        </div>
                                    @endif
                                    @if ($studentData['matricula_id'] ?? null)
                                        <div class="tl-student-card__item">
                                            <div class="tl-student-card__label">Matrícula</div>
                                            <div class="tl-student-card__value">{{ $studentData['matricula_id'] }}</div>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div style="margin-top: 14px; padding: 10px 14px; border-radius: 12px; border: 1px dashed var(--border); font-size: 13px; color: var(--muted);">
                                    Dados do aluno ainda não cacheados. Clique em "Atualizar dados do iEducar" para buscar.
                                </div>
                            @endif

                            <div class="tl-filters">
                                <span style="font-size: 13px; font-weight: 600; color: var(--muted);">Filtrar:</span>
                                <a href="{{ route('admin.student-timeline', ['cod_aluno' => $codAluno]) }}" class="tl-filter-btn {{ $typeFilter === 'all' ? 'tl-filter-btn--active' : '' }}">Todos</a>
                                <a href="{{ route('admin.student-timeline', ['cod_aluno' => $codAluno, 'type' => 'access_event']) }}" class="tl-filter-btn {{ $typeFilter === 'access_event' ? 'tl-filter-btn--active' : '' }}">Acesso</a>
                                <a href="{{ route('admin.student-timeline', ['cod_aluno' => $codAluno, 'type' => 'sms']) }}" class="tl-filter-btn {{ $typeFilter === 'sms' ? 'tl-filter-btn--active' : '' }}">SMS</a>
                                <a href="{{ route('admin.student-timeline', ['cod_aluno' => $codAluno, 'type' => 'facial']) }}" class="tl-filter-btn {{ $typeFilter === 'facial' ? 'tl-filter-btn--active' : '' }}">Facial</a>
                            </div>

                            @if ($timeline->isEmpty())
                                <div class="tl-empty">Nenhum evento encontrado para este aluno{{ $typeFilter !== 'all' ? ' com o filtro selecionado' : '' }}.</div>
                            @else
                                <div class="tl-list">
                                    @foreach ($timeline as $item)
                                        <div class="tl-item tl-item--{{ $item['type'] }}">
                                            <div class="tl-item__head">
                                                <span class="tl-item__type tl-item__type--{{ $item['type'] }}">{{ $item['type'] }}</span>
                                                <span class="tl-item__time">{{ $item['at'] ? \Carbon\Carbon::parse($item['at'])->format('d/m/Y H:i:s') : '—' }}</span>
                                            </div>
                                            <div class="tl-item__summary">{{ $item['summary'] }}</div>
                                            @if ($item['detail_url'])
                                                <div class="tl-item__link"><a href="{{ $item['detail_url'] }}">Ver detalhes &rarr;</a></div>
                                            @endif
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
