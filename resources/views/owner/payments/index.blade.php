@extends('layouts.dashboard')
@section('title','Pembayaran')
@section('dashboard')
<div class="page-head"><div><div class="eyebrow">PEMBAYARAN</div><h1>Verifikasi transfer</h1></div></div>
<form class="filters"><input class="input" name="q" value="{{ request('q') }}" placeholder="Payment / customer / rekening / invoice"><select class="select" name="status"><option value="">Semua status</option>@foreach(['pending','approved','rejected'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>@include('partials.filter-actions',['submitLabel'=>'Filter'])</form>
<div class="table-wrap"><table class="table"><thead><tr><th>Pembayaran</th><th>Customer</th><th>Rekening</th><th>Total</th><th>Tagihan</th><th>Dikirim</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($payments as $p)<tr><td><strong>{{ $p->payment_number }}</strong></td><td>{{ $p->customer->name }}</td><td>{{ $p->bankAccount?->bank_name ?: '-' }}<div class="small muted">{{ $p->bankAccount?->account_number }}</div></td><td class="money">Rp{{ number_format($p->amount,0,',','.') }}</td><td>{{ $p->invoices->count() }}</td><td>{{ $p->submitted_at?->translatedFormat('d F Y, H.i') }}</td><td><span class="badge {{ $p->status==='approved'?'ok':($p->status==='rejected'?'danger':'warn') }}">{{ ucfirst($p->status) }}</span></td><td><a class="btn btn-soft btn-sm" href="{{ route('owner.payments.show',$p) }}">Periksa</a></td></tr>@empty<tr><td colspan="8" class="empty">Tidak ada pembayaran yang cocok.</td></tr>@endforelse
</tbody></table></div>@include('partials.pagination',['paginator'=>$payments])
@endsection
