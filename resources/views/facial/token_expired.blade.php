<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Link expirado • {{ config('app.name', 'Bridge ERP') }}</title>
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
        <script defer src="/home.js"></script>
        <style>
            .fac-exp-page {
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 32px 20px 48px;
                background:
                    radial-gradient(ellipse 120% 80% at 50% -20%, color-mix(in srgb, #f59e0b 12%, transparent), transparent 55%),
                    radial-gradient(ellipse 80% 50% at 80% 100%, color-mix(in srgb, #94a3b8 8%, transparent), transparent 45%),
                    var(--bg0);
            }
            html.dark .fac-exp-page {
                background:
                    radial-gradient(ellipse 120% 80% at 50% -20%, color-mix(in srgb, #f59e0b 10%, transparent), transparent 55%),
                    radial-gradient(ellipse 80% 50% at 80% 100%, color-mix(in srgb, #64748b 12%, transparent), transparent 45%),
                    var(--bg0);
            }
            .fac-exp-card {
                max-width: 440px;
                width: 100%;
                text-align: center;
                padding: 40px 28px 36px;
                border-radius: 24px;
                border: 1px solid color-mix(in srgb, #f59e0b 35%, var(--border));
                background: color-mix(in srgb, var(--card-strong) 96%, transparent);
                box-shadow:
                    0 0 0 1px color-mix(in srgb, #f59e0b 12%, transparent),
                    0 24px 48px -24px color-mix(in srgb, var(--bg0) 40%, #0f172a);
            }
            .fac-exp-visual {
                position: relative;
                width: 120px;
                height: 120px;
                margin: 0 auto 28px;
            }
            .fac-exp-ripple {
                position: absolute;
                inset: 0;
                border-radius: 50%;
                border: 2px solid color-mix(in srgb, #f59e0b 55%, transparent);
                animation: facExpRipple 2.4s ease-out infinite;
                opacity: 0;
            }
            .fac-exp-ripple--2 { animation-delay: 0.8s; }
            .fac-exp-ripple--3 { animation-delay: 1.6s; }
            @keyframes facExpRipple {
                0% { transform: scale(0.35); opacity: 0.85; }
                100% { transform: scale(1.35); opacity: 0; }
            }
            .fac-exp-clock {
                position: absolute;
                inset: 12px;
                border-radius: 50%;
                background: linear-gradient(160deg, var(--surface-2), color-mix(in srgb, var(--surface-1) 88%, var(--border)));
                border: 2px solid color-mix(in srgb, #f59e0b 40%, var(--border));
                display: grid;
                place-items: center;
                box-shadow: inset 0 2px 12px color-mix(in srgb, var(--bg0) 25%, transparent);
                animation: facExpClockWobble 3.5s ease-in-out infinite;
            }
            @keyframes facExpClockWobble {
                0%, 100% { transform: rotate(-2deg); }
                50% { transform: rotate(2deg); }
            }
            .fac-exp-clock svg {
                width: 52px;
                height: 52px;
                color: color-mix(in srgb, #f59e0b 85%, var(--muted));
                animation: facExpClockFade 3.5s ease-in-out infinite;
            }
            @keyframes facExpClockFade {
                0%, 45% { opacity: 1; }
                55%, 100% { opacity: 0.45; }
            }
            .fac-exp-title {
                font-size: 1.35rem;
                font-weight: 800;
                letter-spacing: -0.02em;
                margin: 0 0 12px;
                color: var(--text);
            }
            .fac-exp-lead {
                margin: 0;
                font-size: 1.05rem;
                line-height: 1.55;
                color: var(--muted);
            }
            .fac-exp-hint {
                margin-top: 22px;
                padding-top: 20px;
                border-top: 1px dashed color-mix(in srgb, var(--border) 90%, #f59e0b);
                font-size: 0.9rem;
                line-height: 1.5;
                color: color-mix(in srgb, var(--muted) 92%, var(--text));
            }
            .fac-exp-brand {
                margin-top: 36px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                opacity: 0.55;
            }
            .fac-exp-brand img { width: 28px; height: 28px; }
            .fac-exp-brand span {
                font-size: 13px;
                font-weight: 600;
                color: var(--muted);
            }
        </style>
    </head>
    <body>
        <div class="fac-exp-page">
            <div class="fac-exp-card" role="alert" aria-live="assertive">
                <div class="fac-exp-visual" aria-hidden="true">
                    <span class="fac-exp-ripple"></span>
                    <span class="fac-exp-ripple fac-exp-ripple--2"></span>
                    <span class="fac-exp-ripple fac-exp-ripple--3"></span>
                    <div class="fac-exp-clock">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                </div>
                <h1 class="fac-exp-title">Este link expirou</h1>
                <p class="fac-exp-lead">
                    O prazo do token de envio facial <strong>já encerrou</strong>. Por motivos de segurança, cada link só pode ser usado dentro da validade definida pelo sistema.
                </p>
                <p class="fac-exp-hint">
                    Para continuar, é necessário <strong>gerar um novo link</strong> no iEducar (ou no fluxo da sua escola). Esta página não pode ser reutilizada.
                    @if (! empty($expired_at))
                        <br /><br />
                        <span class="mono" style="font-size: 0.82rem; opacity: 0.9;">Validade deste link: {{ $expired_at->timezone(config('app.timezone'))->format('d/m/Y \à\s H:i') }}</span>
                    @endif
                </p>
            </div>
            <div class="fac-exp-brand">
                <img src="/favicon.svg" alt="" />
                <span>{{ config('app.name', 'Bridge ERP') }}</span>
            </div>
            <div style="margin-top: 20px;">
                <button type="button" class="bridge-btn bridge-iconbtn" data-theme-toggle aria-pressed="false" title="Alternar tema claro/escuro">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 18a6 6 0 1 0 0-12 6 6 0 0 0 0 12Z" stroke="currentColor" stroke-width="2"/>
                        <path d="M12 2v2M12 20v2M4 12H2M22 12h-2M5 5l1.5 1.5M17.5 17.5 19 19M19 5l-1.5 1.5M6.5 17.5 5 19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
        </div>
    </body>
</html>
