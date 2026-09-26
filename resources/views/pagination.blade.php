@if ($paginator->hasPages())
<nav class="pagination-bar" aria-label="Pagination">
    <div class="pagination-nav">
        @if ($paginator->onFirstPage())
            <span class="btn btn-neutral btn-sm is-disabled">Sebelumnya</span>
        @else
            <a class="btn btn-neutral btn-sm" href="{{ $paginator->previousPageUrl() }}">Sebelumnya</a>
        @endif

        @if ($paginator->hasMorePages())
            <a class="btn btn-neutral btn-sm" href="{{ $paginator->nextPageUrl() }}">Berikutnya</a>
        @else
            <span class="btn btn-neutral btn-sm is-disabled">Berikutnya</span>
        @endif
    </div>

    <form method="get" action="{{ url()->current() }}" class="page-jump" data-no-dirty-guard>
        @foreach(request()->except('page') as $key => $value)
            @if(is_array($value))
                @foreach($value as $item)
                    <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                @endforeach
            @else
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        <span class="small muted">Halaman</span>
        <input class="input page-jump-input" type="number" name="page" min="1" max="{{ $paginator->lastPage() }}" value="{{ $paginator->currentPage() }}" required inputmode="numeric" aria-label="Nomor halaman">
        <span class="small muted">dari {{ $paginator->lastPage() }}</span>
        <button class="btn btn-soft btn-sm" type="submit">Buka</button>
    </form>
</nav>
@endif
