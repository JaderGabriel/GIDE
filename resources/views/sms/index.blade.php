<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>SMS • Envios • {{ config('app.name', 'Bridge ERP') }}</title>

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
                                <div class="bridge-brand__tagline">SMS • Envios</div>
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
                                <div class="bridge-panel__title">Mensagens SMS</div>
                                <div class="bridge-panel__meta">logs • status • filtros</div>
                            </div>

                            <form method="GET" action="{{ route('sms.index') }}" class="bridge-form" style="margin-top: 12px;">
                                <div class="bridge-field">
                                    <label class="bridge-label" for="status">Status</label>
                                    <select class="bridge-input" id="status" name="status">
                                        <option value="" {{ ($filters['status'] ?? '') === '' ? 'selected' : '' }}>Todos</option>
                                        <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>pending</option>
                                        <option value="sent" {{ ($filters['status'] ?? '') === 'sent' ? 'selected' : '' }}>sent</option>
                                        <option value="error" {{ ($filters['status'] ?? '') === 'error' ? 'selected' : '' }}>error</option>
                                    </select>
                                </div>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="to">Telefone (contém)</label>
                                    <input class="bridge-input" id="to" name="to" type="text" value="{{ $filters['to'] ?? '' }}" placeholder="55119..." />
                                </div>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="aluno_id">Aluno ID</label>
                                    <input class="bridge-input" id="aluno_id" name="aluno_id" type="text" value="{{ $filters['aluno_id'] ?? '' }}" />
                                </div>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="matricula_id">Matrícula ID</label>
                                    <input class="bridge-input" id="matricula_id" name="matricula_id" type="text" value="{{ $filters['matricula_id'] ?? '' }}" />
                                </div>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="event_id">Event ID</label>
                                    <input class="bridge-input" id="event_id" name="event_id" type="text" value="{{ $filters['event_id'] ?? '' }}" />
                                </div>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="from_date">De (data)</label>
                                    <input class="bridge-input" id="from_date" name="from_date" type="date" value="{{ $filters['from_date'] ?? '' }}" />
                                </div>

                                <div class="bridge-field">
                                    <label class="bridge-label" for="to_date">Até (data)</label>
                                    <input class="bridge-input" id="to_date" name="to_date" type="date" value="{{ $filters['to_date'] ?? '' }}" />
                                </div>

                                <div class="bridge-form__actions">
                                    <button type="submit" class="bridge-btn bridge-btn--primary">Filtrar</button>
                                    <a class="bridge-btn" href="{{ route('sms.index') }}">Limpar</a>
                                    <a class="bridge-btn" href="{{ route('integrations.sms') }}">Configurar SMS</a>
                                    <a class="bridge-btn" href="/dashboard">Voltar</a>
                                </div>
                            </form>

                            <hr style="margin: 18px 0; border: none; border-top: 1px solid var(--border);" />

                            <div style="overflow:auto;">
                                <table style="width:100%; border-collapse: collapse;">
                                    <thead>
                                        <tr>
                                            <th style="text-align:left; padding:10px; border-bottom: 1px solid var(--border);">ID</th>
                                            <th style="text-align:left; padding:10px; border-bottom: 1px solid var(--border);">Status</th>
                                            <th style="text-align:left; padding:10px; border-bottom: 1px solid var(--border);">Telefone</th>
                                            <th style="text-align:left; padding:10px; border-bottom: 1px solid var(--border);">Aluno</th>
                                            <th style="text-align:left; padding:10px; border-bottom: 1px solid var(--border);">Matrícula</th>
                                            <th style="text-align:left; padding:10px; border-bottom: 1px solid var(--border);">Criado</th>
                                            <th style="text-align:left; padding:10px; border-bottom: 1px solid var(--border);">Enviado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($deliveries as $d)
                                            <tr>
                                                <td style="padding:10px; border-bottom: 1px solid var(--border);">
                                                    <a href="{{ route('sms.show', ['id' => $d->id]) }}">{{ $d->id }}</a>
                                                </td>
                                                <td style="padding:10px; border-bottom: 1px solid var(--border);">
                                                    <strong>{{ $d->status }}</strong>
                                                    @if ($d->last_http_status)
                                                        <div class="bridge-muted" style="margin-top: 4px;">HTTP {{ $d->last_http_status }}</div>
                                                    @endif
                                                </td>
                                                <td style="padding:10px; border-bottom: 1px solid var(--border);">{{ $d->to }}</td>
                                                <td style="padding:10px; border-bottom: 1px solid var(--border);">{{ $d->aluno_id ?? '-' }}</td>
                                                <td style="padding:10px; border-bottom: 1px solid var(--border);">{{ $d->matricula_id ?? '-' }}</td>
                                                <td style="padding:10px; border-bottom: 1px solid var(--border); line-height:1.35;">{{ $d->created_at ? \App\Support\DateDisplay::formatHuman($d->created_at, true) : '—' }}</td>
                                                <td style="padding:10px; border-bottom: 1px solid var(--border); line-height:1.35;">{{ $d->sent_at ? \App\Support\DateDisplay::formatHuman($d->sent_at, true) : '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="bridge-muted" style="padding:10px;">Nenhuma mensagem encontrada.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div style="margin-top: 14px;">
                                {{ $deliveries->links() }}
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

