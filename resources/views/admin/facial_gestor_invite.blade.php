<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin • Gestor Invite #{{ $inviteId ?? '?' }} • {{ config('app.name', 'Bridge ERP') }}</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        <link rel="stylesheet" href="/home.css">
        <style>
            .bridge-container { max-width: 1100px; }
            .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12px; }
            .giv-wrap { padding: 24px 0 48px; }
            .giv-card { border: 1px solid var(--border); border-radius: 16px; padding: 18px; background: var(--card-strong); margin-top: 16px; }
            .giv-url { word-break: break-all; color: var(--accent-a); }
            .giv-pre { margin: 12px 0 0; padding: 14px; border-radius: 12px; border: 1px solid var(--border); background: color-mix(in srgb, var(--bg0) 75%, transparent); max-height: min(75vh, 900px); overflow: auto; white-space: pre-wrap; }
            .giv-actions { margin-top: 16px; display: flex; gap: 10px; flex-wrap: wrap; }
            .fac-btn { display: inline-flex; align-items: center; gap: 8px; padding: 0 14px; height: 40px; border-radius: 12px; border: 1px solid var(--border); background: var(--surface-1); color: var(--text); font-size: 13px; font-weight: 600; text-decoration: none; }
            .fac-btn:hover { background: color-mix(in srgb, var(--bg0) 82%, transparent); text-decoration: none; }
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
                                <div class="bridge-brand__tagline">Admin • Invite Gestor (GET)</div>
                            </div>
                        </a>
                        @include('partials.bridge-user-menu')
                    </div>
                </div>
            </header>
            <main class="bridge-main">
                <div class="bridge-container giv-wrap">
                    <div class="giv-actions">
                        <a class="fac-btn" href="{{ route('admin.facial-requests.show', ['id' => $item->id]) }}">← Voltar ao pedido #{{ $item->id }}</a>
                        <a class="fac-btn" href="{{ route('admin.facial-requests.index') }}">Lista de faciais</a>
                    </div>

                    <div class="giv-card">
                        <h1 style="margin:0 0 8px;font-size:1.15rem;">GET Invite (SDK / catraca)</h1>
                        <p class="bridge-muted" style="margin:0 0 12px;">Mesmo contrato do cliente: <span class="mono">GET {base_url}/SDK/Invite/{InviteId}</span> — <span class="mono">base_url</span> vem da integração Gestor em <span class="mono">/integracoes/gestor</span> (ex.: Kiper Cloud).</p>

                        @if (! empty($error))
                            <p style="margin:0;color:var(--danger, #b91c1c);font-weight:600;">{{ $error }}</p>
                        @endif

                        @if (! empty($effectiveUrl))
                            <div><strong>URL efetiva</strong></div>
                            <div class="mono giv-url">{{ $effectiveUrl }}</div>
                        @endif

                        @if ($httpStatus !== null)
                            <p style="margin:14px 0 0;"><strong>HTTP</strong> <span class="mono">{{ $httpStatus }}</span></p>
                        @endif

                        @if (is_array($responseJson))
                            <h2 style="margin:18px 0 8px;font-size:1rem;">JSON (decodificado)</h2>
                            <pre class="mono giv-pre">{{ json_encode($responseJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        @elseif (! empty($rawBody))
                            <h2 style="margin:18px 0 8px;font-size:1rem;">Corpo bruto</h2>
                            <pre class="mono giv-pre">{{ $rawBody }}</pre>
                        @endif
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
