@extends('layouts.dashboard')
@section('title','Dashboard Operasional')
@section('dashboard')
<div class="page-head">
    <div>
        <div class="eyebrow">OPERASIONAL</div>
        <h1>{{ auth()->user()->isOwner() ? 'Dashboard Owner' : 'Dashboard Admin' }}</h1>
        <p class="muted">{{ auth()->user()->isOwner() ? 'Prioritaskan pekerjaan yang perlu ditangani hari ini, bukan sekadar melihat angka.' : 'Akses Admin difokuskan untuk pembaruan status Batch dan tracking.' }}</p>
    </div>
    @if(auth()->user()->isOwner())
        <a class="btn btn-primary" href="{{ route('owner.products.create') }}">Tambah produk</a>
    @endif
</div>

@if(auth()->user()->isOwner())
    <div class="attention-grid">
        <a class="attention-card {{ $stats['pendingPayments'] ? 'needs-action' : '' }}" href="{{ route('owner.payments.index',['status'=>'pending']) }}">
            <span>Pembayaran perlu dicek</span><strong>{{ $stats['pendingPayments'] }}</strong><small>Verifikasi bukti transfer customer</small>
        </a>
        <a class="attention-card {{ $stats['overdue'] ? 'needs-action' : '' }}" href="{{ route('owner.invoices.index',['status'=>'overdue']) }}">
            <span>Tagihan overdue</span><strong>{{ $stats['overdue'] }}</strong><small>Periksa tagihan yang melewati deadline</small>
        </a>
        <a class="attention-card {{ $stats['missingTracking'] ? 'needs-action' : '' }}" href="{{ route('owner.batches.index',['q'=>'']) }}">
            <span>Batch tanpa tracking</span><strong>{{ $stats['missingTracking'] }}</strong><small>Tracking belum diisi pada Batch aktif</small>
        </a>
        <a class="attention-card {{ $stats['arrivedIndo'] ? 'needs-action' : '' }}" href="{{ route('owner.batches.index') }}">
            <span>Arrived Indo</span><strong>{{ $stats['arrivedIndo'] }}</strong><small>Cek apakah perlu Tax / shipping aktual</small>
        </a>
        <a class="attention-card {{ $stats['poClosingToday'] ? 'needs-action' : '' }}" href="{{ route('owner.products.index',['type'=>'po']) }}">
            <span>PO tutup hari ini</span><strong>{{ $stats['poClosingToday'] }}</strong><small>Pastikan order terakhir sudah masuk</small>
        </a>
        <a class="attention-card {{ $stats['unclaimed'] ? 'needs-action' : '' }}" href="{{ route('owner.orders.index',['status'=>'unclaimed']) }}">
            <span>Unclaimed</span><strong>{{ $stats['unclaimed'] }}</strong><small>Barang melewati batas claim</small>
        </a>
    </div>

    <div class="metric-grid compact-metrics">
        <div class="metric"><span>Customer</span><strong>{{ $stats['customers'] }}</strong></div>
        <div class="metric"><span>Total order</span><strong>{{ $stats['orders'] }}</strong></div>
        <div class="metric"><span>Tagihan aktif</span><strong>{{ $stats['unpaid'] }}</strong></div>
        <div class="metric"><span>Batch aktif</span><strong>{{ $stats['activeBatches'] }}</strong></div>
        <div class="metric"><span>PO terbuka</span><strong>{{ $stats['openPo'] }}</strong></div>
    </div>

    <div class="quick-actions">
        <a class="quick-action" href="{{ route('owner.batches.index') }}"><strong>Kelola Batch</strong><span class="muted small">Update Batch, cleanup order, dan tracking.</span></a>
        <a class="quick-action" href="{{ route('owner.payments.index',['status'=>'pending']) }}"><strong>Cek pembayaran</strong><span class="muted small">Lihat bukti transfer yang menunggu verifikasi.</span></a>
        <a class="quick-action" href="{{ route('owner.rate.index') }}"><strong>Atur rate</strong><span class="muted small">Ubah rate, simbol mata uang, dan fee negara.</span></a>
    </div>

    <div class="grid grid-2" style="margin-top:20px">
        <div class="card card-blue">
            <div class="section-head"><h3>Order terbaru</h3><a class="small" href="{{ route('owner.orders.index') }}">Semua order</a></div>
            @forelse($orders as $o)
                <div class="summary-row"><div><strong>{{ $o->order_number }}</strong><div class="small muted">{{ $o->customer->name }}</div></div><span class="badge status-colored" style="--status-color:{{ \App\Services\OrderStatusService::color($o->status) }}">{{ \App\Services\OrderStatusService::label($o->status) }}</span></div>
            @empty
                <div class="empty">Belum ada order.</div>
            @endforelse
        </div>
        <div class="card card-pink">
            <div class="section-head"><h3>Pembayaran menunggu</h3><a class="small" href="{{ route('owner.payments.index',['status'=>'pending']) }}">Semua pembayaran</a></div>
            @forelse($payments as $p)
                <div class="summary-row"><div><strong>{{ $p->customer->name }}</strong><div class="small muted">{{ $p->payment_number }}</div></div><span class="money">Rp{{ number_format($p->amount,0,',','.') }}</span></div>
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
