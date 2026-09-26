@extends('layouts.dashboard')
@section('title','Order Saya')
@section('dashboard')
<div class="page-head"><div><div class="eyebrow">ORDER SAYA</div><h1>Semua barangmu</h1><p class="muted">PO, Batch, dan Ready Stock tersimpan di sini.</p></div></div>
<form class="filters"><input class="input" name="q" value="{{ request('q') }}" placeholder="Order / barang / batch / GO">@include('partials.filter-actions')</form>
<div class="table-wrap"><table class="table"><thead><tr><th>Order</th><th>Barang</th><th>Sumber</th><th>GO / Batch</th><th>Status</th><th>Tanggal</th><th></th></tr></thead><tbody>@forelse($orders as $o)<tr><td><strong>{{ $o->order_number }}</strong></td><td>{{ $o->items->pluck('item_name')->join(', ') }}</td><td>{{ strtoupper($o->source_type) }}</td><td>{{ $o->goGroup?->name ?: ($o->batch?->code ?: '-') }}</td><td><span class="badge status-colored" style="--status-color:{{ \App\Services\OrderStatusService::color($o->status) }}">{{ \App\Services\OrderStatusService::label($o->status) }}</span></td><td>{{ $o->created_at->translatedFormat('d F Y') }}</td><td><a class="btn btn-soft btn-sm" href="{{ route('customer.orders.show',$o) }}">Detail</a></td></tr>@empty<tr><td colspan="7" class="empty">Tidak ada order yang cocok.</td></tr>@endforelse</tbody></table></div><div class="pagination">{{ $orders->links() }}</div>
@endsection
