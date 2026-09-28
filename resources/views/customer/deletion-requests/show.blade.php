@extends('layouts.dashboard')
@section('title','Persetujuan Penghapusan Order')
@section('dashboard')
@php($s=(array)$deletionRequest->snapshot)
<div class="page-head"><div><div class="eyebrow">PERSETUJUAN CUSTOMER</div><h1>Penghapusan {{ $s['order_number'] ?? 'Order' }}</h1><p class="muted">Periksa rincian sebelum memilih Setujui atau Tolak.</p></div><span class="badge {{ $deletionRequest->status==='pending'?'warn':'gray' }}">{{ strtoupper($deletionRequest->status) }}</span></div>
<div class="grid grid-2">
<div class="card"><h3>Permintaan Owner</h3><div class="summary-row"><span>Order</span><strong>{{ $s['order_number'] ?? '-' }}</strong></div><div class="summary-row"><span>Customer</span><strong>{{ $s['customer_name'] ?? '-' }}</strong></div><div class="summary-row"><span>Batch</span><strong>{{ $s['batch'] ?? '-' }}</strong></div><div class="summary-row"><span>Alasan</span><strong>{{ $deletionRequest->reason ?: '-' }}</strong></div></div>
<div class="card"><h3>Riwayat finansial</h3><div class="summary-row"><span>Status</span><strong>{{ $s['payment_status'] ?? '-' }}</strong></div>@forelse(($s['invoices'] ?? []) as $invoice)<div class="summary-row"><div><strong>{{ $invoice['number'] ?? '-' }}</strong><div class="small muted">{{ strtoupper($invoice['status'] ?? '-') }}</div></div><span>Rp{{ number_format((int)($invoice['amount'] ?? 0),0,',','.') }}</span></div>@empty<div class="empty">Tidak ada tagihan.</div>@endforelse</div>
</div>
<div class="notice small" style="margin-top:18px">Jika disetujui, order serta tagihan/pembayaran terkait akan hilang dari data operasional. Jejak audit internal tetap disimpan untuk mencegah manipulasi histori transaksi.</div>
@if($deletionRequest->status==='pending')
<div class="actions" style="margin-top:18px"><form method="post" action="{{ route('customer.deletion-requests.reject',$deletionRequest) }}" data-confirm-title="Tolak penghapusan?" data-confirm="Order akan tetap tersimpan dan Owner akan melihat bahwa permintaan tidak disetujui.">@csrf<button class="btn btn-neutral" type="submit">Tolak</button></form><form method="post" action="{{ route('customer.deletion-requests.approve',$deletionRequest) }}" data-confirm-title="Setujui penghapusan?" data-confirm="Order, tagihan, dan pembayaran terkait akan dihapus dari data operasional. Jejak audit internal tetap disimpan.">@csrf<button class="btn btn-danger" type="submit">Setujui Penghapusan</button></form></div>
@endif
@endsection
