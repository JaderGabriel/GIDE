@php
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator */
    /** @var int $perPage */
    /** @var string $position */
    use App\Support\AdminListPerPage;
    $position = $position ?? 'bottom';
    $pagerPosClass = $position === 'top' ? 'fac-pager--above' : 'fac-pager--below';
    $selectId = 'fac-per-page-'.$position;
@endphp
<div class="fac-pager {{ $pagerPosClass }}">
    <div class="fac-pager__left">
        <span class="fac-pager__meta">
            @if ($paginator->total() === 0)
                Nenhum registro.
            @else
                Mostrando <strong>{{ $paginator->firstItem() }}</strong>–<strong>{{ $paginator->lastItem() }}</strong> de <strong>{{ $paginator->total() }}</strong>
            @endif
        </span>
        <form method="get" action="{{ url()->current() }}" class="fac-pager__form">
            @foreach (request()->except(['per_page', 'page']) as $name => $value)
                @if (! is_array($value))
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach
            <input type="hidden" name="page" value="1">
            <label class="fac-pager__label" for="{{ $selectId }}">Por página</label>
            <select id="{{ $selectId }}" name="per_page" class="fac-pager__select" onchange="this.form.submit()">
                @foreach (AdminListPerPage::ALLOWED as $n)
                    <option value="{{ $n }}" @selected((int) $perPage === $n)>{{ $n }}</option>
                @endforeach
            </select>
        </form>
    </div>
    <div class="fac-pager__links">
        @if ($paginator->hasPages())
            {{ $paginator->onEachSide(1)->links('pagination.fac-admin') }}
        @endif
    </div>
</div>
