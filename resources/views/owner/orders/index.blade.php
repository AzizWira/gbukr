@extends('layouts.dashboard')
@section('title','Order')
@section('dashboard')
<div class="page-head"><div><div class="eyebrow">ORDER</div><h1>Semua order</h1><p class="muted">PO, Batch, dan Ready Stock dalam satu daftar.</p></div></div>
<form class="filters"><input class="input" name="q" value="{{ request('q') }}" placeholder="Order / customer / barang / batch / GO"><select class="select" name="status"><option value="">Semua status</option>@foreach(\App\Services\OrderStatusService::statuses() as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ \App\Services\OrderStatusService::label($s) }}</option>@endforeach</select>@include('partials.filter-actions',['submitLabel'=>'Filter'])</form>
<div class="table-wrap"><table class="table"><thead><tr><th>Order</th><th>Customer</th><th>Barang</th><th>Sumber</th><th>GO/Batch</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($orders as $o)<tr><td><strong>{{ $o->order_number }}</strong><div class="small muted">{{ $o->created_at->translatedFormat('d F Y') }}</div></td><td>{{ $o->customer->name }}</td><td>{{ $o->items->pluck('item_name')->join(', ') }}</td><td>{{ strtoupper($o->source_type) }}</td><td>{{ $o->batch?->code ?: $o->goGroup?->name ?: '-' }}</td><td><span class="badge status-colored" style="--status-color:{{ \App\Services\OrderStatusService::color($o->status) }}">{{ \App\Services\OrderStatusService::label($o->status) }}</span></td><td><a class="btn btn-soft btn-sm" href="{{ route('owner.orders.show',$o) }}">Buka</a></td></tr>@empty<tr><td colspan="7" class="empty">Tidak ada order yang cocok.</td></tr>@endforelse
</tbody></table></div><div class="pagination">{{ $orders->links() }}</div>
@endsection
