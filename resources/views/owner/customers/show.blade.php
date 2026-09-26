@extends('layouts.dashboard')
@section('title',$customer->name)
@section('dashboard')
<div class="page-head"><div><div class="eyebrow">CUSTOMER</div><h1>{{ $customer->name }}</h1><p class="muted">{{ str_ends_with($customer->email,'@placeholder.local')?'Belum terhubung akun':$customer->email }}</p></div><span class="badge {{ $customer->active?'ok':'gray' }}">{{ $customer->active?'Aktif':'Nonaktif' }}</span></div>
<div class="grid grid-2">
<form class="card" method="post" action="{{ route('owner.customers.update',$customer) }}">@csrf @method('put')<h3>Data customer</h3><div class="form-grid" style="margin-top:12px"><div class="field"><label>Nama</label><input class="input" name="name" value="{{ $customer->name }}" required></div><div class="field"><label>Username</label><input class="input" name="username" value="{{ $customer->customerProfile?->username }}"></div><div class="field"><label>WhatsApp</label><input class="input" name="whatsapp" value="{{ $customer->customerProfile?->whatsapp }}"></div><div class="field"><label>LINE</label><input class="input" name="line_id" value="{{ $customer->customerProfile?->line_id }}"></div><div class="field"><label>Sumber</label><select class="select" name="source_channel"><option value="">-</option>@foreach(['whatsapp'=>'WhatsApp','line'=>'LINE','other'=>'Lainnya'] as $value=>$label)<option value="{{ $value }}" @selected($customer->customerProfile?->source_channel===$value)>{{ $label }}</option>@endforeach</select></div><div class="field"><label>Catatan</label><input class="input" name="notes" value="{{ $customer->customerProfile?->notes }}"></div></div><div class="actions" style="margin-top:12px"><button class="btn btn-primary">Simpan</button></div></form>
<div class="card">
    <h3>Ringkasan</h3>
    <div class="summary-row"><span>Order</span><strong>{{ $customer->orders->count() }}</strong></div>
    <div class="summary-row"><span>Tagihan</span><strong>{{ $customer->invoices->count() }}</strong></div>
    <div class="summary-row"><span>Pembayaran</span><strong>{{ $customer->payments->count() }}</strong></div>
    <div class="summary-row"><span>Sisa tagihan</span><strong>Rp{{ number_format($customer->invoices->where('status','!=','cancelled')->sum(fn($i)=>$i->outstanding()),0,',','.') }}</strong></div>
    <div class="actions" style="margin-top:14px">
        <form method="post" action="{{ route('owner.customers.toggle',$customer) }}" data-confirm="{{ $customer->active?'Nonaktifkan customer ini? Login dan penggunaan akun akan diblokir sampai diaktifkan kembali, tetapi seluruh histori tetap tersimpan.':'Aktifkan customer ini?' }}">
            @csrf
            <button class="btn {{ $customer->active?'btn-danger':'btn-soft' }}">{{ $customer->active?'Nonaktifkan customer':'Aktifkan customer' }}</button>
        </form>
        @if(($customerUsageCount ?? 0) === 0 && !$customer->admin_enabled)
            <form method="post" action="{{ route('owner.customers.destroy',$customer) }}" data-confirm-title="Hapus permanen" data-confirm="Hapus customer '{{ $customer->name }}' secara permanen? Akun ini belum memiliki order, tagihan, atau pembayaran dan tidak memiliki akses Admin. Profil serta session akun akan ikut dihapus. Tindakan tidak dapat dibatalkan.">
                @csrf
                @method('delete')
                <button class="btn btn-danger">Hapus customer</button>
            </form>
        @endif
    </div>
    @if(($customerUsageCount ?? 0) > 0)
        <div class="delete-note">Customer tidak dapat dihapus permanen karena sudah mempunyai histori transaksi. Gunakan Nonaktifkan agar histori order, tagihan, dan pembayaran tetap utuh.</div>
    @elseif($customer->admin_enabled)
        <div class="delete-note">Customer masih memiliki akses Admin. Cabut akses Admin terlebih dahulu jika akun benar-benar ingin dihapus.</div>
    @endif
</div>
<div class="card" style="grid-column:1/-1"><h3>Order terakhir</h3>@forelse($customer->orders->sortByDesc('created_at')->take(15) as $o)<div class="summary-row"><div><strong>{{ $o->order_number }}</strong><div class="small muted">{{ $o->items->first()?->item_name }}</div></div><a class="btn btn-soft btn-sm" href="{{ route('owner.orders.show',$o) }}">{{ \App\Services\OrderStatusService::label($o->status) }}</a></div>@empty<div class="empty">Belum ada order.</div>@endforelse</div>
@if(str_ends_with($customer->email,'@placeholder.local'))<div class="card" style="grid-column:1/-1"><h3>Gabungkan data legacy</h3><p class="small muted">Gunakan jika record hasil migrasi perlu dipindahkan ke akun customer yang sudah aktif.</p><form method="post" action="{{ route('owner.customers.merge',$customer) }}" data-confirm="Semua order, tagihan, dan pembayaran customer ini akan dipindahkan ke akun tujuan. Lanjutkan?">@csrf<div class="field" style="max-width:360px"><label>Email akun tujuan</label><input class="input" type="email" name="target_email" placeholder="customer@example.com" required></div><div class="actions" style="margin-top:12px"><button class="btn btn-danger">Gabungkan data</button></div></form></div>@endif
</div>
@endsection
