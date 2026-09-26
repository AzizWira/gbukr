@extends('layouts.dashboard')
@section('title', $product->exists ? 'Edit Produk' : 'Tambah Produk')

@section('dashboard')
@php($scheme = $product->payment_scheme ?? [])
<div class="page-head">
    <div>
        <div class="eyebrow">{{ $product->exists ? 'EDIT PRODUK' : 'PRODUK BARU' }}</div>
        <h1>{{ $product->exists ? $product->name : 'Tambah produk' }}</h1>
        <p class="muted">Atur informasi PO/Ready Stock, fee per barang, dan detail variasi yang ditampilkan ke customer.</p>
    </div>
    @if($product->exists)
        <div class="actions">
            <form method="post" action="{{ route('owner.products.toggle',$product) }}" data-confirm="{{ $product->active ? 'Nonaktifkan produk ini? Produk tidak dapat dipesan lagi, tetapi order lama tetap aman.' : 'Aktifkan kembali produk ini?' }}">
                @csrf
                <button class="btn {{ $product->active ? 'btn-danger' : 'btn-soft' }}">{{ $product->active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
            </form>
            @if(($productUsageCount ?? 0) === 0)
                <form method="post" action="{{ route('owner.products.destroy',$product) }}" data-confirm-title="Hapus permanen" data-confirm="Hapus produk '{{ $product->name }}' secara permanen? Produk dan seluruh variasinya belum pernah dipakai dalam order. Foto produk juga akan dihapus. Tindakan tidak dapat dibatalkan.">
                    @csrf
                    @method('delete')
                    <button class="btn btn-danger">Hapus</button>
                </form>
            @endif
        </div>
    @endif
</div>

@if($product->exists && ($productUsageCount ?? 0) > 0)
    <div class="lifecycle-note small">Produk ini sudah dipakai oleh {{ $productUsageCount }} item order sehingga tidak dapat dihapus permanen. Gunakan <strong>Nonaktifkan</strong> jika produk tidak dijual lagi.</div>
@endif

@if($product->exists && $product->preorder?->isOpen())
    <div class="actions" style="margin-bottom:18px">
        <form method="post" action="{{ route('owner.products.close', $product) }}" data-confirm="Tutup PO ini lebih awal? Customer tidak bisa membuat order baru setelah ditutup.">
            @csrf
            <button class="btn btn-danger">Tutup PO</button>
        </form>
    </div>
@endif

<form class="card" method="post" enctype="multipart/form-data" action="{{ $product->exists ? route('owner.products.update', $product) : route('owner.products.store') }}">
    @csrf
    @if($product->exists)
        @method('put')
    @endif

    <div class="form-section-title">Informasi utama</div>
    <div class="form-grid">
        <div class="field">
            <label>Nama produk</label>
            <input class="input" name="name" value="{{ old('name', $product->name) }}" required placeholder="Contoh: Enhypen The Sin : Bliss">
        </div>
        <div class="field">
            <label>Jenis</label>
            <select class="select" name="type" required data-product-type>
                <option value="po" @selected(old('type', $product->type) === 'po')>PO</option>
                <option value="ready" @selected(old('type', $product->type) === 'ready')>Ready Stock</option>
            </select>
        </div>
        <div class="field">
            <label>Negara</label>
            <select class="select" name="country_id" required data-product-country>
                @foreach($countries as $c)
                    <option value="{{ $c->id }}" data-currency-symbol="{{ $c->moneySymbol() }}" @selected(old('country_id', $product->country_id) == $c->id)>{{ $c->name }} · {{ $c->currency_code }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label>Foto</label>
            <input class="input" type="file" name="image" accept="image/jpeg,image/png,image/webp">
        </div>
        <div class="field full">
            <label>Deskripsi</label>
            <textarea class="textarea" name="description" placeholder="Deskripsi singkat produk. Tidak perlu memasukkan rate/fee di sini karena akan ditampilkan otomatis.">{{ old('description', $product->description) }}</textarea>
        </div>
    </div>

    <div class="form-section-title">Harga & informasi PO</div>
    <div class="form-grid">
        <div class="field">
            <label>Fee per barang (Rupiah)</label>
            <input class="input" type="number" min="0" name="item_fee_idr" value="{{ old('item_fee_idr', $product->item_fee_idr ?? 0) }}" placeholder="Contoh 12500">
            <div class="help">Ditampilkan bersama rate aktif pada detail produk.</div>
        </div>
        <div class="field">
            <label>Lokasi</label>
            <input class="input" name="location_note" value="{{ old('location_note', $product->location_note) }}" placeholder="Contoh: Sidoarjo, Jawa Timur">
        </div>
        <div class="field">
            <label>Tanggal informasi/event (opsional)</label>
            <input class="input" type="date" name="event_date" value="{{ old('event_date', $product->event_date?->format('Y-m-d')) }}">
        </div>
        <div class="field">
            <label>Status tax</label>
            <select class="select" name="tax_status" required>
                <option value="excluded" @selected(old('tax_status', $product->tax_status ?? (($product->ems_tax ?? false) ? 'included_estimate' : 'excluded')) === 'excluded')>Belum termasuk tax</option>
                <option value="included_estimate" @selected(old('tax_status', $product->tax_status ?? (($product->ems_tax ?? false) ? 'included_estimate' : 'excluded')) === 'included_estimate')>Sudah termasuk estimasi tax</option>
            </select>
            <div class="help">Pilih sesuai cara harga produk disusun. Customer akan melihat status ini secara eksplisit.</div>
        </div>
        <div class="field check-stack">
            <label><input type="checkbox" name="free_shipping" value="1" @checked(old('free_shipping', $product->free_shipping ?? false))> Free Shipping</label>
            <label><input type="checkbox" name="apply_fansign" value="1" @checked(old('apply_fansign', $product->apply_fansign ?? false))> Apply Fansign</label>
        </div>
    </div>

    <div class="form-section-title">Periode & pembayaran</div>
    <div class="form-grid">
        <div class="field">
            <label>Open PO</label>
            <input class="input" type="datetime-local" name="open_at" value="{{ old('open_at', $product->preorder?->open_at?->format('Y-m-d\TH:i')) }}">
        </div>
        <div class="field">
            <label>Close PO</label>
            <input class="input" type="datetime-local" name="close_at" data-close-po value="{{ old('close_at', $product->preorder?->close_at?->format('Y-m-d\TH:i')) }}">
        </div>
        <div class="field">
            <label>Pembayaran awal</label>
            <select class="select" name="payment_type" required>
                <option value="full" @selected(($scheme['type'] ?? 'full') === 'full')>Full Payment</option>
                <option value="dp" @selected(($scheme['type'] ?? '') === 'dp')>DP</option>
                <option value="cicilan" @selected(($scheme['type'] ?? '') === 'cicilan')>Cicilan awal</option>
                <option value="pelunasan" @selected(($scheme['type'] ?? '') === 'pelunasan')>Pelunasan</option>
            </select>
        </div>
        <div class="field">
            <label>Nominal DP default (opsional)</label>
            <input class="input" type="number" name="payment_amount" min="0" value="{{ old('payment_amount', $scheme['amount'] ?? '') }}">
            <div class="help">Bisa dioverride per variasi.</div>
        </div>
        <div class="field">
            <label>DP persen jika nominal kosong</label>
            <input class="input" type="number" step="0.01" min="1" max="100" name="payment_percent" value="{{ old('payment_percent', $scheme['percent'] ?? '') }}">
        </div>
        <div class="field">
            <label>Deadline pembayaran (hari)</label>
            <input class="input" type="number" min="0" name="deadline_days" value="{{ old('deadline_days', $scheme['deadline_days'] ?? '') }}">
        </div>
        <div class="field full">
            <label><input type="checkbox" name="active" value="1" @checked(old('active', $product->exists ? $product->active : true))> Produk aktif</label>
        </div>
    </div>

    <button class="btn btn-primary" style="margin-top:18px">Simpan produk</button>
</form>

@if($product->exists)
    <div class="card" style="margin-top:18px">
        <div class="section-head">
            <div>
                <h3>Variasi produk</h3>
                <p>Kelompokkan variasi dengan nama website seperti Korean Web, Ktown4u Web, atau Weverse Website.</p>
            </div>
        </div>

        @forelse($product->variants->groupBy(fn($v) => $v->source_label ?: 'Tanpa grup') as $source => $variants)
            <div class="variant-source-block">
                <div class="variant-source-title">{{ $source }}</div>
                @foreach($variants as $v)
                    <details class="variant-editor">
                        <summary>
                            <div>
                                <strong>{{ $v->name }}</strong>
                                @if($v->details)
                                    <div class="small muted">{{ $v->details }}</div>
                                @endif
                            </div>
                            <div class="variant-summary-meta">
                                @if($v->estimated_weight_grams !== null)<span class="badge gray">Est {{ number_format($v->estimated_weight_grams,0,',','.') }}gr</span>@endif
                                <span class="badge">{{ $v->price_idr !== null ? 'Rp'.number_format($v->price_idr,0,',','.') : $product->country?->moneySymbol().number_format((float)$v->price_foreign,0,',','.') }}</span>
                                @if($v->dp_amount_idr)<span class="badge pink">DP Rp{{ number_format($v->dp_amount_idr,0,',','.') }}</span>@endif
                                <span class="badge {{ $v->active ? 'ok' : 'gray' }}">{{ $v->active ? 'Aktif' : 'Nonaktif' }}</span>
                            </div>
                        </summary>
                        <form method="post" action="{{ route('owner.products.variant.update', [$product, $v]) }}" style="margin-top:14px">
                            @csrf
                            @method('put')
                            <div class="form-grid">
                                <div class="field"><label>Sumber website</label><input class="input" name="source_label" value="{{ $v->source_label }}"></div>
                                <div class="field"><label>Nama variasi</label><input class="input" name="name" value="{{ $v->name }}" required></div>
                                <div class="field full"><label>Detail versi</label><input class="input" name="details" value="{{ $v->details }}" placeholder="Contoh: Studio Ver / Street Ver / Bridge Ver"></div>
                                <div class="field"><label>Estimasi berat (gram)</label><input class="input" type="number" min="0" name="estimated_weight_grams" value="{{ $v->estimated_weight_grams }}"></div>
                                <div class="field"><label>DP variasi (Rupiah)</label><input class="input" type="number" min="1" name="dp_amount_idr" value="{{ $v->dp_amount_idr }}"></div>
                                <div class="field"><label data-foreign-price-label>Harga mata uang asal ({{ $product->country?->moneySymbol() }})</label><input class="input" type="number" step="0.01" min="0" name="price_foreign" value="{{ $v->price_foreign }}"></div>
                                <div class="field"><label>Pricelist Rupiah</label><input class="input" type="number" min="1" name="price_idr" value="{{ $v->price_idr }}"></div>
                                <div class="field"><label>SKU (opsional)</label><input class="input" name="sku" value="{{ $v->sku }}"></div>
                                <div class="field"><label>Stok (Ready Stock)</label><input class="input" type="number" min="0" name="stock" value="{{ $v->stock }}" data-required-when-ready></div>
                            </div>
                            <div class="actions" style="margin-top:14px">
                                <button class="btn btn-primary btn-sm">Simpan perubahan</button>
                            </div>
                        </form>
                        <div class="actions" style="margin-top:10px">
                            <form method="post" action="{{ route('owner.products.variant.toggle', [$product, $v]) }}" data-confirm="{{ $v->active ? 'Nonaktifkan variasi ini? Variasi tidak dapat dipilih untuk order baru, tetapi order lama tetap aman.' : 'Aktifkan kembali variasi ini?' }}">
                                @csrf
                                <button class="btn btn-sm {{ $v->active ? 'btn-danger' : 'btn-soft' }}">{{ $v->active ? 'Nonaktifkan variasi' : 'Aktifkan variasi' }}</button>
                            </form>
                            @if((int) data_get($variantUsage ?? [], $v->id, 0) === 0)
                                <form method="post" action="{{ route('owner.products.variant.destroy', [$product, $v]) }}" data-confirm-title="Hapus permanen" data-confirm="Hapus variasi '{{ $v->name }}' secara permanen? Variasi ini belum pernah dipakai dalam order. Tindakan tidak dapat dibatalkan.">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-sm btn-danger">Hapus variasi</button>
                                </form>
                            @endif
                        </div>
                        @if((int) data_get($variantUsage ?? [], $v->id, 0) > 0)
                            <div class="delete-note">Variasi ini sudah dipakai oleh {{ data_get($variantUsage, $v->id) }} item order sehingga hanya dapat dinonaktifkan.</div>
                        @endif
                    </details>
                @endforeach
            </div>
        @empty
            <div class="empty">Belum ada variasi.</div>
        @endforelse
    </div>

    <form class="card" style="margin-top:18px" method="post" action="{{ route('owner.products.variant', $product) }}">
        @csrf
        <h3>Tambah variasi</h3>
        <div class="form-grid" style="margin-top:14px">
            <div class="field"><label>Sumber website</label><input class="input" name="source_label" placeholder="Contoh: Korean Web / Ktown4u Web"></div>
            <div class="field"><label>Nama variasi</label><input class="input" name="name" required placeholder="Contoh: Photobook Ver"></div>
            <div class="field full"><label>Detail versi</label><input class="input" name="details" placeholder="Contoh: Studio Ver / Street Ver / Bridge Ver"></div>
            <div class="field"><label>Estimasi berat (gram)</label><input class="input" type="number" min="0" name="estimated_weight_grams" placeholder="300"></div>
            <div class="field"><label>DP variasi (Rupiah)</label><input class="input" type="number" min="1" name="dp_amount_idr" placeholder="150000"></div>
            <div class="field"><label data-foreign-price-label>Harga mata uang asal ({{ $product->country?->moneySymbol() ?: $countries->first()?->moneySymbol() }})</label><input class="input" type="number" step="0.01" min="0" name="price_foreign"></div>
            <div class="field"><label>Pricelist Rupiah</label><input class="input" type="number" min="1" name="price_idr" placeholder="241000"></div>
            <div class="field"><label>SKU (opsional)</label><input class="input" name="sku"></div>
            <div class="field"><label>Stok (Ready Stock)</label><input class="input" type="number" min="0" name="stock" data-required-when-ready></div>
        </div>
        <div class="help" style="margin-top:10px">Isi tepat satu harga: mata uang asal atau Pricelist Rupiah.</div>
        <button class="btn btn-primary" style="margin-top:14px">Tambah variasi</button>
    </form>
@endif
@endsection
