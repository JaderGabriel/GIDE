@auth
    @php
        $u = auth()->user();
        $isAdmin = (bool) $u->is_admin;
        $roleTitle = $isAdmin ? 'Administrador' : 'Acesso a integrações';
        $roleTriggerClass = $isAdmin ? 'bridge-user-menu__role-trigger--admin' : 'bridge-user-menu__role-trigger--integration';
        $rolePanelClass = $isAdmin ? 'bridge-user-menu__role--admin' : 'bridge-user-menu__role--integration';
    @endphp
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
            <button type="button" class="bridge-btn bridge-user-menu__trigger" data-user-menu-trigger aria-expanded="false" aria-haspopup="true" title="{{ $roleTitle }}" aria-label="Menu da conta: {{ $u->name ?: $u->username }}, {{ $roleTitle }}">
                <span class="bridge-user-menu__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr((string) ($u->name ?: $u->username), 0, 1)) }}</span>
                <span class="bridge-user-menu__role-trigger {{ $roleTriggerClass }}" aria-hidden="true" title="{{ $roleTitle }}">
                    @if ($isAdmin)
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    @endif
                </span>
                <span class="bridge-user-menu__name">{{ $u->name ?: $u->username }}</span>
                <svg class="bridge-user-menu__chev" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <div class="bridge-user-menu__panel" data-user-menu-panel hidden role="menu">
                <div class="bridge-user-menu__meta">
                    <div class="bridge-user-menu__meta-line"><strong>{{ $u->name }}</strong></div>
                    <div class="bridge-user-menu__meta-line bridge-muted mono" style="font-size:12px;">{{ $u->username }}</div>
                    <div class="bridge-user-menu__meta-line bridge-muted" style="font-size:12px;">{{ $u->email }}</div>
                    <div class="bridge-user-menu__meta-line bridge-user-menu__role-row" style="margin-top:10px;">
                        <span class="bridge-user-menu__role {{ $rolePanelClass }}" title="{{ $roleTitle }}">
                            <span class="bridge-sr-only">Tipo de acesso: {{ $roleTitle }}</span>
                            @if ($isAdmin)
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                            @endif
                        </span>
                        <a href="{{ url('/') }}" class="bridge-user-menu__home-icon" role="menuitem" title="Início" aria-label="Início">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        </a>
                    </div>
                </div>
                @if ($isAdmin)
                    <div class="bridge-user-menu__sub" role="group" aria-labelledby="bridge-user-menu-users-label">
                        <div class="bridge-user-menu__sub-label" id="bridge-user-menu-users-label">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <span>Usuários</span>
                        </div>
                        <a href="{{ route('users.index') }}" class="bridge-user-menu__item bridge-user-menu__item--sub bridge-user-menu__item--row" role="menuitem">
                            <span class="bridge-user-menu__item-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                            </span>
                            <span>Gerenciar</span>
                        </a>
                        <a href="{{ route('admin.user-audit-logs.index') }}" class="bridge-user-menu__item bridge-user-menu__item--sub bridge-user-menu__item--row" role="menuitem">
                            <span class="bridge-user-menu__item-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>
                            </span>
                            <span>Auditoria</span>
                        </a>
                    </div>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="bridge-user-menu__logout">
                    @csrf
                    <button type="submit" class="bridge-user-menu__item bridge-user-menu__item--btn bridge-user-menu__item--row" role="menuitem">
                        <span class="bridge-user-menu__item-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        </span>
                        <span>Sair</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
@endauth
