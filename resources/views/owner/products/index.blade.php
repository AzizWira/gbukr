@extends('layouts.dashboard')
@section('title', 'Produk & PO')

@section('dashboard')
<div class="page-head">
    <div>
        <div class="eyebrow">PRODUK</div>
        <h1>PO &amp; Ready Stock</h1>
    </div>
    <a class="btn btn-primary" href="{{ route('owner.products.create') }}">Tambah produk</a>
</div>

<form class="filters">
    <input class="input" name="q" value="{{ request('q') }}" placeholder="Cari produk">
    <select class="select" name="type">
        <option value="">Semua</option>
        <option value="po" @selected(request('type') === 'po')>PO</option>
        <option value="ready" @selected(request('type') === 'ready')>Ready Stock</option>
    </select>
    @include('partials.filter-actions',['submitLabel'=>'Filter'])
</form>

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Produk</th>
                <th>Jenis</th>
                <th>Negara</th>
                <th>Rate / Fee</th>
                <th>Variasi</th>
                <th>PO</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $p)
                <tr>
                    <td><strong>{{ $p->name }}</strong></td>
                    <td>{{ strtoupper($p->type) }}</td>
                    <td>{{ $p->country->name }}</td>
                    <td><strong>{{ rtrim(rtrim(number_format((float)$p->country->rate,4,',','.'),'0'),',') }}</strong><div class="small muted">Fee Rp{{ number_format((int)$p->item_fee_idr,0,',','.') }}{{ $p->free_shipping ? ' · Free Shipping' : '' }}</div></td>
                    <td>{{ $p->variants->count() }}</td>
                    <td>
                        @if($p->preorder)
                            {{ $p->preorder->close_at?->translatedFormat('d F Y, H.i') }}
                        @else
                            -
                        @endif
                    </td>
                    <td><span class="badge {{ $p->active ? 'ok' : 'gray' }}">{{ $p->active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td><a class="btn btn-soft btn-sm" href="{{ route('owner.products.edit', $p) }}">Atur</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty">Belum ada produk.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination">{{ $products->links() }}</div>
@endsection
