@extends('layouts.dashboard')
@section('title','Keranjang')
@section('dashboard')
<div class="page-head"><div><div class="eyebrow">KERANJANG</div><h1>Keranjang belanja</h1><p class="muted">Atur jumlah di sini. Kamu bisa kembali ke Shop untuk menambah barang lain tanpa kehilangan isi keranjang.</p></div><a class="btn btn-soft" href="{{ route('catalog.index') }}">+ Tambah barang lain</a></div>
@if($cart->count())
<div class="cart-layout"><div class="cart-items">
@foreach($cart as $row)
@php
    $variant = $row['variant'];
    $product = $variant->product;
    $unit = $variant->price_idr !== null ? (int) $variant->price_idr : (int) round(((float) $variant->price_foreign * (float) $product->country->rate) + (int) $product->item_fee_idr);
@endphp
<div class="cart-item-card"><div class="cart-thumb">@if($product->image_path)<img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}">@else<span>{{ strtoupper($product->type) }}</span>@endif</div><div class="cart-item-copy"><div class="status-line"><span class="badge">{{ strtoupper($product->type) }}</span><span class="badge gray">{{ $product->country->name }}</span></div><h3>{{ $product->name }}</h3><div class="small muted">{{ $variant->source_label ? $variant->source_label.' · ' : '' }}{{ $variant->name }}</div><div class="money cart-unit-price">Rp{{ number_format($unit,0,',','.') }} / item</div></div><div class="cart-item-actions"><form method="post" action="{{ route('cart.update',$variant) }}" class="cart-qty-form">@csrf @method('patch')<label class="small muted">Jumlah</label><div class="actions"><input class="input" type="number" name="qty" min="1" max="99" value="{{ $row['qty'] }}"><button class="btn btn-soft btn-sm">Update</button></div></form><div class="money">Rp{{ number_format($unit*$row['qty'],0,',','.') }}</div><form method="post" action="{{ route('cart.destroy',$variant) }}" data-confirm="Hapus barang ini dari keranjang?">@csrf @method('delete')<button class="btn btn-danger btn-sm">Hapus</button></form></div></div>
@endforeach
</div><aside class="card cart-summary"><div class="eyebrow">RINGKASAN</div><h3>{{ $cart->sum('qty') }} item di keranjang</h3><p class="small muted">Nominal final mengikuti validasi stok, rate snapshot, dan aturan pembayaran pada saat checkout.</p><a class="btn btn-primary" style="width:100%" href="{{ route('checkout.show') }}">Lanjut checkout</a><a class="btn btn-neutral" style="width:100%;margin-top:10px" href="{{ route('catalog.index') }}">Lanjut belanja</a></aside></div>
@else<div class="empty cart-empty"><div class="empty-icon">🛍️</div><h3>Keranjang masih kosong</h3><p>Pilih produk atau versi yang kamu mau dari Shop.</p><a class="btn btn-primary" href="{{ route('catalog.index') }}">Buka Shop</a></div>@endif
@endsection
