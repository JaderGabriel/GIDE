function getPreferredTheme() {
    const stored = localStorage.getItem('theme');
    if (stored === 'light' || stored === 'dark') return stored;
    return window.matchMedia?.('(prefers-color-scheme: dark)')?.matches ? 'dark' : 'light';
}

function applyTheme(theme) {
    const root = document.documentElement;
    root.classList.toggle('dark', theme === 'dark');
    root.dataset.theme = theme;
}

function initThemeToggle() {
    applyTheme(getPreferredTheme());

    const btn = document.querySelector('[data-theme-toggle]');
    if (!btn) return;

    const setLabel = () => {
        const isDark = document.documentElement.classList.contains('dark');
        btn.setAttribute('aria-pressed', String(isDark));
        btn.setAttribute('title', isDark ? 'Mudar para tema claro' : 'Mudar para tema escuro');
    };

    setLabel();

    btn.addEventListener('click', () => {
        const isDark = document.documentElement.classList.contains('dark');
        const next = isDark ? 'light' : 'dark';
        localStorage.setItem('theme', next);
        applyTheme(next);
        setLabel();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initThemeToggle();
});
