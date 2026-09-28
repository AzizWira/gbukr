<div class="pagination-bar">
    <div class="pagination-meta">
        @if($paginator->total() > 0)
            Menampilkan <strong>{{ $paginator->firstItem() }}</strong>–<strong>{{ $paginator->lastItem() }}</strong> dari <strong>{{ number_format($paginator->total(),0,',','.') }}</strong> data
        @else
            Tidak ada data
        @endif
    </div>

    <div class="pagination-nav">
        @if($paginator->onFirstPage())
            <span class="btn btn-neutral btn-sm disabled" aria-disabled="true">Sebelumnya</span>
        @else
            <a class="btn btn-neutral btn-sm" href="{{ $paginator->previousPageUrl() }}" rel="prev">Sebelumnya</a>
        @endif

        @if($paginator->hasMorePages())
            <a class="btn btn-neutral btn-sm" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya</a>
        @else
            <span class="btn btn-neutral btn-sm disabled" aria-disabled="true">Berikutnya</span>
        @endif
    </div>

    <form class="page-jump" method="get" action="{{ url()->current() }}" data-no-dirty-guard>
        @foreach(request()->except(['page','per_page']) as $key => $value)
            @if(is_scalar($value))
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach

        <span>Halaman</span>
        <input
            class="input page-jump-input"
            type="number"
            name="page"
            min="1"
            max="{{ max(1,$paginator->lastPage()) }}"
            value="{{ max(1,$paginator->currentPage()) }}"
            aria-label="Nomor halaman"
        >
        <span>dari {{ max(1,$paginator->lastPage()) }}</span>

        <select class="select per-page-select" name="per_page" aria-label="Data per halaman">
            @foreach([20,50,100] as $size)
                <option value="{{ $size }}" @selected((int)request('per_page',$paginator->perPage()) === $size)>{{ $size }} / halaman</option>
            @endforeach
        </select>

        <button class="btn btn-soft btn-sm" type="submit">Buka</button>
    </form>
</div>
