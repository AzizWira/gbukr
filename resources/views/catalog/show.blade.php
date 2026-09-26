@extends('layouts.app')
@section('title', $product->name . ' — GBUKPOP x KRJASTIP')

@section('content')
<section class="section">
    <div class="container product-detail-grid">
        <div class="card product-detail-image">
            @if($product->image_path)
                <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}">
            @else
                <div class="product-placeholder">
                    <div class="logo-pair">
                        <img src="{{ asset('images/gbukpop.jpeg') }}" alt="GBUKPOP">
                        <img src="{{ asset('images/krjastip.jpeg') }}" alt="KRJASTIP">
                    </div>
                    <span class="small muted">Foto produk belum tersedia</span>
                </div>
            @endif
        </div>

        <div class="product-detail-copy">
            <div class="status-line">
                <span class="badge">{{ strtoupper($product->type) }}</span>
                <span class="badge gray">{{ $product->country->name }}</span>
                @if($product->free_shipping)<span class="badge ok">Free Shipping</span>@endif
            </div>

            <h1>{{ $product->name }}</h1>
            @if($product->description)<p class="muted">{{ $product->description }}</p>@endif

            @if($product->type === 'po' && $product->preorder)
                <div class="notice small">Close PO: <strong>{{ $product->preorder->close_at?->translatedFormat('d F Y, H.i') ?? 'Belum ditentukan' }}</strong></div>
            @endif

            <div class="product-rate-panel">
                <div><strong>Rate aktif:</strong> {{ rtrim(rtrim(number_format((float)$product->country->rate, 4, ',', '.'), '0'), ',') }} <span class="small muted">({{ $product->country->moneySymbol() }} {{ $product->country->currency_code }})</span></div>
                <div><strong>Fee per barang:</strong> Rp{{ number_format((int)$product->item_fee_idr, 0, ',', '.') }}</div>
            </div>

            <p class="product-price-note">
                Harga dapat naik atau turun mengikuti rate, estimasi berat, dan shipping aktual.
                @if(($product->tax_status ?? 'excluded') === 'included_estimate')
                    Harga saat ini sudah memasukkan estimasi tax, tetapi tetap dapat disesuaikan jika biaya aktual berbeda.
                @else
                    Harga yang tertera merupakan harga bersih sebelum tax/pajak. Tagihan tambahan dapat diberikan setelah biaya aktual diketahui.
                @endif
            </p>

            <div class="product-info-list">
                <span>{{ ($product->tax_status ?? 'excluded') === 'included_estimate' ? '✅ Sudah termasuk estimasi tax' : '⚠️ Belum termasuk tax' }}</span>
                @if($product->free_shipping)<span>✅ Free Shipping</span>@endif
                @if(in_array($product->payment_scheme['type'] ?? null, ['dp','cicilan'], true))<span>✅ Cicilan DP</span>@endif
                @if($product->apply_fansign)<span>✅ Apply Fansign</span>@endif
                @if($product->location_note)<span>📍 {{ $product->location_note }}</span>@endif
                @if($product->event_date)<span>📅 {{ $product->event_date->translatedFormat('d F Y') }}</span>@endif
            </div>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="container">
        <div class="section-head">
            <div>
                <h2>Pilihan versi</h2>
                <p>Pilih versi sesuai website sumber dan kebutuhanmu.</p>
            </div>
        </div>

        <div class="variant-groups">
            @forelse($product->variants->where('active', true)->groupBy(fn($v) => $v->source_label ?: 'Pilihan') as $source => $variants)
                <div class="card variant-public-group">
                    <div class="variant-public-title">{{ $source }}</div>
                    @foreach($variants as $variant)
                        @php
                            $displayPrice = $variant->price_idr !== null
                                ? (int)$variant->price_idr
                                : (int)round(((float)$variant->price_foreign * (float)$product->country->rate) + (int)$product->item_fee_idr);
                        @endphp
                        <div class="variant-public-row">
                            <div>
                                <strong>{{ $variant->name }}</strong>
                                @if($variant->details)<div class="small muted">{{ $variant->details }}</div>@endif
                                @if($variant->price_foreign !== null)
                                    <div class="small muted">Harga asal {{ $product->country->moneySymbol() }}{{ number_format((float)$variant->price_foreign, 0, ',', '.') }}</div>
                                @endif
                            </div>
                            <div class="variant-public-values">
                                <span>{{ $variant->estimated_weight_grams !== null ? number_format($variant->estimated_weight_grams,0,',','.').'gr' : '-' }}</span>
                                <strong>Rp{{ number_format($displayPrice,0,',','.') }}</strong>
                                <span>{{ $variant->dp_amount_idr ? 'DP Rp'.number_format($variant->dp_amount_idr,0,',','.') : (($product->payment_scheme['type'] ?? 'full') === 'full' ? 'Full Payment' : 'DP sesuai ketentuan') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @empty
                <div class="empty">Variasi produk belum tersedia.</div>
            @endforelse
        </div>

        @guest
            <div class="card" style="margin-top:18px"><a class="btn btn-primary" href="{{ route('login') }}">Masuk untuk membeli</a></div>
        @else
            @if(auth()->user()->isCustomerMode())
                <form method="post" action="{{ route('cart.store') }}" class="card" style="margin-top:18px">
                    @csrf
                    <div class="form-grid">
                        <div class="field">
                            <label>Variasi</label>
                            <select class="select" name="variant_id" required>
                                @foreach($product->variants->where('active', true) as $variant)
                                    @php
                                        $optionPrice = $variant->price_idr !== null
                                            ? (int)$variant->price_idr
                                            : (int)round(((float)$variant->price_foreign * (float)$product->country->rate) + (int)$product->item_fee_idr);
                                    @endphp
                                    <option value="{{ $variant->id }}">{{ $variant->source_label ? $variant->source_label.' · ' : '' }}{{ $variant->name }} — Rp{{ number_format($optionPrice,0,',','.') }}{{ $variant->stock !== null ? ' · stok '.$variant->stock : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>Jumlah</label>
                            <input class="input" type="number" name="qty" min="1" max="99" value="1" required>
                        </div>
                    </div>
                    <div class="actions" style="margin-top:16px">
                        @if($product->variants->where('active', true)->isEmpty() || ($product->type === 'po' && !$product->preorder?->isOpen()))
                            <button class="btn btn-neutral" disabled>Tidak tersedia</button>
                        @else
                            <button class="btn btn-primary">Masukkan keranjang</button>
                        @endif
                    </div>
                </form>
            @elseif(auth()->user()->canAccessAdmin() && !auth()->user()->isOwner())
                <div class="notice small" style="margin-top:18px">Kamu sedang menggunakan mode Admin. <a href="{{ route('auth.mode') }}" style="color:var(--blue);font-weight:800">Beralih ke mode Customer</a> untuk berbelanja.</div>
            @endif
        @endguest
    </div>
</section>
@endsection
