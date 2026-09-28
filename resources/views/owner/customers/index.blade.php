@extends('layouts.dashboard')
@section('title','Customer')
@section('dashboard')
<div class="page-head"><div><div class="eyebrow">CUSTOMER</div><h1>Data customer</h1></div></div>
<form class="filters"><input class="input" name="q" value="{{ request('q') }}" placeholder="Nama / email / username / WhatsApp / LINE">@include('partials.filter-actions')</form>
<div class="table-wrap"><table class="table"><thead><tr><th>Customer</th><th>Kontak</th><th>Order</th><th>Tagihan</th><th>Status akun</th><th></th></tr></thead><tbody>
@forelse($customers as $c)<tr><td><strong>{{ $c->name }}</strong>@if($c->customerProfile?->legacy_name)<div class="small muted">Legacy: {{ $c->customerProfile->legacy_name }}</div>@endif</td><td>{{ str_ends_with($c->email,'@placeholder.local')?'Belum terhubung akun':$c->email }}<div class="small muted">{{ $c->customerProfile?->whatsapp ?: $c->customerProfile?->line_id ?: '-' }}</div></td><td>{{ $c->orders_count }}</td><td>{{ $c->invoices_count }}</td><td><span class="badge {{ $c->active?'ok':'gray' }}">{{ $c->active?'Aktif':'Nonaktif' }}</span></td><td><a class="btn btn-soft btn-sm" href="{{ route('owner.customers.show',$c) }}">Detail</a></td></tr>@empty<tr><td colspan="6" class="empty">Tidak ada customer yang cocok.</td></tr>@endforelse
</tbody></table></div>@include('partials.pagination',['paginator'=>$customers])
@endsection
