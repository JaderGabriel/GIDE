{{-- Alinhado à visão geral de integrações: largura do painel, títulos de secção e hero de página. --}}
<style>
    .integr-app .bridge-container { max-width: 1400px; }
    .integr-app .bridge-auth { max-width: none; }
    .integr-app .bridge-panel { width: 100%; }

    .integr-section__title { font-weight: 850; font-size: 15px; letter-spacing: -0.02em; margin: 0; line-height: 1.25; color: var(--text); }
    .integr-section__lead { margin: 6px 0 0; font-size: 13px; color: var(--muted); line-height: 1.5; max-width: 960px; }
    .integr-section-card { border: 1px solid var(--border); border-radius: 18px; background: var(--card-strong); box-shadow: var(--shadow-soft); padding: 16px 16px 14px; }
    .integr-section-card .row { align-items: flex-start; }

    .integr-page-hero { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 16px; margin-top: 4px; }
    .integr-page-hero__main { flex: 1 1 320px; min-width: 0; }
    .integr-page-hero__actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: flex-end; flex: 0 1 auto; min-width: min(100%, 360px); }

    .integr-app .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 12px; }
</style>
