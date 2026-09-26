@extends('layouts.dashboard')
@section('title','Dashboard Operasional')
@section('dashboard')
<div class="page-head">
    <div>
        <div class="eyebrow">OPERASIONAL</div>
        <h1>{{ auth()->user()->isOwner()?'Dashboard Owner':'Dashboard Admin' }}</h1>
        <p class="muted">{{ auth()->user()->isOwner()?'Fokus pada hal yang perlu ditangani hari ini dengan tampilan yang lebih rapi dan enak dipantau.':'Akses Admin difokuskan untuk pembaruan status Batch dan tracking.' }}</p>
    </div>
    @if(auth()->user()->isOwner())
        <a class="btn btn-primary" href="{{ route('owner.products.create') }}">Tambah produk</a>
    @endif
</div>

@if(auth()->user()->isOwner())
    <div class="metric-grid">
        <div class="metric"><span>Customer</span><strong>{{ $stats['customers'] }}</strong></div>
        <div class="metric"><span>Total order</span><strong>{{ $stats['orders'] }}</strong></div>
        <div class="metric"><span>Tagihan aktif</span><strong>{{ $stats['unpaid'] }}</strong></div>
        <div class="metric kpi-accent"><span>Bayar menunggu cek</span><strong>{{ $stats['pendingPayments'] }}</strong></div>
        <div class="metric"><span>Batch aktif</span><strong>{{ $stats['activeBatches'] }}</strong></div>
        <div class="metric"><span>PO terbuka</span><strong>{{ $stats['openPo'] }}</strong></div>
        <div class="metric"><span>Unclaimed</span><strong>{{ $stats['unclaimed'] }}</strong></div>
    </div>

    <div class="quick-actions">
        <a class="quick-action" href="{{ route('owner.batches.index') }}"><strong>Kelola Batch</strong><span class="muted small">Update batch dan cek item yang sedang berjalan.</span></a>
        <a class="quick-action" href="{{ route('owner.payments.index',['status'=>'pending']) }}"><strong>Cek pembayaran</strong><span class="muted small">Lihat bukti transfer yang menunggu verifikasi.</span></a>
        <a class="quick-action" href="{{ route('owner.master.index') }}"><strong>Atur rate &amp; fee</strong><span class="muted small">Ubah rate, shipping option, dan fee admin per negara.</span></a>
    </div>

    <div class="grid grid-2" style="margin-top:20px">
        <div class="card card-blue">
            <div class="section-head"><h3>Order terbaru</h3><a class="small" href="{{ route('owner.orders.index') }}">Semua order</a></div>
            @forelse($orders as $o)
                <div class="summary-row">
                    <div>
                        <strong>{{ $o->order_number }}</strong>
                        <div class="small muted">{{ $o->customer->name }}</div>
                    </div>
                    <span class="badge status-colored" style="--status-color:{{ \App\Services\OrderStatusService::color($o->status) }}">{{ \App\Services\OrderStatusService::label($o->status) }}</span>
                </div>
            @empty
                <div class="empty">Belum ada order.</div>
            @endforelse
        </div>
        <div class="card card-pink">
            <div class="section-head"><h3>Pembayaran menunggu</h3><a class="small" href="{{ route('owner.payments.index',['status'=>'pending']) }}">Semua pembayaran</a></div>
            @forelse($payments as $p)
                <div class="summary-row">
                    <div>
                        <strong>{{ $p->customer->name }}</strong>
                        <div class="small muted">{{ $p->payment_number }}</div>
                    </div>
                    <span class="money">Rp{{ number_format($p->amount,0,',','.') }}</span>
                </div>
            @empty
                <div class="empty">Tidak ada yang menunggu verifikasi.</div>
            @endforelse
        </div>
    </div>
@else
    <div class="metric-grid">
        <div class="metric"><span>Batch aktif</span><strong>{{ $stats['activeBatches'] }}</strong></div>
        <div class="metric"><span>Total order</span><strong>{{ $stats['orders'] }}</strong></div>
        <div class="metric"><span>Unclaimed</span><strong>{{ $stats['unclaimed'] }}</strong></div>
    </div>
    <div class="grid grid-2">
        <a class="card card-mint" href="{{ route('owner.batches.index') }}"><div class="eyebrow">BATCH</div><h3>Perbarui status Batch</h3><p class="muted">Update status bersama untuk order dalam Batch yang sama.</p></a>
        <a class="card card-lilac" href="{{ route('owner.tracking.index') }}"><div class="eyebrow">TRACKING</div><h3>Perbarui tracking</h3><p class="muted">Kelola tracking number dan status yang ditampilkan ke customer.</p></a>
    </div>
@endif
@endsection
