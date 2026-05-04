<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Integrações • Frequência registro • {{ config('app.name', 'Bridge ERP') }}</title>

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
        @include('partials.integr-visual-kit')
        <script defer src="/home.js"></script>
        <style>
            .fr-json {
                width: 100%;
                min-height: 280px;
                padding: 12px 14px;
                border-radius: 14px;
                border: 1px solid var(--border);
                background: var(--surface-1);
                color: var(--text);
                line-height: 1.45;
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
                font-size: 12px;
            }
            .fr-json:focus {
                outline: 2px solid color-mix(in srgb, var(--accent-a) 32%, transparent);
                outline-offset: 1px;
            }
            .fr-back-row {
                margin-top: 12px;
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                align-items: center;
            }
            .fr-back-fila {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 0 16px;
                min-height: 42px;
                border-radius: 12px;
                border: 1px solid color-mix(in srgb, #7c3aed 38%, var(--border));
                background: color-mix(in srgb, #7c3aed 10%, var(--surface-1));
                color: color-mix(in srgb, var(--text) 86%, #7c3aed);
                font-size: 14px;
                font-weight: 650;
                text-decoration: none;
                transition: background 0.14s ease, border-color 0.14s ease, transform 0.08s ease;
            }
            .fr-back-fila:hover {
                background: color-mix(in srgb, #7c3aed 16%, var(--surface-1));
                border-color: color-mix(in srgb, #7c3aed 52%, var(--border));
                text-decoration: none;
                color: color-mix(in srgb, var(--text) 82%, #7c3aed);
            }
            .fr-back-fila:active {
                transform: translateY(1px);
            }
            .fr-back-fila svg {
                width: 18px;
                height: 18px;
                flex-shrink: 0;
                opacity: 0.95;
            }
            html.dark .fr-back-fila {
                border-color: color-mix(in srgb, #a78bfa 40%, var(--border));
                background: color-mix(in srgb, #7c3aed 16%, var(--surface-1));
            }
            .fr-hist-wrap {
                margin-top: 14px;
                border-radius: 16px;
                border: 1px solid var(--border);
                overflow: hidden;
                background: color-mix(in srgb, var(--surface-1) 70%, var(--surface-2));
                box-shadow: var(--shadow-soft, 0 8px 24px -12px color-mix(in srgb, var(--bg0) 45%, transparent));
            }
            .fr-hist {
                width: 100%;
                border-collapse: collapse;
                font-size: 13px;
            }
            .fr-hist thead th {
                padding: 11px 14px;
                text-align: left;
                font-size: 10px;
                font-weight: 800;
                letter-spacing: 0.07em;
                text-transform: uppercase;
                color: var(--muted);
                background: color-mix(in srgb, var(--surface-2) 92%, var(--surface-1));
                border-bottom: 1px solid var(--border);
            }
            .fr-hist thead th:last-child {
                text-align: center;
                width: 56px;
            }
            .fr-hist tbody td {
                padding: 12px 14px;
                vertical-align: middle;
                border-bottom: 1px solid color-mix(in srgb, var(--border) 85%, transparent);
            }
            .fr-hist tbody tr:last-child td {
                border-bottom: none;
            }
            .fr-hist-row--preview td:first-child {
                box-shadow: inset 3px 0 0 0 #0284c7;
            }
            .fr-hist-row--apply td:first-child {
                box-shadow: inset 3px 0 0 0 #059669;
            }
            html.dark .fr-hist-row--preview td:first-child {
                box-shadow: inset 3px 0 0 0 #38bdf8;
            }
            html.dark .fr-hist-row--apply td:first-child {
                box-shadow: inset 3px 0 0 0 #34d399;
            }
            .fr-hist-id {
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                font-size: 12px;
                font-weight: 700;
                color: var(--text);
            }
            .fr-hist-pill {
                display: inline-flex;
                align-items: center;
                padding: 4px 10px;
                border-radius: 999px;
                font-size: 11px;
                font-weight: 750;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                border: 1px solid var(--border);
            }
            .fr-hist-pill--preview {
                border-color: color-mix(in srgb, #0284c7 42%, var(--border));
                background: color-mix(in srgb, #0284c7 12%, var(--surface-1));
                color: color-mix(in srgb, var(--text) 88%, #0284c7);
            }
            .fr-hist-pill--apply {
                border-color: color-mix(in srgb, #059669 42%, var(--border));
                background: color-mix(in srgb, #059669 12%, var(--surface-1));
                color: color-mix(in srgb, var(--text) 86%, #059669);
            }
            .fr-hist-st {
                display: inline-flex;
                align-items: center;
                padding: 3px 9px;
                border-radius: 999px;
                font-size: 10px;
                font-weight: 750;
                letter-spacing: 0.05em;
                text-transform: uppercase;
                border: 1px solid var(--border);
            }
            .fr-hist-st--pending {
                background: color-mix(in srgb, #f59e0b 12%, var(--surface-1));
                border-color: color-mix(in srgb, #f59e0b 38%, var(--border));
                color: color-mix(in srgb, var(--text) 75%, #d97706);
            }
            .fr-hist-st--processing {
                background: color-mix(in srgb, #6366f1 12%, var(--surface-1));
                border-color: color-mix(in srgb, #6366f1 38%, var(--border));
                color: color-mix(in srgb, var(--text) 82%, #4f46e5);
            }
            .fr-hist-st--completed {
                background: color-mix(in srgb, #059669 12%, var(--surface-1));
                border-color: color-mix(in srgb, #059669 36%, var(--border));
                color: color-mix(in srgb, var(--text) 82%, #059669);
            }
            .fr-hist-st--failed {
                background: color-mix(in srgb, #ef4444 10%, var(--surface-1));
                border-color: color-mix(in srgb, #ef4444 40%, var(--border));
                color: #dc2626;
            }
            .fr-hist-http {
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                font-size: 12px;
                color: var(--muted);
            }
            .fr-hist-date {
                font-size: 12px;
                color: var(--muted);
                white-space: nowrap;
            }
            .fr-hist-go {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                margin: 0 auto;
                border-radius: 12px;
                border: 1px solid color-mix(in srgb, var(--accent-a) 28%, var(--border));
                background: color-mix(in srgb, var(--accent-a) 8%, var(--surface-1));
                color: color-mix(in srgb, var(--text) 88%, var(--accent-a));
                text-decoration: none;
                transition: background 0.14s ease, border-color 0.14s ease, transform 0.08s ease, box-shadow 0.14s ease;
            }
            .fr-hist-go:hover {
                background: color-mix(in srgb, var(--accent-a) 16%, var(--surface-1));
                border-color: color-mix(in srgb, var(--accent-a) 45%, var(--border));
                text-decoration: none;
                color: var(--text);
                box-shadow: 0 0 0 1px color-mix(in srgb, var(--accent-a) 12%, transparent);
            }
            .fr-hist-go:active {
                transform: translateY(1px);
            }
            .fr-hist-go svg {
                width: 20px;
                height: 20px;
            }
            .fr-hist-empty {
                padding: 22px 16px;
                text-align: center;
                color: var(--muted);
                font-size: 14px;
            }
            .fr-enqueue-row {
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
                align-items: stretch;
                margin-top: 6px;
                margin-bottom: 4px;
            }
            .fr-enqueue-btn {
                appearance: none;
                cursor: pointer;
                font-family: inherit;
                font-size: 14px;
                font-weight: 700;
                padding: 14px 18px;
                border-radius: 14px;
                border: 1px solid var(--border);
                transition: background 0.14s ease, border-color 0.14s ease, box-shadow 0.14s ease, transform 0.08s ease;
                flex: 1 1 220px;
                min-height: 52px;
                line-height: 1.25;
                text-align: center;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 2px;
            }
            .fr-enqueue-btn:active:not(:disabled) {
                transform: translateY(1px);
            }
            .fr-enqueue-btn:disabled {
                opacity: 0.48;
                cursor: not-allowed;
            }
            .fr-enqueue-btn--preview {
                background: color-mix(in srgb, #0284c7 12%, var(--surface-1));
                border-color: color-mix(in srgb, #0284c7 44%, var(--border));
                color: color-mix(in srgb, var(--text) 86%, #0284c7);
                box-shadow: 0 0 0 1px color-mix(in srgb, #0284c7 10%, transparent);
            }
            .fr-enqueue-btn--preview:hover:not(:disabled) {
                background: color-mix(in srgb, #0284c7 20%, var(--surface-1));
                border-color: color-mix(in srgb, #0284c7 58%, var(--border));
            }
            .fr-enqueue-btn--homolog {
                background: color-mix(in srgb, #059669 16%, var(--surface-1));
                border-color: color-mix(in srgb, #059669 48%, var(--border));
                color: color-mix(in srgb, var(--text) 84%, #059669);
                box-shadow: 0 0 0 1px color-mix(in srgb, #059669 12%, transparent);
            }
            .fr-enqueue-btn--homolog:hover:not(:disabled) {
                background: color-mix(in srgb, #059669 26%, var(--surface-1));
                border-color: color-mix(in srgb, #059669 62%, var(--border));
            }
            html.dark .fr-enqueue-btn--preview {
                background: color-mix(in srgb, #38bdf8 14%, var(--surface-1));
                border-color: color-mix(in srgb, #38bdf8 42%, var(--border));
                color: color-mix(in srgb, var(--text) 90%, #38bdf8);
            }
            html.dark .fr-enqueue-btn--preview:hover:not(:disabled) {
                background: color-mix(in srgb, #38bdf8 22%, var(--surface-1));
                border-color: color-mix(in srgb, #38bdf8 55%, var(--border));
            }
            html.dark .fr-enqueue-btn--homolog {
                background: color-mix(in srgb, #34d399 14%, var(--surface-1));
                border-color: color-mix(in srgb, #34d399 40%, var(--border));
                color: color-mix(in srgb, var(--text) 88%, #34d399);
            }
            html.dark .fr-enqueue-btn--homolog:hover:not(:disabled) {
                background: color-mix(in srgb, #34d399 22%, var(--surface-1));
                border-color: color-mix(in srgb, #34d399 52%, var(--border));
            }
            .fr-enqueue-hint {
                font-size: 10px;
                font-weight: 650;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                opacity: 0.88;
            }
        </style>
    </head>
    <body>
        <div class="bridge-shell integr-app">
            <header class="bridge-header">
                <div class="bridge-container">
                    <div class="bridge-header__inner">
                        <a class="bridge-brand" href="{{ url('/dashboard') }}">
                            <img src="/favicon.svg" alt="" class="bridge-brand__logo" />
                            <div class="bridge-brand__text">
                                <div class="bridge-brand__name">{{ config('app.name', 'Bridge ERP') }}</div>
                                <div class="bridge-brand__tagline">Integrações • Frequência → iEducar</div>
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
                                <div class="bridge-panel__meta">GIDE → iEducar • contrato v1 por aluno • preview e homologação</div>
                            </div>

                            <x-audit-toolbar style="margin-top: 12px;" />

                            <div class="fr-back-row">
                                <a class="fr-back-fila" href="{{ route('admin.ieducar-frequencia-deliveries.index') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <line x1="19" y1="12" x2="5" y2="12" />
                                        <polyline points="12 19 5 12 12 5" />
                                    </svg>
                                    Voltar à fila
                                </a>
                            </div>

                            @if (session('status'))
                                <p class="bridge-muted" style="margin-top: 12px;">
                                    <strong>{{ session('status') }}</strong>
                                </p>
                            @endif

                            @if ($errors->any())
                                <div class="bridge-error" style="margin-top: 12px;">
                                    @foreach ($errors->all() as $e)
                                        <div>{{ $e }}</div>
                                    @endforeach
                                </div>
                            @endif

                            <section class="integr-section-card" style="margin-top: 14px;">
                                <h2 class="integr-section__title">Enfileirar</h2>
                                <p class="integr-section__lead">
                                    Destino no iEducar: <code class="mono">{{ $targetPath }}</code> (relativo ao <code class="mono">base_url</code> da integração iEducar).
                                    Bearer: token de confirmação ou token principal — ver
                                    <a href="{{ route('integrations.ieducar') }}">Integrações → iEducar</a>.
                                    Documentação:
                                    <a href="{{ route('integrations.docs.ieducar-frequencia-registro') }}" target="_blank" rel="noreferrer">markdown</a>.
                                </p>

                                @if (! $ieducar || ! $ieducar->enabled)
                                    <div class="bridge-error" style="margin-top: 12px;">
                                        <strong>Integração iEducar desabilitada ou inexistente.</strong> Habilite em Integrações → iEducar.
                                    </div>
                                @endif

                                <form method="POST" class="bridge-form" style="margin-top: 14px;">
                                    @csrf
                                    <div class="fr-enqueue-row" role="group" aria-label="Enfileirar processamento">
                                        <button
                                            type="submit"
                                            class="fr-enqueue-btn fr-enqueue-btn--preview"
                                            formaction="{{ route('integrations.ieducar.frequencia-registro.preview') }}"
                                            formmethod="POST"
                                            aria-label="Enfileirar em modo preview (simulação)"
                                            title="Fila em modo preview — não grava definitivamente no iEducar conforme o contrato de teste"
                                            {{ ! $ieducar || ! $ieducar->enabled ? 'disabled' : '' }}
                                        >
                                            Enfileirar preview
                                            <span class="fr-enqueue-hint">simulação · fila preview</span>
                                        </button>
                                        <button
                                            type="submit"
                                            class="fr-enqueue-btn fr-enqueue-btn--homolog"
                                            formaction="{{ route('integrations.ieducar.frequencia-registro.enqueue') }}"
                                            formmethod="POST"
                                            aria-label="Enfileirar em modo homologação (gravação)"
                                            title="Fila em modo apply — envio real ao endpoint de registo no iEducar"
                                            {{ ! $ieducar || ! $ieducar->enabled ? 'disabled' : '' }}
                                        >
                                            Enfileirar homologação
                                            <span class="fr-enqueue-hint">gravação · fila apply</span>
                                        </button>
                                    </div>

                                    <div class="bridge-field" style="margin-top: 8px;">
                                        <label class="bridge-label" for="payload">Corpo JSON (contrato v1 — plano B por aluno)</label>
                                        <textarea class="fr-json" id="payload" name="payload" required>{{ old('payload', $defaultPayloadJson) }}</textarea>
                                    </div>
                                </form>
                            </section>

                            <section class="integr-section-card" style="margin-top: 16px;">
                                <h2 class="integr-section__title">Histórico</h2>
                                <p class="integr-section__lead">
                                    As <strong>3 últimas</strong> solicitações enfileiradas a partir desta página. A seta abre o detalhe completo na fila admin
                                    (<code class="mono">/admin/frequencia-ieducar/{id}</code>).
                                </p>
                                <div class="fr-hist-wrap">
                                    <table class="fr-hist">
                                        <thead>
                                            <tr>
                                                <th scope="col">ID</th>
                                                <th scope="col">Tipo</th>
                                                <th scope="col">Estado</th>
                                                <th scope="col">HTTP</th>
                                                <th scope="col">Enfileirado</th>
                                                <th scope="col"><span class="bridge-sr-only">Abrir detalhe</span></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($recent as $r)
                                                @php
                                                    $isPreview = $r->mode === \App\Models\IeducarFrequenciaRegistroDelivery::MODE_PREVIEW;
                                                    $st = (string) $r->status;
                                                    $stClass = match ($st) {
                                                        \App\Models\IeducarFrequenciaRegistroDelivery::STATUS_PENDING => 'fr-hist-st--pending',
                                                        \App\Models\IeducarFrequenciaRegistroDelivery::STATUS_PROCESSING => 'fr-hist-st--processing',
                                                        \App\Models\IeducarFrequenciaRegistroDelivery::STATUS_COMPLETED => 'fr-hist-st--completed',
                                                        \App\Models\IeducarFrequenciaRegistroDelivery::STATUS_FAILED => 'fr-hist-st--failed',
                                                        default => '',
                                                    };
                                                    $stLabel = match ($st) {
                                                        \App\Models\IeducarFrequenciaRegistroDelivery::STATUS_PENDING => 'Pendente',
                                                        \App\Models\IeducarFrequenciaRegistroDelivery::STATUS_PROCESSING => 'Processando',
                                                        \App\Models\IeducarFrequenciaRegistroDelivery::STATUS_COMPLETED => 'Concluído',
                                                        \App\Models\IeducarFrequenciaRegistroDelivery::STATUS_FAILED => 'Falhou',
                                                        default => $st,
                                                    };
                                                @endphp
                                                <tr class="{{ $isPreview ? 'fr-hist-row--preview' : 'fr-hist-row--apply' }}">
                                                    <td><span class="fr-hist-id">#{{ $r->id }}</span></td>
                                                    <td>
                                                        @if ($isPreview)
                                                            <span class="fr-hist-pill fr-hist-pill--preview">Preview</span>
                                                        @else
                                                            <span class="fr-hist-pill fr-hist-pill--apply">Homologação</span>
                                                        @endif
                                                    </td>
                                                    <td><span class="fr-hist-st {{ $stClass }}">{{ $stLabel }}</span></td>
                                                    <td><span class="fr-hist-http">{{ $r->http_status ?? '—' }}</span></td>
                                                    <td><span class="fr-hist-date">{{ $r->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span></td>
                                                    <td style="text-align: center;">
                                                        <a
                                                            class="fr-hist-go"
                                                            href="{{ route('admin.ieducar-frequencia-deliveries.show', $r->id) }}"
                                                            title="Abrir detalhe #{{ $r->id }} na fila"
                                                            aria-label="Abrir detalhe da entrega {{ $r->id }} na fila admin"
                                                        >
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                <line x1="5" y1="12" x2="19" y2="12" />
                                                                <polyline points="12 5 19 12 12 19" />
                                                            </svg>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="fr-hist-empty">Nenhuma solicitação ainda. Use os botões acima para enfileirar preview ou homologação.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </section>
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
