@extends('layouts.dashboard')
@section('title', 'Detail Order')

@section('dashboard')
<div class="page-head">
    <div>
        <div class="eyebrow">{{ strtoupper($order->source_type) }}</div>
        <h1>{{ $order->order_number }}</h1>
        <p class="muted">{{ $order->customer->name }} · {{ $order->created_at->translatedFormat('d F Y, H.i') }}</p>
    </div>
    <span class="badge status-colored" style="--status-color:{{ \App\Services\OrderStatusService::color($order->status) }}">{{ \App\Services\OrderStatusService::label($order->status) }}</span>
</div>

<div class="grid grid-2">
    <div class="card">
        <h3>Barang</h3>
        @foreach($order->items as $item)
            <div class="summary-row">
                <div>
                    <strong>{{ $item->item_name }}</strong>
                    <div class="small muted">{{ $item->details ?: $item->description_type ?: '-' }}</div>
                </div>
                <span>{{ $item->qty }} pcs</span>
            </div>
        @endforeach
    </div>

    <div class="card">
        <h3>Customer</h3>
        <div class="summary-row"><span>Nama</span><strong>{{ $order->customer->name }}</strong></div>
        <div class="summary-row"><span>Email</span><strong>{{ str_ends_with($order->customer->email, '@placeholder.local') ? 'Belum terhubung akun' : $order->customer->email }}</strong></div>
        <div class="summary-row"><span>WhatsApp</span><strong>{{ $order->customer->customerProfile?->whatsapp ?: '-' }}</strong></div>
        <div class="summary-row"><span>LINE</span><strong>{{ $order->customer->customerProfile?->line_id ?: '-' }}</strong></div>
    </div>

    <div class="card">
        <h3>Tagihan</h3>
        @forelse($order->invoices as $invoice)
            <div class="summary-row">
                <div>
                    <strong>{{ $invoice->invoice_number }}</strong>
                    <div class="small muted">{{ $invoice->type === 'kekurangan' ? 'TAGIHAN TAMBAHAN' : strtoupper($invoice->type) }}</div>
                </div>
                <span class="money">Rp{{ number_format($invoice->outstanding(), 0, ',', '.') }}</span>
            </div>
        @empty
            <div class="empty">Belum ada tagihan.</div>
        @endforelse
    </div>

    <form class="card" method="post" action="{{ route('owner.orders.status', $order) }}" data-loading-text="Menyimpan status…">
        @csrf
        @method('patch')
        <h3>Update / override status</h3>
        <div class="field" style="margin-top:12px">
            <label>Status</label>
            <select class="select" name="status">
                @foreach(\App\Services\OrderStatusService::statuses() as $status)
                    <option value="{{ $status }}" @selected($order->status === $status)>{{ \App\Services\OrderStatusService::label($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="field" style="margin-top:10px">
            <label>Catatan</label>
            <textarea class="textarea" name="notes"></textarea>
        </div>
        <button class="btn btn-primary" style="margin-top:14px">Simpan status</button>
    </form>

    <div class="card" style="grid-column:1/-1">
        <div class="section-head">
            <div><h3>Timeline status</h3><p>Riwayat perubahan status membantu melihat siapa mengubah apa dan kapan.</p></div>
        </div>
        <div class="status-timeline">
            @forelse($order->statusHistories as $history)
                <div class="timeline-item">
                    <span class="timeline-dot" style="--status-color:{{ \App\Services\OrderStatusService::color($history->to_status) }}"></span>
                    <div>
                        <strong>{{ \App\Services\OrderStatusService::label($history->to_status) }}</strong>
                        <div class="small muted">{{ $history->changed_at?->translatedFormat('d F Y, H.i') ?: '-' }} · {{ $history->changer?->name ?: 'Sistem' }}@if($history->notes) · {{ $history->notes }}@endif</div>
                    </div>
                </div>
            @empty
                <div class="empty">Belum ada histori perubahan status.</div>
            @endforelse
        </div>
    </div>

    <div class="card card-pink" style="grid-column:1/-1">
        <div class="section-head">
            <div><div class="eyebrow">TAGIHAN TAMBAHAN</div><h3>Riwayat tagihan tambahan</h3><p>Satu order dapat memiliki lebih dari satu tambahan, misalnya Tax lalu perubahan Rate. Setiap tambahan disimpan terpisah.</p></div>
        </div>
        <div class="adjustment-card-list">
            @forelse($order->adjustments as $adjustment)
                <div class="adjustment-card">
                    <div><strong>{{ $adjustment->reasonLabel() }}</strong><div class="small muted">
                        @if($adjustment->estimated_weight_grams || $adjustment->actual_weight_grams) Berat {{ $adjustment->estimated_weight_grams ?: '-' }} → {{ $adjustment->actual_weight_grams ?: '-' }} gr @endif
                        @if($adjustment->original_rate || $adjustment->final_rate) · Rate {{ $adjustment->original_rate ?: '-' }} → {{ $adjustment->final_rate ?: '-' }} @endif
                        @if($adjustment->estimated_shipping_idr !== null || $adjustment->actual_shipping_idr !== null) · Shipping Rp{{ number_format((int)$adjustment->estimated_shipping_idr,0,',','.') }} → Rp{{ number_format((int)$adjustment->actual_shipping_idr,0,',','.') }} @endif
                        @if($adjustment->notes) · {{ $adjustment->notes }} @endif
                    </div></div>
                    <div class="adjustment-card-value"><div class="money">Rp{{ number_format($adjustment->amount_idr,0,',','.') }}</div><span class="badge {{ $adjustment->invoice?->status==='paid'?'ok':'warn' }}">{{ ucfirst($adjustment->invoice?->status ?: 'unpaid') }}</span></div>
                </div>
            @empty
                <div class="empty">Belum ada tagihan tambahan.</div>
            @endforelse
        </div>
        <details class="adjustment-create" style="margin-top:16px">
            <summary class="btn btn-primary">+ Tambah tagihan tambahan</summary>
            <form method="post" action="{{ route('owner.orders.adjustments.store',$order) }}" style="margin-top:16px" data-adjustment-form data-loading-text="Membuat tagihan tambahan…">@csrf
                <div class="form-grid">
                    <div class="field"><label>Jenis tambahan</label><select class="select" name="reason" required data-adjustment-reason><option value="tax">Tax / Pajak</option><option value="shipping_actual">Shipping Aktual</option><option value="weight">Penyesuaian Berat</option><option value="rate">Penyesuaian Rate</option><option value="weight_rate">Berat + Rate</option><option value="other">Lainnya</option></select></div>
                    <div class="field"><label>Nominal tambahan (Rp)</label><input class="input" type="number" name="amount_idr" min="1" required></div>
                    @include('partials.adjustment-context-fields',['defaultRate'=>$order->rate_snapshot])
                    <div class="field"><label>Deadline pembayaran</label><input class="input" type="datetime-local" name="deadline_at"></div>
                    <div class="field full" data-adjustment-notes><label>Catatan</label><textarea class="textarea" name="notes" placeholder="Keterangan tambahan"></textarea></div>
                </div>
                <button class="btn btn-primary" style="margin-top:14px">Buat tagihan tambahan</button>
            </form>
        </details>
    </div>

    <div class="card danger-zone" style="grid-column:1/-1">
        <div style="width:100%">
            <div class="eyebrow">DATA LIFECYCLE</div>
            <h3>{{ $deletePolicy['action'] === 'approval_required' ? 'Ajukan penghapusan order' : 'Hapus order' }}</h3>
            <p class="muted">
                <strong>{{ $deletePolicy['account_label'] }}</strong> · {{ $deletePolicy['payment_label'] }}.
                @if($deletePolicy['action'] === 'approval_required')
                    Karena order sudah terhubung akun dan memiliki histori pembayaran, customer wajib menyetujui sebelum order dihapus.
                @elseif($deletePolicy['action'] === 'delete_notify')
                    Order dapat dihapus langsung dan customer akan menerima notifikasi penghapusan.
                @elseif($deletePolicy['payment_state'] !== 'none')
                    Akun belum terhubung sehingga persetujuan customer tidak dapat diminta. Order dapat dibersihkan oleh Owner; histori finansial tetap tersimpan di audit internal.
                @else
                    Order belum terhubung akun dan belum memiliki pembayaran sehingga dapat dihapus langsung setelah konfirmasi.
                @endif
            </p>

            @if($latestDeletionRequest?->status === 'pending')
                <div class="notice small" style="margin-top:14px">
                    Permintaan penghapusan sudah dikirim ke customer pada {{ $latestDeletionRequest->requested_at?->translatedFormat('d F Y, H.i') }}. Order tetap aktif sampai customer menyetujui.
                </div>
            @elseif($latestDeletionRequest?->status === 'rejected')
                <div class="alert error" style="margin-top:14px">Permintaan penghapusan terakhir ditolak customer.</div>
            @endif

            @if($latestDeletionRequest?->status !== 'pending')
                <form method="post" action="{{ route('owner.orders.destroy',$order) }}" style="margin-top:16px" data-confirm-title="{{ $deletePolicy['action'] === 'approval_required' ? 'Kirim permintaan penghapusan?' : 'Hapus order?' }}" data-confirm="{{ $deletePolicy['action'] === 'approval_required' ? 'Customer akan menerima rincian order, tagihan, pembayaran, dan alasan penghapusan untuk disetujui atau ditolak.' : 'Order akan dihapus dari data operasional. Jika ada histori finansial pada akun yang belum terhubung, jejak audit internal tetap dipertahankan.' }}">
                    @csrf
                    @method('delete')
                    <div class="field">
                        <label>Alasan penghapusan</label>
                        <textarea class="textarea" name="reason" maxlength="500" placeholder="Contoh: data duplikat hasil migrasi / salah input" @if($deletePolicy['action'] === 'approval_required' || $deletePolicy['payment_state'] !== 'none') required @endif></textarea>
                        <div class="help">Alasan disimpan pada audit. Untuk penghapusan yang memerlukan approval, alasan ini juga terlihat oleh customer.</div>
                    </div>
                    <button class="btn btn-danger" type="submit" style="margin-top:12px">{{ $deletePolicy['action'] === 'approval_required' ? 'Kirim Permintaan Penghapusan' : 'Hapus Order' }}</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
