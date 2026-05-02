(function () {
    function getPreferredTheme() {
        try {
            const stored = localStorage.getItem('theme');
            if (stored === 'light' || stored === 'dark') return stored;
        } catch (_) {}
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function applyTheme(theme) {
        const root = document.documentElement;
        root.classList.toggle('dark', theme === 'dark');
        root.dataset.theme = theme;
    }

    function syncButton(btn) {
        const isDark = document.documentElement.classList.contains('dark');
        btn.setAttribute('aria-pressed', String(isDark));
        btn.setAttribute('title', isDark ? 'Mudar para tema claro' : 'Mudar para tema escuro');
    }

    document.addEventListener('DOMContentLoaded', () => {
        applyTheme(getPreferredTheme());

        const buttons = Array.from(document.querySelectorAll('[data-theme-toggle]'));
        buttons.forEach(syncButton);

        const onToggle = () => {
            const isDark = document.documentElement.classList.contains('dark');
            const next = isDark ? 'light' : 'dark';
            try {
                localStorage.setItem('theme', next);
            } catch (_) {}
            applyTheme(next);
            buttons.forEach(syncButton);
        };

        buttons.forEach((btn) => btn.addEventListener('click', onToggle));

        document.querySelectorAll('[data-user-menu]').forEach((root) => {
            const trigger = root.querySelector('[data-user-menu-trigger]');
            const panel = root.querySelector('[data-user-menu-panel]');
            if (!trigger || !panel) return;

            const close = () => {
                root.classList.remove('is-open');
                panel.hidden = true;
                trigger.setAttribute('aria-expanded', 'false');
            };

            const open = () => {
                root.classList.add('is-open');
                panel.hidden = false;
                trigger.setAttribute('aria-expanded', 'true');
            };

            const toggle = () => {
                if (panel.hidden) {
                    open();
                } else {
                    close();
                }
            };

            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                toggle();
            });

            document.addEventListener('click', (e) => {
                if (!root.contains(e.target)) {
                    close();
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    close();
                }
            });
        });
    });
})();
