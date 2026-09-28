@if(request('per_page'))
    <input type="hidden" name="per_page" value="{{ (int) request('per_page') }}">
@endif
<div class="filter-actions">
    <button class="btn btn-primary" type="submit">{{ $submitLabel ?? 'Cari' }}</button>
    @if(request()->query())
        <a class="btn btn-neutral" href="{{ url()->current() }}" data-no-dirty-guard>Clear</a>
    @endif
</div>
