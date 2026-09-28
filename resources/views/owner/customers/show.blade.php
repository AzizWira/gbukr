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
        @if(($customerDeletionState['can_hard_delete'] ?? false))
            @php
                $archivedOrders = (int)($customerDeletionState['archived_order_count'] ?? 0);
                $archivedInvoices = (int)($customerDeletionState['archived_invoice_count'] ?? 0);
                $archivedPayments = (int)($customerDeletionState['archived_payment_count'] ?? 0);
                $hasArchivedResidue = ($archivedOrders + $archivedInvoices + $archivedPayments) > 0;
                $deleteConfirm = $hasArchivedResidue
                    ? "Hapus customer '{$customer->name}' secara permanen? Tidak ada transaksi aktif atau histori finansial yang masih dilindungi. {$archivedOrders} Order, {$archivedInvoices} Tagihan, dan {$archivedPayments} Pembayaran yang sudah dihapus dari operasional akan ikut dibersihkan. Jejak audit approval/penghapusan tetap disimpan. Tindakan tidak dapat dibatalkan."
                    : "Hapus customer '{$customer->name}' secara permanen? Akun ini belum memiliki order, tagihan, atau pembayaran dan tidak memiliki akses Admin. Profil serta session akun akan ikut dihapus. Tindakan tidak dapat dibatalkan.";
            @endphp
            <form method="post" action="{{ route('owner.customers.destroy',$customer) }}" data-confirm-title="Hapus permanen" data-confirm="{{ $deleteConfirm }}">
                @csrf
                @method('delete')
                <button class="btn btn-danger">Hapus customer</button>
            </form>
        @endif
    </div>

    @if(($customerDeletionState['can_hard_delete'] ?? false) && (($customerDeletionState['archived_order_count'] ?? 0) + ($customerDeletionState['archived_invoice_count'] ?? 0) + ($customerDeletionState['archived_payment_count'] ?? 0) > 0))
        <div class="delete-note">
            Customer tidak mempunyai transaksi aktif atau histori finansial yang masih dilindungi. Data yang sudah dihapus dari operasional dapat ikut dibersihkan permanen; audit approval/penghapusan tetap tersimpan.
            <div class="small muted" style="margin-top:6px">
                Akan dibersihkan: {{ $customerDeletionState['archived_order_count'] ?? 0 }} order · {{ $customerDeletionState['archived_invoice_count'] ?? 0 }} tagihan · {{ $customerDeletionState['archived_payment_count'] ?? 0 }} pembayaran
            </div>
        </div>
    @elseif($customer->admin_enabled)
        <div class="delete-note">Customer masih memiliki akses Admin. Cabut akses Admin terlebih dahulu jika akun benar-benar ingin dihapus.</div>
    @elseif($customerDeletionState['has_active_transactions'] ?? false)
        <div class="delete-note">
            Customer belum dapat dihapus karena masih mempunyai Order atau Tagihan aktif. Hapus/selesaikan data aktif terlebih dahulu, atau gunakan Nonaktifkan jika akun masih perlu dipertahankan.
            <div class="small muted" style="margin-top:6px">
                Aktif: {{ $customerDeletionState['active_order_count'] ?? 0 }} order · {{ $customerDeletionState['active_invoice_count'] ?? 0 }} tagihan
            </div>
        </div>
    @elseif($customerDeletionState['has_protected_financial_history'] ?? false)
        <div class="delete-note">
            Customer belum dapat dihapus permanen karena masih mempunyai histori finansial yang belum pernah disetujui untuk dihapus. Gunakan Nonaktifkan atau tinjau transaksi terkait terlebih dahulu.
            <div class="small muted" style="margin-top:6px">
                Histori tersimpan: {{ $deletionUsage['order'] ?? 0 }} order · {{ $deletionUsage['tagihan'] ?? 0 }} tagihan · {{ $deletionUsage['pembayaran'] ?? 0 }} pembayaran
            </div>
        </div>
    @endif
</div>
<div class="card" style="grid-column:1/-1"><h3>Order terakhir</h3>@forelse($customer->orders->sortByDesc('created_at')->take(15) as $o)<div class="summary-row"><div><strong>{{ $o->order_number }}</strong><div class="small muted">{{ $o->items->first()?->item_name }}</div></div><a class="btn btn-soft btn-sm" href="{{ route('owner.orders.show',$o) }}">{{ \App\Services\OrderStatusService::label($o->status) }}</a></div>@empty<div class="empty">Belum ada order.</div>@endforelse</div>
@if(str_ends_with($customer->email,'@placeholder.local'))<div class="card" style="grid-column:1/-1"><h3>Gabungkan data legacy</h3><p class="small muted">Gunakan jika record hasil migrasi perlu dipindahkan ke akun customer yang sudah aktif.</p><form method="post" action="{{ route('owner.customers.merge',$customer) }}" data-confirm="Semua order, tagihan, dan pembayaran customer ini akan dipindahkan ke akun tujuan. Lanjutkan?">@csrf<div class="field" style="max-width:360px"><label>Email akun tujuan</label><input class="input" type="email" name="target_email" placeholder="customer@example.com" required></div><div class="actions" style="margin-top:12px"><button class="btn btn-danger">Gabungkan data</button></div></form></div>@endif
</div>
@endsection
