<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Frequência GIDE → iEducar • {{ config('app.name', 'Bridge ERP') }}</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        <script>
            (function () {
                try {
                    const stored = localStorage.getItem('theme');
                    const theme = stored === 'light' || stored === 'dark' ? stored : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                    document.documentElement.classList.toggle('dark', theme === 'dark');
                    document.documentElement.dataset.theme = theme;
                } catch (_) {}
            })();
        </script>
        <link rel="stylesheet" href="/home.css">
        <script defer src="/home.js"></script>
        <style>
            .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12px; }
            .fr-json { width: 100%; min-height: 280px; padding: 12px; border-radius: 14px; border: 1px solid var(--border); background: var(--surface-1); color: var(--text); line-height: 1.45; }
            .fr-table-wrap { margin-top: 14px; overflow: auto; border: 1px solid var(--border); border-radius: 14px; max-height: 320px; }
            .fr-table { width: 100%; border-collapse: collapse; font-size: 12px; }
            .fr-table th, .fr-table td { padding: 8px 10px; border-bottom: 1px solid var(--border); text-align: left; vertical-align: top; }
            .fr-table th { position: sticky; top: 0; background: var(--surface-2); color: var(--muted); font-weight: 650; z-index: 4; box-shadow: var(--sticky-table-head-shadow, 0 10px 28px -8px rgba(2, 6, 23, 0.28)); }
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
                                <div class="bridge-brand__tagline">Frequência • GIDE → iEducar</div>
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
                                <div class="bridge-panel__title">Registro de frequência (lote)</div>
                                <div class="bridge-panel__meta">formato por aluno (cod_aluno + data_ref) • preview e gravação na fila</div>
                            </div>

                            @if (session('status'))
                                <p class="bridge-muted" style="margin-top: 12px;"><strong>{{ session('status') }}</strong></p>
                            @endif

                            @if ($errors->any())
                                <div class="bridge-error" style="margin-top: 12px;">
                                    @foreach ($errors->all() as $e)
                                        <div>{{ $e }}</div>
                                    @endforeach
                                </div>
                            @endif

                            <p class="bridge-muted" style="margin-top: 12px;">
                                Destino no iEducar: <code class="mono">{{ $targetPath }}</code> (relativo ao <code class="mono">base_url</code> da integração iEducar).
                                Bearer: token de confirmação ou token principal — ver tela <a href="{{ route('integrations.ieducar') }}">Integrações → iEducar</a>.
                                Documentação: <a href="{{ route('integrations.docs.ieducar-frequencia-registro') }}" target="_blank" rel="noreferrer">markdown</a>.
                            </p>

                            @if (! $ieducar || ! $ieducar->enabled)
                                <p style="margin-top: 12px; color: #ef4444;"><strong>Integração iEducar desabilitada ou inexistente.</strong> Habilite em Integrações → iEducar.</p>
                            @endif

                            <form method="POST" class="bridge-form" style="margin-top: 14px;">
                                @csrf
                                <label class="bridge-label" for="payload">Corpo JSON (contrato v1 — plano B por aluno)</label>
                                <textarea class="fr-json mono" id="payload" name="payload" required>{{ old('payload', $defaultPayloadJson) }}</textarea>

                                <div class="bridge-form__actions" style="margin-top: 12px;">
                                    <button type="submit" class="bridge-btn" formaction="{{ route('integrations.ieducar.frequencia-registro.preview') }}" formmethod="POST" {{ ! $ieducar || ! $ieducar->enabled ? 'disabled' : '' }}>Enfileirar preview</button>
                                    <button type="submit" class="bridge-btn bridge-btn--primary" formaction="{{ route('integrations.ieducar.frequencia-registro.enqueue') }}" formmethod="POST" {{ ! $ieducar || ! $ieducar->enabled ? 'disabled' : '' }}>Enfileirar gravação</button>
                                    <a class="bridge-btn" href="{{ route('admin.ieducar-frequencia-deliveries.index') }}">Voltar à lista (admin)</a>
                                    <a class="bridge-btn" href="{{ route('integrations.ieducar') }}">Voltar ao iEducar</a>
                                </div>
                            </form>

                            <h3 class="bridge-panel__title" style="margin-top: 22px; font-size: 15px;">Últimos envios</h3>
                            <div class="fr-table-wrap">
                                <table class="fr-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Modo</th>
                                            <th>Status</th>
                                            <th>HTTP</th>
                                            <th>Criado</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($recent as $r)
                                            <tr>
                                                <td class="mono">{{ $r->id }}</td>
                                                <td>{{ $r->mode }}</td>
                                                <td>{{ $r->status }}</td>
                                                <td class="mono">{{ $r->http_status ?? '—' }}</td>
                                                <td class="mono" style="font-size:11px;">{{ $r->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                                                <td style="white-space: nowrap;">
                                                    @if ($r->status === \App\Models\IeducarFrequenciaRegistroDelivery::STATUS_PENDING)
                                                        <form method="POST" action="{{ route('integrations.ieducar.frequencia-registro.force-send', ['id' => $r->id]) }}" style="display: inline; margin: 0;">
                                                            @csrf
                                                            <button type="submit" class="bridge-btn bridge-btn--primary" style="padding:4px 10px;font-size:12px;">Enviar</button>
                                                        </form>
                                                    @endif
                                                    <a class="bridge-btn" style="padding:4px 10px;font-size:12px;" href="{{ route('integrations.ieducar.frequencia-registro.show', $r->id) }}">Detalhe</a>
                                                    <a class="bridge-btn" style="padding:4px 10px;font-size:12px;" href="{{ route('admin.ieducar-frequencia-deliveries.show', $r->id) }}">Admin</a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="bridge-muted">Nenhum registro ainda.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <footer class="bridge-footer">
                <div class="bridge-container">
                    <div class="bridge-footer__inner">
                        <div>© {{ now()->year }} {{ config('app.name', 'Bridge ERP') }}</div>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
