<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Usuários • {{ config('app.name', 'Bridge ERP') }}</title>
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
            .users-panel-head {
                display: flex;
                flex-wrap: wrap;
                align-items: flex-start;
                justify-content: space-between;
                gap: 14px 20px;
            }
            .users-panel-head__titles { min-width: 0; flex: 1 1 200px; }
            .users-toolbar {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                align-items: center;
                justify-content: flex-end;
                flex-shrink: 0;
            }
            .users-t { width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 16px; }
            .users-t th, .users-t td { padding: 12px 14px; border-bottom: 1px solid var(--border); text-align: left; vertical-align: middle; }
            .users-t th { color: var(--muted); font-weight: 650; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; }
            .users-t th:last-child, .users-t td:last-child { text-align: right; }
            .users-namecell { display: flex; align-items: center; gap: 12px; min-width: 0; }
            .users-avatar {
                width: 38px;
                height: 38px;
                border-radius: 999px;
                flex-shrink: 0;
                display: grid;
                place-items: center;
                font-size: 13px;
                font-weight: 800;
                border: 1px solid var(--border);
            }
            .users-avatar--admin {
                background: color-mix(in srgb, var(--accent-a) 22%, var(--card-strong));
                color: color-mix(in srgb, var(--text) 88%, var(--accent-a));
            }
            .users-avatar--member {
                background: color-mix(in srgb, var(--accent-c) 20%, var(--card-strong));
                color: color-mix(in srgb, var(--text) 82%, var(--accent-c));
            }
            .users-namecell__text { min-width: 0; }
            .users-namecell__name { font-weight: 650; }
            .users-namecell__hint { font-size: 12px; color: var(--muted); margin-top: 2px; }
            .users-pill {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 4px 10px;
                border-radius: 999px;
                font-size: 12px;
                font-weight: 650;
                border: 1px solid var(--border);
            }
            .users-pill--admin {
                border-color: color-mix(in srgb, var(--accent-a) 38%, var(--border));
                background: color-mix(in srgb, var(--accent-a) 10%, transparent);
                color: color-mix(in srgb, var(--text) 85%, var(--accent-a));
            }
            .users-pill--member {
                border-color: color-mix(in srgb, var(--accent-c) 35%, var(--border));
                background: color-mix(in srgb, var(--accent-c) 10%, transparent);
                color: color-mix(in srgb, var(--text) 82%, var(--accent-c));
            }
            .users-pill--ok {
                border-color: color-mix(in srgb, var(--accent-c) 40%, var(--border));
                color: var(--accent-c);
                font-weight: 700;
            }
            .users-pill--off {
                border-color: color-mix(in srgb, #dc2626 35%, var(--border));
                color: #dc2626;
                font-weight: 700;
            }
            .users-actions {
                display: inline-flex;
                flex-wrap: wrap;
                gap: 6px;
                align-items: center;
                justify-content: flex-end;
            }
            .users-actions .users-btn {
                appearance: none;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                height: 34px;
                padding: 0 12px;
                border-radius: 10px;
                border: 1px solid var(--border);
                background: color-mix(in srgb, var(--bg0) 78%, var(--card));
                color: var(--text);
                font-size: 12px;
                font-weight: 650;
                font-family: inherit;
                cursor: pointer;
                text-decoration: none;
                transition: background 0.12s ease;
            }
            .users-actions .users-btn:hover { background: color-mix(in srgb, var(--bg0) 65%, var(--card-strong)); text-decoration: none; }
            .users-actions .users-btn--primary {
                border-color: color-mix(in srgb, var(--accent-a) 35%, var(--border));
                background: color-mix(in srgb, var(--accent-a) 12%, var(--card-strong));
            }
            .users-actions .users-btn--warn {
                border-color: color-mix(in srgb, #d97706 40%, var(--border));
                background: color-mix(in srgb, #d97706 10%, transparent);
                color: color-mix(in srgb, var(--text) 75%, #b45309);
            }
            .users-actions .users-btn--danger {
                border-color: color-mix(in srgb, #dc2626 38%, var(--border));
                background: color-mix(in srgb, #dc2626 8%, transparent);
                color: #b91c1c;
            }
            .users-actions .users-btn svg { width: 15px; height: 15px; flex-shrink: 0; opacity: 0.9; }
            .users-actions form { margin: 0; display: inline; }
            .users-actions--self { justify-content: flex-end; }
            .users-ico-audit { color: var(--muted); }
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
                                <div class="bridge-brand__tagline">Usuários</div>
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
                            <div class="bridge-panel__head users-panel-head">
                                <div class="users-panel-head__titles">
                                    <div class="bridge-panel__title">Usuários</div>
                                    <div class="bridge-panel__meta">contas do painel • perfil admin ou só integrações • ativar / desativar</div>
                                </div>
                                <div class="users-toolbar">
                                    <a class="bridge-btn bridge-btn--primary" href="{{ route('users.create') }}">Novo usuário</a>
                                    <a class="bridge-btn" href="{{ route('admin.user-audit-logs.index') }}">Auditoria de ações</a>
                                </div>
                            </div>
                            @if (session('status'))
                                <p class="bridge-muted" style="margin-top: 12px;"><strong>{{ session('status') }}</strong></p>
                            @endif
                            @error('user')
                                <p class="bridge-error" style="margin-top: 12px;">{{ $message }}</p>
                            @enderror
                            <div style="margin-top: 16px; overflow: auto;">
                                <table class="users-t">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>Usuário</th>
                                            <th>E-mail</th>
                                            <th>Perfil</th>
                                            <th>Estado</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($users as $u)
                                            @php
                                                $isSelf = (int) $u->getKey() === (int) auth()->id();
                                                $initial = mb_strtoupper(mb_substr((string) ($u->name ?: $u->username), 0, 1));
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="users-namecell">
                                                        <div class="users-avatar {{ $u->is_admin ? 'users-avatar--admin' : 'users-avatar--member' }}" aria-hidden="true">{{ $initial }}</div>
                                                        <div class="users-namecell__text">
                                                            <div class="users-namecell__name">{{ $u->name }}</div>
                                                            @if ($isSelf)
                                                                <div class="users-namecell__hint">Sua conta</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="mono">{{ $u->username }}</td>
                                                <td>{{ $u->email }}</td>
                                                <td>
                                                    @if ($u->is_admin)
                                                        <span class="users-pill users-pill--admin" title="Acesso completo ao painel admin">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                                            Administrador
                                                        </span>
                                                    @else
                                                        <span class="users-pill users-pill--member" title="Configurações e integrações, sem rotas admin">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                                                            Integrações
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($u->isActive())
                                                        <span class="users-pill users-pill--ok">Ativo</span>
                                                    @else
                                                        <span class="users-pill users-pill--off">Desativado</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($isSelf)
                                                        <div class="users-actions users-actions--self">
                                                            <a class="users-btn users-ico-audit" href="{{ route('admin.user-audit-logs.index', ['audit_user_id' => $u->getKey()]) }}">Minha auditoria</a>
                                                        </div>
                                                    @else
                                                        <div class="users-actions">
                                                            <a class="users-btn users-ico-audit" href="{{ route('admin.user-audit-logs.index', ['audit_user_id' => $u->getKey()]) }}" title="Auditoria deste usuário">
                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                                                Auditoria
                                                            </a>
                                                            @if ($u->is_admin)
                                                                <form method="POST" action="{{ route('users.demote-admin', $u) }}" onsubmit="return confirm('Rebaixar este usuário para acesso só a integrações?');">
                                                                    @csrf
                                                                    <button type="submit" class="users-btn users-btn--warn" title="Remove perfil de administrador">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4"/><path d="M12 17h.01"/><circle cx="12" cy="12" r="10"/></svg>
                                                                        Rebaixar admin
                                                                    </button>
                                                                </form>
                                                            @else
                                                                <form method="POST" action="{{ route('users.promote-admin', $u) }}" onsubmit="return confirm('Promover este usuário a administrador? Ele terá acesso completo ao painel.');">
                                                                    @csrf
                                                                    <button type="submit" class="users-btn users-btn--primary" title="Concede perfil de administrador">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                                                                        Tornar admin
                                                                    </button>
                                                                </form>
                                                            @endif
                                                            @if ($u->isActive())
                                                                <form method="POST" action="{{ route('users.deactivate', $u) }}" onsubmit="return confirm('Desativar este usuário? Ele não poderá mais entrar.');">
                                                                    @csrf
                                                                    <button type="submit" class="users-btn users-btn--danger">Desativar</button>
                                                                </form>
                                                            @else
                                                                <form method="POST" action="{{ route('users.reactivate', $u) }}">
                                                                    @csrf
                                                                    <button type="submit" class="users-btn users-btn--primary">Reativar</button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="bridge-form__actions" style="margin-top: 18px;">
                                <a class="bridge-btn" href="{{ url('/dashboard') }}">Voltar</a>
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
