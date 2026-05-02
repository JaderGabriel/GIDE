@php
    $e = $entry ?? null;
    $auto = is_array($e) && ! empty($e['auto']);
    $okLabel = $auto ? 'Preview API: OK' : 'Último teste: OK';
    $badLabel = $auto ? 'Preview API: falha' : 'Último teste: falha';
@endphp
<div class="int-lane__status" role="status" aria-live="polite">
    @if (! is_array($e))
        <span class="int-lane__badge int-lane__badge--pending">
            <svg class="int-lane__badge-ico" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Sem teste manual
        </span>
    @elseif (! empty($e['ok']))
        <span class="int-lane__badge int-lane__badge--ok">
            <svg class="int-lane__badge-ico" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
            {{ $okLabel }}
        </span>
        @if (! empty($e['tested_at_short']))
            <span class="int-lane__when mono">{{ $e['tested_at_short'] }}{{ $auto ? ' · cache 15 min' : '' }}</span>
        @endif
    @else
        <span class="int-lane__badge int-lane__badge--bad">
            <svg class="int-lane__badge-ico" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            {{ $badLabel }}
        </span>
        @if (! empty($e['tested_at_short']))
            <span class="int-lane__when mono">{{ $e['tested_at_short'] }}{{ $auto ? ' · cache 15 min' : '' }}</span>
        @endif
    @endif
</div>
