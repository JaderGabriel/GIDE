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
        if (buttons.length === 0) return;
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
    });
})();
