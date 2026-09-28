@extends('layouts.dashboard')
@section('title','Dashboard Saya')
@section('dashboard')
<div class="page-head">
    <div>
        <div class="eyebrow">HALO, {{ strtoupper(strtok(auth()->user()->name,' ')) }}</div>
        <h1>Ringkasan akunmu</h1>
        <p class="muted">Semua order, tagihan, dan pembayaranmu diringkas di sini biar lebih gampang dicek.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('catalog.index') }}">Cari barang</a>
</div>

<div class="metric-grid">
    <div class="metric"><span>Total order</span><strong>{{ $summary['orders'] }}</strong></div>
    <div class="metric"><span>Tagihan aktif</span><strong>{{ $summary['unpaid'] }}</strong></div>
    <div class="metric"><span>Menunggu verifikasi</span><strong>{{ $summary['pending'] }}</strong></div>
    <div class="metric kpi-accent"><span>Sisa tagihan</span><strong style="font-size:1.2rem">Rp{{ number_format($summary['outstanding'],0,',','.') }}</strong></div>
</div>

@if($deletionRequests->isNotEmpty())
<div class="card" style="margin-bottom:18px;border-color:#f3c5cf">
    <div class="section-head"><div><div class="eyebrow">PERLU TINDAKAN</div><h3>Persetujuan penghapusan order</h3><p>Owner meminta persetujuanmu sebelum menghapus order yang mempunyai histori pembayaran.</p></div><span class="badge warn">{{ $deletionRequests->count() }} menunggu</span></div>
    @foreach($deletionRequests as $request)
        <div class="summary-row"><div><strong>{{ $request->snapshot['order_number'] ?? 'Order' }}</strong><div class="small muted">{{ $request->reason ?: 'Tanpa alasan' }}</div></div><a class="btn btn-danger btn-sm" href="{{ route('customer.deletion-requests.show',$request) }}">Tinjau</a></div>
    @endforeach
</div>
@endif

<div class="quick-actions">
    <a class="quick-action" href="{{ route('customer.orders.index') }}">
        <strong>Lihat order</strong>
        <span class="muted small">Cek semua order yang pernah kamu buat.</span>
    </a>
    <a class="quick-action" href="{{ route('customer.invoices.index') }}">
        <strong>Buka tagihan</strong>
        <span class="muted small">Lihat tagihan aktif dan sisa pembayaranmu.</span>
    </a>
    <a class="quick-action" href="{{ route('customer.profile') }}">
        <strong>Lengkapi profil</strong>
        <span class="muted small">Perbarui kontak yang biasa kamu pakai untuk take.</span>
    </a>
</div>

<div class="grid grid-2" style="margin-top:20px">
    <div class="card card-blue">
        <div class="section-head"><div><h3>Order terbaru</h3></div><a class="small" href="{{ route('customer.orders.index') }}">Lihat semua</a></div>
        @forelse($orders as $o)
            <div class="summary-row">
                <div>
                    <strong>{{ $o->order_number }}</strong>
                    <div class="small muted">{{ $o->items->first()?->item_name ?? 'Order' }} · {{ strtoupper($o->source_type) }}</div>
                </div>
                <span class="badge">{{ str_replace('_',' ',$o->status) }}</span>
            </div>
        @empty
            <div class="empty">Belum ada order.</div>
        @endforelse
    </div>
    <div class="card card-pink">
        <div class="section-head"><div><h3>Tagihan terbaru</h3></div><a class="small" href="{{ route('customer.invoices.index') }}">Lihat semua</a></div>
        @forelse($invoices as $i)
            <div class="summary-row">
                <div>
                    <strong>{{ $i->invoice_number }}</strong>
                    <div class="small muted">{{ strtoupper($i->type) }} · {{ $i->deadline_at?'Batas '.$i->deadline_at->translatedFormat('d F Y'):'Tanpa deadline' }}</div>
                </div>
                <div class="money">Rp{{ number_format($i->outstanding(),0,',','.') }}</div>
            </div>
        @empty
            <div class="empty">Belum ada tagihan.</div>
        @endforelse
    </div>
</div>
@endsection
