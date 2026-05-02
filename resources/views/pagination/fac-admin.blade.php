@if ($paginator->hasPages())
    <nav class="fac-pagination" role="navigation" aria-label="Paginação">
        <ul class="fac-pagination__list">
            @foreach ($paginator->linkCollection() as $link)
                @if ($link['url'])
                    <li>
                        <a class="fac-pagination__link{{ ! empty($link['active']) ? ' fac-pagination__link--active' : '' }}" href="{{ $link['url'] }}">{!! $link['label'] !!}</a>
                    </li>
                @else
                    <li><span class="fac-pagination__gap" aria-hidden="true">{!! $link['label'] !!}</span></li>
                @endif
            @endforeach
        </ul>
    </nav>
@endif
