@extends('layouts.app')
@section('title','GBUKPOP x KRJASTIP — GBUKR')
@section('content')
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-copy">
            <div class="eyebrow">GBUKPOP × KRJASTIP</div>
            <h1>Order lebih rapi, cek semuanya lebih gampang.</h1>
            <p>Lihat produk, hitung estimasi harga, cek tagihan, dan pantau status barang dari GBUKR.</p>
            <div class="actions">
                <a class="btn btn-primary" href="{{ route('catalog.index') }}">Lihat barang</a>
                <a class="btn btn-neutral" href="{{ route('calculator') }}">Hitung estimasi</a>
            </div>
        </div>
        <div class="hero-card">
            <div class="hero-card-inner">
                <div class="mini-brand">
                    <img src="{{ asset('images/gbukpop.jpeg') }}" alt="GBUKPOP">
                    <img src="{{ asset('images/krjastip.jpeg') }}" alt="KRJASTIP">
                </div>
                <div class="stats-strip">
                    <div class="stat-chip"><strong>Shop</strong><span>PO &amp; ready stock</span></div>
                    <div class="stat-chip"><strong>Tagihan</strong><span>cek pembayaran</span></div>
                    <div class="stat-chip"><strong>Tracking</strong><span>pantau barang</span></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section quick-access-section">
    <div class="container">
        <div class="quick-access-grid">
            <a class="quick-access-card qa-pink" href="{{ route('catalog.index') }}">
                <span class="quick-access-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M6 8h12l-1 11H7L6 8Zm3 0V6a3 3 0 0 1 6 0v2"/></svg>
                </span>
                <span><strong>Shop</strong><small>PO &amp; Ready Stock</small></span>
            </a>
            <a class="quick-access-card qa-blue" href="{{ route('calculator') }}">
                <span class="quick-access-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 7h8M8 11h2M14 11h2M8 15h2M14 15h2M8 18h2M14 18h2"/></svg>
                </span>
                <span><strong>Kalkulator</strong><small>Hitung estimasi harga</small></span>
            </a>
            <a class="quick-access-card qa-mint" href="{{ route('tracking') }}">
                <span class="quick-access-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M3 7h11v10H3zM14 10h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>
                </span>
                <span><strong>Tracking</strong><small>Cek perjalanan barang</small></span>
            </a>
            @auth
                <a class="quick-access-card qa-lilac" href="{{ auth()->user()->canAccessAdmin() && !auth()->user()->isOwner() && !in_array(session('acting_as'), ['admin','customer'], true) ? route('auth.mode') : (auth()->user()->isStaff()?route('owner.dashboard'):route('customer.dashboard')) }}">
                    <span class="quick-access-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                    </span>
                    <span><strong>Dashboard</strong><small>Buka akunmu</small></span>
                </a>
            @else
                <a class="quick-access-card qa-lilac" href="{{ route('login') }}">
                    <span class="quick-access-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M10 17l5-5-5-5M15 12H3M13 3h7a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1h-7"/></svg>
                    </span>
                    <span><strong>Masuk</strong><small>Cek order &amp; tagihanmu</small></span>
                </a>
            @endauth
        </div>
    </div>
</section>

@if($products->count())
<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <h2>Baru dibuka</h2>
                <p>PO dan ready stock yang sedang tersedia.</p>
            </div>
            <a class="btn btn-soft" href="{{ route('catalog.index') }}">Lihat semua</a>
        </div>
        <div class="grid grid-3">
            @foreach($products as $product)
                <a class="card product-card" href="{{ route('catalog.show',$product) }}">
                    <div class="product-img">
                        @if($product->image_path)
                            <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}">
                        @else
                            <div class="noimg">{{ strtoupper($product->type) }}</div>
                        @endif
                    </div>
                    <div class="product-body">
                        <div class="status-line">
                            <span class="badge">{{ strtoupper($product->type) }}</span>
                            <span class="badge gray">{{ $product->country->currency_code }}</span>
                        </div>
                        <div class="card-title" style="margin-top:10px">{{ $product->name }}</div>
                        <div class="muted small">{{ $product->country->name }}</div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection
