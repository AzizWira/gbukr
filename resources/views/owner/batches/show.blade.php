@extends('layouts.dashboard')
@section('title', $batch->code . ' — Batch')

@section('dashboard')
<div class="page-head">
    <div>
        <div class="eyebrow">BATCH · {{ $batch->country->name }}</div>
        <h1>{{ $batch->code }}</h1>
        <p class="muted">{{ $batch->name }} · {{ $batch->goGroup?->name ?: 'Tanpa GO' }}</p>
    </div>
    <span class="badge status-colored" style="--status-color:{{ \App\Services\OrderStatusService::color($batch->status) }}">{{ \App\Services\OrderStatusService::label($batch->status) }}</span>
</div>

<div class="grid grid-2">
    <form class="card" method="post" action="{{ route('owner.batches.status', $batch) }}">
        @csrf
        @method('patch')

        <h3>Update status bersama</h3>

        <div class="field" style="margin-top:12px">
            <label>Status</label>
            <select class="select" name="status">
                @foreach(\App\Services\OrderStatusService::statuses() as $status)
                    <option value="{{ $status }}" @selected($batch->status === $status)>
                        {{ \App\Services\OrderStatusService::label($status) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="field" style="margin-top:12px">
            <label>Tracking Number</label>
            <input class="input" name="tracking_number" value="{{ old('tracking_number', $batch->tracking_number) }}" maxlength="180" placeholder="Boleh kosong jika belum tersedia">
            <div class="help">Tracking Number mengikuti nomor asli dari seller/kurir.</div>
        </div>

        <button class="btn btn-primary" style="margin-top:14px">Perbarui Batch</button>
    </form>

    <div class="card">
        <h3>Informasi internal</h3>
        <div class="summary-row"><span>GO</span><strong>{{ $batch->goGroup?->name ?: '-' }}</strong></div>
        <div class="summary-row"><span>Negara</span><strong>{{ $batch->country->name }}</strong></div>
        <div class="summary-row"><span>Warehouse</span><strong>{{ auth()->user()->isOwner() ? ($batch->warehouse?->code ?: '-') : 'Internal' }}</strong></div>
        <div class="summary-row"><span>Tracking publik</span><strong>{{ $batch->shipment?->visible_publicly ? 'Aktif' : 'Tidak aktif' }}</strong></div>
        <div class="summary-row"><span>Arrived GBU/KRJASTIP</span><strong>{{ $batch->arrived_gbu_at?->translatedFormat('d F Y, H.i') ?: '-' }}</strong></div>
    </div>
</div>

@if(auth()->user()->isOwner())
    <div class="card" style="margin-top:18px">
        <div class="section-head" style="margin-bottom:12px">
            <div>
                <h3>Customer &amp; barang dalam Batch</h3>
                <p>Tracking publik akan merangkum item yang sudah dimasukkan ke Batch ini.</p>
            </div>
        </div>

        @if($batch->orders->count())
            @php
                $safeCleanupCount = $batch->orders->filter(fn($order) => empty($deleteBlockers[$order->id]))->count();
                $protectedCleanupCount = $batch->orders->count() - $safeCleanupCount;
            @endphp
            <form method="post" action="{{ route('owner.batches.orders.destroy',$batch) }}" data-confirm-title="Bersihkan order terpilih?" data-confirm="Order yang tidak memerlukan approval akan dibersihkan. Untuk order terhubung akun yang mempunyai histori pembayaran, sistem hanya mengirim permintaan persetujuan dan order tetap berada di Batch sampai disetujui." data-loading-text="Membersihkan order…" data-no-dirty-guard>
                @csrf
                @method('delete')
                <div class="batch-bulk-toolbar">
                    <div>
                        <strong>Cleanup isi Batch</strong>
                        <div class="small muted">Order yang tidak memerlukan approval akan dihapus sesuai aturan akun. Order terhubung akun dengan histori pembayaran akan menunggu persetujuan customer dan tetap berada di Batch.</div>
                    </div>
                    <button class="btn btn-danger btn-sm" type="submit">Bersihkan order terpilih</button>
                </div>
                <div class="table-wrap">
                    <table class="table responsive-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" data-check-all="batch-orders" aria-label="Pilih semua order yang aman dihapus"></th>
                                <th>Customer</th>
                                <th>Barang</th>
                                <th>Detail</th>
                                <th>Qty</th>
                                <th>Tagihan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($batch->orders as $order)
                                @php
                                    $blocker = $deleteBlockers[$order->id] ?? null;
                                @endphp
                                <tr>
                                    <td data-label="Pilih">
                                        <input type="checkbox" name="order_ids[]" value="{{ $order->id }}" data-check-group="batch-orders" aria-label="Pilih {{ $order->order_number }}">
                                    </td>
                                    <td data-label="Customer">{{ $order->customer->name }}</td>
                                    <td data-label="Barang">{{ $order->items->first()?->item_name ?: '-' }}</td>
                                    <td data-label="Detail">{{ $order->items->first()?->details ?: ($order->items->first()?->description_type ?: '-') }}</td>
                                    <td data-label="Qty">{{ $order->items->sum('qty') }}</td>
                                    <td data-label="Tagihan">
                                        @if($order->invoices->count())
                                            Rp{{ number_format($order->invoices->sum(fn ($invoice) => $invoice->outstanding()), 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                        @if($blocker)
                                            <div class="small protected-note">Butuh approval customer sebelum dihapus</div>
                                        @else
                                            <div class="small safe-delete-note">Dapat diproses tanpa approval</div>
                                        @endif
                                    </td>
                                    <td data-label="Aksi"><a class="btn btn-soft btn-sm" href="{{ route('owner.orders.show',$order) }}">Buka</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>

            <div class="danger-zone" style="margin-top:16px">
                <div>
                    <div class="eyebrow">HAPUS BATCH</div>
                    <h3>Bersihkan lalu hapus Batch</h3>
                    <p class="muted">
                        {{ $safeCleanupCount }} order dapat diproses tanpa persetujuan customer.
                        @if($protectedCleanupCount > 0)
                            {{ $protectedCleanupCount }} order membutuhkan persetujuan customer. Jika masih ada yang menunggu approval, Batch tidak akan dihapus.
                        @endif
                    </p>
                </div>
                <form method="post" action="{{ route('owner.batches.destroy',$batch) }}" data-confirm-title="Bersihkan & hapus Batch?" data-confirm="Sistem akan memeriksa seluruh order. Jika ada order yang membutuhkan approval customer, permintaan akan dikirim dan Batch belum dihapus. Jika semuanya aman, Batch dan tracking terkait akan dibersihkan." data-loading-text="Menghapus Batch…" data-no-dirty-guard>
                    @csrf
                    @method('delete')
                    <button class="btn btn-danger" type="submit">Bersihkan &amp; Hapus Batch</button>
                </form>
            </div>
        @else
            <div class="empty actionable-empty">
                <strong>Batch ini belum memiliki order.</strong>
                <span>Karena belum ada histori customer, Batch ini aman dihapus permanen bila memang tidak diperlukan.</span>
                <form method="post" action="{{ route('owner.batches.destroy',$batch) }}" data-confirm-title="Hapus Batch kosong?" data-confirm="Batch {{ $batch->code }} dan tracking publik yang terkait akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.">
                    @csrf
                    @method('delete')
                    <button class="btn btn-danger btn-sm" type="submit">Hapus Batch</button>
                </form>
            </div>
        @endif
    </div>

    @if($batch->orders->count())
        @php
            $batchAdjustments = $batch->orders->flatMap->adjustments->sortByDesc('created_at');
        @endphp
        <div class="card card-pink" style="margin-top:18px">
            <div class="section-head"><div><div class="eyebrow">TAGIHAN TAMBAHAN</div><h3>Tambahan pada Batch ini</h3><p>Tax, Rate, Berat, Shipping, dan tambahan lain disimpan sebagai section terpisah. Tambahan baru tidak menimpa yang lama.</p></div></div>
            <div class="adjustment-card-list">
                @forelse($batchAdjustments as $adjustment)
                    <div class="adjustment-card">
                        <div>
                            <strong>{{ $adjustment->reasonLabel() }}</strong>
                            <div class="small muted">
                                {{ $adjustment->order?->customer?->name }} · {{ $adjustment->invoice?->invoice_number }}
                                @if($adjustment->original_rate || $adjustment->final_rate)
                                    · Rate {{ $adjustment->original_rate ?: '-' }} → {{ $adjustment->final_rate ?: '-' }}
                                @endif
                                @if($adjustment->estimated_weight_grams || $adjustment->actual_weight_grams)
                                    · Berat {{ $adjustment->estimated_weight_grams ?: '-' }} → {{ $adjustment->actual_weight_grams ?: '-' }} gr
                                @endif
                                @if($adjustment->estimated_shipping_idr !== null || $adjustment->actual_shipping_idr !== null)
                                    · Shipping Rp{{ number_format((int) ($adjustment->estimated_shipping_idr ?? 0), 0, ',', '.') }} → Rp{{ number_format((int) ($adjustment->actual_shipping_idr ?? 0), 0, ',', '.') }}
                                @endif
                            </div>
                        </div>
                        <div class="money">Rp{{ number_format($adjustment->amount_idr, 0, ',', '.') }}</div>
                    </div>
                @empty
                    <div class="empty">Belum ada tagihan tambahan di Batch ini.</div>
                @endforelse
            </div>
            <details class="adjustment-create" style="margin-top:16px">
                <summary class="btn btn-primary">+ Tambah tagihan tambahan</summary>
                <form style="margin-top:16px" method="post" action="{{ route('owner.batches.adjustments.store',$batch) }}" data-bulk-adjustment-form data-adjustment-form data-loading-text="Membuat tagihan tambahan…">@csrf
                    <div class="form-grid">
                        <div class="field"><label>Jenis tambahan</label><select class="select" name="reason" required data-adjustment-reason><option value="tax">Tax / Pajak</option><option value="shipping_actual">Shipping Aktual</option><option value="weight">Penyesuaian Berat</option><option value="rate">Penyesuaian Rate</option><option value="weight_rate">Berat + Rate</option><option value="other">Kekurangan Lainnya</option></select></div>
                        <div class="field"><label>Cara hitung</label><select class="select" name="mode" required data-bulk-adjustment-mode><option value="per_item">Nominal sama per barang</option><option value="per_customer">Nominal sama per customer</option><option value="custom">Nominal berbeda tiap customer</option></select></div>
                        <div class="field" data-bulk-shared-amount><label>Nominal (Rp)</label><input class="input" type="number" name="shared_amount_idr" min="1"><div class="help" data-bulk-amount-help>Mode per barang akan dikali Qty.</div></div>
                        @include('partials.adjustment-context-fields',['defaultRate'=>$batch->orders->first()?->rate_snapshot])
                        <div class="field"><label>Deadline pembayaran</label><input class="input" type="datetime-local" name="deadline_at"></div>
                        <div class="field full" data-adjustment-notes><label>Catatan</label><textarea class="textarea" name="notes" placeholder="Keterangan tambahan"></textarea></div>
                    </div>
                    <div class="table-wrap" style="margin-top:16px"><table class="table"><thead><tr><th>Pilih</th><th>Customer</th><th>Barang</th><th>Qty</th><th data-bulk-custom-heading hidden>Nominal tambahan</th></tr></thead><tbody>@foreach($batch->orders as $order)<tr><td><input type="checkbox" name="order_ids[]" value="{{ $order->id }}" checked></td><td><strong>{{ $order->customer->name }}</strong></td><td>{{ $order->items->pluck('item_name')->join(', ') ?: '-' }}</td><td>{{ $order->items->sum('qty') }}</td><td data-bulk-custom-cell hidden><input class="input" style="min-width:150px" type="number" min="1" name="custom_amounts[{{ $order->id }}]" data-bulk-custom-amount></td></tr>@endforeach</tbody></table></div>
                    <div class="notice small" style="margin-top:14px">Tagihan awal tetap utuh. Sistem membuat invoice tambahan baru untuk customer yang dipilih.</div>
                    <button class="btn btn-primary" style="margin-top:16px">Buat tagihan tambahan</button>
                </form>
            </details>
        </div>
    @endif
@else
    <div class="notice small" style="margin-top:18px">
        Batch ini terhubung ke {{ $batch->orders->count() }} order. Detail customer dan tagihan hanya tersedia untuk Owner.
    </div>
@endif

@if(auth()->user()->isOwner())
    <form class="card" style="margin-top:18px" method="post" action="{{ route('owner.batches.orders.store', $batch) }}">
        @csrf

        <h3>Tambahkan order customer</h3>

        <div class="form-grid" style="margin-top:12px">
            <div class="field">
                <label>Customer yang sudah ada</label>
                <select class="select" name="customer_id">
                    <option value="">— input customer baru —</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                            {{ $customer->name }} · {{ str_ends_with($customer->email, '@placeholder.local') ? 'Belum terhubung akun' : $customer->email }}
                        </option>
                    @endforeach
                </select>
                <div class="help">Jika customer belum terdaftar, isi data customer baru di bawah.</div>
            </div>

            <div class="field">
                <label>Nama customer baru</label>
                <input class="input" name="customer_name" value="{{ old('customer_name') }}" maxlength="120" placeholder="Isi jika customer belum ada">
            </div>

            <div class="field">
                <label>Username (opsional)</label>
                <input class="input" name="customer_username" value="{{ old('customer_username') }}" maxlength="100">
            </div>

            <div class="field">
                <label>Nomor WhatsApp (opsional)</label>
                <input class="input" name="customer_whatsapp" value="{{ old('customer_whatsapp') }}" maxlength="30">
            </div>

            <div class="field">
                <label>LINE (opsional)</label>
                <input class="input" name="customer_line" value="{{ old('customer_line') }}" maxlength="100">
            </div>

            <div class="field">
                <label>Nama barang</label>
                <input class="input" name="item_name" value="{{ old('item_name') }}" required maxlength="180">
            </div>

            <div class="field">
                <label>Detail / variasi</label>
                <input class="input" name="details" value="{{ old('details') }}" maxlength="180">
            </div>

            <div class="field">
                <label>Keterangan publik</label>
                <input class="input" name="description_type" value="{{ old('description_type') }}" required maxlength="100" placeholder="Album / Photocard / Merchandise">
            </div>

            <div class="field">
                <label>Qty</label>
                <input class="input" type="number" name="qty" value="{{ old('qty', 1) }}" min="1" max="999" required>
            </div>

            <div class="field">
                <label>Jenis tagihan</label>
                <select class="select" name="invoice_type">
                    @foreach(['pelunasan' => 'Pelunasan', 'full' => 'Full Payment', 'dp' => 'DP', 'cicilan' => 'Cicilan', 'kenaikan' => 'Kenaikan', 'penyesuaian' => 'Penyesuaian'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('invoice_type', 'pelunasan') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label>Nominal tagihan (opsional)</label>
                <input class="input" type="number" min="1" name="invoice_amount" value="{{ old('invoice_amount') }}">
            </div>

            <div class="field">
                <label>Deadline (opsional)</label>
                <input class="input" type="datetime-local" name="deadline_at" value="{{ old('deadline_at') }}">
            </div>
        </div>

        <div class="help" style="margin-top:12px">Jika membuat customer baru, isi minimal salah satu username, WhatsApp, atau LINE agar data tidak tertukar dengan customer lain yang namanya sama.</div>
        <button class="btn btn-primary" style="margin-top:16px">Tambahkan ke Batch</button>
    </form>
@endif
@endsection
