@extends('layouts.app')
@section('title','Tracking — GBUKPOP x KRJASTIP')
@section('content')
<section class="section"><div class="container">
<div class="section-head"><div><div class="eyebrow">TRACKING PUBLIK</div><h2>Cek perjalanan barang</h2><p>Cari berdasarkan Batch/PO, detail barang, negara, atau tracking number.</p></div></div>
<form class="filters"><input class="input" name="q" value="{{ request('q') }}" placeholder="Contoh: TH-LEG-G3-31 / album / tracking">@include('partials.filter-actions')</form>
<div class="table-wrap"><table class="table"><thead><tr><th>Batch / PO</th><th>Detail Barang</th><th>Keterangan</th><th>Negara</th><th>Tracking Number</th><th>Status</th></tr></thead><tbody>
@forelse($shipments as $s)<tr><td><strong>{{ $s->reference }}</strong></td><td>{{ $s->item_details }}@if($s->info)<div class="small muted">{{ $s->info }}</div>@endif</td><td>{{ $s->description_type }}</td><td>{{ $s->country->name }}</td><td>{{ $s->tracking_number ?: 'Belum tersedia' }}</td><td><span class="badge status-colored" style="--status-color:{{ \App\Services\OrderStatusService::color($s->status) }}">{{ \App\Services\OrderStatusService::label($s->status) }}</span></td></tr>@empty<tr><td colspan="6" class="empty">Tidak ada tracking yang cocok.</td></tr>@endforelse
</tbody></table></div><div class="pagination">{{ $shipments->links() }}</div>
</div></section>
@endsection
