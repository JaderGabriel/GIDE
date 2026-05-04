@props([
    /** Sobrescreve a deteção automática: dashboard|facials|events|frequencia|sms|users */
    'auditCurrent' => null,
])

@php
    $current = $auditCurrent ?? match (true) {
        request()->is('dashboard') => 'dashboard',
        request()->routeIs('admin.facial-requests.*') => 'facials',
        request()->routeIs('admin.gestor-access-events.*') => 'events',
        request()->routeIs('admin.ieducar-frequencia-deliveries.*') => 'frequencia',
        request()->routeIs('integrations.ieducar.frequencia-registro*') => 'integrations-freq',
        request()->routeIs('integrations.gide-queues') => 'gide-queues',
        request()->routeIs('sms.*') => 'sms',
        request()->routeIs('admin.user-audit-logs.*') => 'users',
        default => null,
    };

    $items = [
        'dashboard' => [
            'href' => url('/dashboard'),
            'title' => 'Dashboard',
            'class' => 'audit-sc--dashboard',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
        ],
        'facials' => [
            'href' => route('admin.facial-requests.index'),
            'title' => 'Solicitações faciais',
            'class' => 'audit-sc--facials',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2a4 4 0 0 1 4 4v2a4 4 0 0 1-8 0V6a4 4 0 0 1 4-4z"/><path d="M6 22v-2a6 6 0 0 1 12 0v2"/></svg>',
        ],
        'events' => [
            'href' => route('admin.gestor-access-events.index'),
            'title' => 'Access-events (Gestor / catraca)',
            'class' => 'audit-sc--events',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>',
        ],
        'gide-queues' => [
            'href' => route('integrations.gide-queues'),
            'title' => 'Filas GIDE',
            'class' => 'audit-sc--gidequeues',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M4 6h.01M4 12h.01M4 18h.01"/></svg>',
        ],
        'frequencia' => [
            'href' => route('admin.ieducar-frequencia-deliveries.index'),
            'title' => 'Fila de frequência iEducar',
            'class' => 'audit-sc--frequencia',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/></svg>',
        ],
        'sms' => [
            'href' => route('sms.index'),
            'title' => 'Mensagens SMS',
            'class' => 'audit-sc--sms',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>',
        ],
        'users' => [
            'href' => route('admin.user-audit-logs.index'),
            'title' => 'Auditoria de utilizadores',
            'class' => 'audit-sc--users',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>',
        ],
    ];
@endphp

@once
    <style>
        .audit-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px 14px;
            width: 100%;
            box-sizing: border-box;
        }
        .audit-toolbar__left {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            min-width: 0;
            flex: 1 1 auto;
        }
        .audit-toolbar__shortcuts {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            flex: 0 0 auto;
            margin-left: auto;
        }
        .audit-sc {
            display: inline-flex;
            width: 40px;
            height: 40px;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            border: 1px solid var(--border);
            text-decoration: none;
            color: inherit;
            flex-shrink: 0;
            transition: background 0.14s ease, border-color 0.14s ease, transform 0.08s ease, box-shadow 0.14s ease;
        }
        .audit-sc:hover {
            text-decoration: none;
            transform: translateY(-1px);
        }
        .audit-sc:active { transform: translateY(0); }
        .audit-sc svg {
            width: 20px;
            height: 20px;
            pointer-events: none;
        }
        .audit-sc--dashboard {
            background: color-mix(in srgb, var(--accent-a) 12%, var(--surface-1));
            border-color: color-mix(in srgb, var(--accent-a) 38%, var(--border));
            color: color-mix(in srgb, var(--text) 88%, var(--accent-a));
        }
        .audit-sc--dashboard:hover {
            background: color-mix(in srgb, var(--accent-a) 20%, var(--surface-1));
            border-color: color-mix(in srgb, var(--accent-a) 52%, var(--border));
        }
        .audit-sc--facials {
            background: color-mix(in srgb, #c2410c 11%, var(--surface-1));
            border-color: color-mix(in srgb, #c2410c 42%, var(--border));
            color: color-mix(in srgb, var(--text) 88%, #c2410c);
        }
        .audit-sc--facials:hover {
            background: color-mix(in srgb, #c2410c 18%, var(--surface-1));
            border-color: color-mix(in srgb, #c2410c 55%, var(--border));
        }
        .audit-sc--events {
            background: color-mix(in srgb, #4f46e5 11%, var(--surface-1));
            border-color: color-mix(in srgb, #4f46e5 40%, var(--border));
            color: color-mix(in srgb, var(--text) 86%, #4f46e5);
        }
        .audit-sc--events:hover {
            background: color-mix(in srgb, #4f46e5 18%, var(--surface-1));
            border-color: color-mix(in srgb, #4f46e5 54%, var(--border));
        }
        .audit-sc--gidequeues {
            background: color-mix(in srgb, #7c2d12 10%, var(--surface-1));
            border-color: color-mix(in srgb, #7c2d12 38%, var(--border));
            color: color-mix(in srgb, var(--text) 88%, #7c2d12);
        }
        .audit-sc--gidequeues:hover {
            background: color-mix(in srgb, #7c2d12 16%, var(--surface-1));
            border-color: color-mix(in srgb, #7c2d12 50%, var(--border));
        }
        .audit-sc--frequencia {
            background: color-mix(in srgb, #7c3aed 11%, var(--surface-1));
            border-color: color-mix(in srgb, #7c3aed 40%, var(--border));
            color: color-mix(in srgb, var(--text) 86%, #7c3aed);
        }
        .audit-sc--frequencia:hover {
            background: color-mix(in srgb, #7c3aed 18%, var(--surface-1));
            border-color: color-mix(in srgb, #7c3aed 52%, var(--border));
        }
        .audit-sc--sms {
            background: color-mix(in srgb, #0284c7 11%, var(--surface-1));
            border-color: color-mix(in srgb, #0284c7 40%, var(--border));
            color: color-mix(in srgb, var(--text) 86%, #0284c7);
        }
        .audit-sc--sms:hover {
            background: color-mix(in srgb, #0284c7 18%, var(--surface-1));
            border-color: color-mix(in srgb, #0284c7 52%, var(--border));
        }
        .audit-sc--users {
            background: color-mix(in srgb, #b45309 10%, var(--surface-1));
            border-color: color-mix(in srgb, #b45309 38%, var(--border));
            color: color-mix(in srgb, var(--text) 88%, #b45309);
        }
        .audit-sc--users:hover {
            background: color-mix(in srgb, #b45309 16%, var(--surface-1));
            border-color: color-mix(in srgb, #b45309 50%, var(--border));
        }
        html.dark .audit-sc--users {
            border-color: color-mix(in srgb, #fbbf24 42%, var(--border));
            color: color-mix(in srgb, var(--text) 90%, #fbbf24);
        }
    </style>
@endonce


<div {{ $attributes->class(['audit-toolbar']) }}>
    @if (isset($left) && ! $left->isEmpty())
        <div class="audit-toolbar__left">
            {{ $left }}
        </div>
    @endif
    <nav class="audit-toolbar__shortcuts" aria-label="Atalhos de auditoria">
        @foreach ($items as $id => $lnk)
            @continue($current !== null && $id === $current)
            <a
                class="audit-sc {{ $lnk['class'] }}"
                href="{{ $lnk['href'] }}"
                title="{{ $lnk['title'] }}"
                aria-label="{{ $lnk['title'] }}"
            >{!! $lnk['icon'] !!}</a>
        @endforeach
    </nav>
</div>
