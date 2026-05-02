<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Novo usuário • {{ config('app.name', 'Bridge ERP') }}</title>
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
            .bridge-password-row { display: flex; gap: 10px; align-items: stretch; }
            .bridge-password-row .bridge-input { flex: 1; min-width: 0; }
            .bridge-password-row .bridge-btn--icon { flex-shrink: 0; min-width: 92px; font-size: 13px; }
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
                                <div class="bridge-brand__tagline">Novo usuário</div>
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
                                <div class="bridge-panel__title">Criar usuário</div>
                                <div class="bridge-panel__meta">admin ou acesso só a integrações</div>
                            </div>
                            <form method="POST" action="{{ route('users.store') }}" class="bridge-form" style="margin-top: 14px;">
                                @csrf
                                <div class="bridge-field">
                                    <label class="bridge-label" for="name">Nome</label>
                                    <input class="bridge-input" id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name" />
                                    @error('name')<div class="bridge-error">{{ $message }}</div>@enderror
                                </div>
                                <div class="bridge-field">
                                    <label class="bridge-label" for="username">Usuário (login)</label>
                                    <input class="bridge-input" id="username" name="username" type="text" value="{{ old('username') }}" required autocomplete="username" />
                                    @error('username')<div class="bridge-error">{{ $message }}</div>@enderror
                                </div>
                                <div class="bridge-field">
                                    <label class="bridge-label" for="email">E-mail</label>
                                    <input class="bridge-input" id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" />
                                    @error('email')<div class="bridge-error">{{ $message }}</div>@enderror
                                </div>
                                <div class="bridge-field">
                                    <label class="bridge-label" for="password">Senha</label>
                                    <div class="bridge-password-row">
                                        <input class="bridge-input" id="password" name="password" type="password" required autocomplete="new-password" />
                                        <button type="button" class="bridge-btn bridge-btn--icon" data-password-toggle="password" aria-pressed="false" aria-controls="password" title="Mostrar ou ocultar senha">Mostrar</button>
                                    </div>
                                    @error('password')<div class="bridge-error">{{ $message }}</div>@enderror
                                </div>
                                <div class="bridge-field">
                                    <label class="bridge-label" for="password_confirmation">Confirmar senha</label>
                                    <div class="bridge-password-row">
                                        <input class="bridge-input" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" />
                                        <button type="button" class="bridge-btn bridge-btn--icon" data-password-toggle="password_confirmation" aria-pressed="false" aria-controls="password_confirmation" title="Mostrar ou ocultar senha">Mostrar</button>
                                    </div>
                                </div>
                                <label class="bridge-check" style="margin-top: 10px;">
                                    <input type="checkbox" name="is_admin" value="1" {{ old('is_admin') ? 'checked' : '' }} />
                                    <span>Administrador (acesso completo ao painel)</span>
                                </label>
                                @error('is_admin')<div class="bridge-error">{{ $message }}</div>@enderror
                                <div class="bridge-form__actions" style="margin-top: 18px;">
                                    <button type="submit" class="bridge-btn bridge-btn--primary">Criar</button>
                                    <a class="bridge-btn" href="{{ route('users.index') }}">Cancelar</a>
                                </div>
                            </form>
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
        <script>
            (function () {
                document.querySelectorAll('[data-password-toggle]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var id = btn.getAttribute('data-password-toggle');
                        var input = id ? document.getElementById(id) : null;
                        if (!input) return;
                        var show = input.type === 'password';
                        input.type = show ? 'text' : 'password';
                        btn.textContent = show ? 'Ocultar' : 'Mostrar';
                        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
                    });
                });
            })();
        </script>
    </body>
</html>
