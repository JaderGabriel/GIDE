@auth
    <div class="bridge-actions bridge-header__end">
        @if ($showThemeToggle ?? true)
            <button type="button" class="bridge-btn bridge-iconbtn" data-theme-toggle aria-pressed="false" title="Mudar tema">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 18a6 6 0 1 0 0-12 6 6 0 0 0 0 12Z" stroke="currentColor" stroke-width="2"/>
                    <path d="M12 2v2M12 20v2M4 12H2M22 12h-2M5 5l1.5 1.5M17.5 17.5 19 19M19 5l-1.5 1.5M6.5 17.5 5 19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        @endif

        <div class="bridge-user-menu" data-user-menu>
            <button type="button" class="bridge-btn bridge-user-menu__trigger" data-user-menu-trigger aria-expanded="false" aria-haspopup="true">
                <span class="bridge-user-menu__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr((string) (auth()->user()->name ?: auth()->user()->username), 0, 1)) }}</span>
                <span class="bridge-user-menu__name">{{ auth()->user()->name ?: auth()->user()->username }}</span>
                <svg class="bridge-user-menu__chev" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <div class="bridge-user-menu__panel" data-user-menu-panel hidden role="menu">
                <div class="bridge-user-menu__meta">
                    <div class="bridge-user-menu__meta-line"><strong>{{ auth()->user()->name }}</strong></div>
                    <div class="bridge-user-menu__meta-line bridge-muted mono" style="font-size:12px;">{{ auth()->user()->username }}</div>
                    <div class="bridge-user-menu__meta-line bridge-muted" style="font-size:12px;">{{ auth()->user()->email }}</div>
                    <div class="bridge-user-menu__meta-line" style="margin-top:8px;">
                        @if (auth()->user()->is_admin)
                            <span class="bridge-chip" style="font-size:11px;">Administrador</span>
                        @else
                            <span class="bridge-chip" style="font-size:11px;">Acesso integrações</span>
                        @endif
                    </div>
                </div>
                <a href="{{ url('/') }}" class="bridge-user-menu__item" role="menuitem">Início</a>
                @if (auth()->user()->is_admin)
                    <div class="bridge-user-menu__sub" role="group" aria-labelledby="bridge-user-menu-users-label">
                        <div class="bridge-user-menu__sub-label" id="bridge-user-menu-users-label">Usuários</div>
                        <a href="{{ route('users.index') }}" class="bridge-user-menu__item bridge-user-menu__item--sub" role="menuitem">Gerenciar</a>
                        <a href="{{ route('admin.user-audit-logs.index') }}" class="bridge-user-menu__item bridge-user-menu__item--sub" role="menuitem">Auditoria</a>
                    </div>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="bridge-user-menu__logout">
                    @csrf
                    <button type="submit" class="bridge-user-menu__item bridge-user-menu__item--btn" role="menuitem">Sair</button>
                </form>
            </div>
        </div>
    </div>
@endauth
