<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title','GBUKPOP x KRJASTIP')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<header class="topbar">
    <div class="container nav">
        <a class="brand" href="{{ route('home') }}" aria-label="GBUKR - GBUKPOP x KRJASTIP">
            <img src="{{ asset('images/gbukpop.jpeg') }}" alt="GBUKPOP">
            <span class="divider"></span>
            <img src="{{ asset('images/krjastip.jpeg') }}" alt="KRJASTIP">
            <div class="brand-copy">
                <span class="brand-short">GBUKR</span>
                <span class="brand-sub">GBUKPOP x KRJASTIP</span>
            </div>
        </a>

        <nav class="navlinks" data-nav>
            <a class="{{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Home</a>
            <a class="{{ request()->routeIs('catalog.*') ? 'active' : '' }}" href="{{ route('catalog.index') }}">Shop</a>
            <a class="{{ request()->routeIs('calculator') ? 'active' : '' }}" href="{{ route('calculator') }}">Kalkulator</a>
            <a class="{{ request()->routeIs('tracking') ? 'active' : '' }}" href="{{ route('tracking') }}">Tracking</a>
            @auth
                @if(auth()->user()->isCustomerMode())
                    <div class="nav-cart-wrap" data-cart-nav>
                        <a class="{{ request()->routeIs('cart.*') ? 'active' : '' }}" href="{{ route('cart.index') }}">Keranjang <span class="cart-count">{{ array_sum(session('cart', [])) }}</span></a>
                        <div class="nav-cart-popover" data-cart-popover data-url="{{ route('cart.summary') }}" hidden></div>
                    </div>
                @endif
            @endauth
            @auth
                @if(auth()->user()->isOwner() || !auth()->user()->canAccessAdmin() || in_array(session('acting_as'), ['admin', 'customer'], true))
                    <a class="{{ request()->routeIs('customer.*') || request()->routeIs('owner.*') ? 'active' : '' }}" href="{{ auth()->user()->isStaff() ? route('owner.dashboard') : route('customer.dashboard') }}">Dashboard</a>
                @endif
            @endauth
        </nav>

        <div class="nav-actions">
            @guest
                <a class="btn btn-soft" href="{{ route('login') }}">Masuk</a>
                <a class="btn btn-primary" href="{{ route('register') }}">Daftar</a>
            @else
                @if(auth()->user()->canAccessAdmin() && !auth()->user()->isOwner() && in_array(session('acting_as'), ['admin', 'customer'], true))
                    <a class="btn btn-soft mode-switch" href="{{ route('auth.mode') }}">Mode: {{ auth()->user()->isStaff() ? 'Admin' : 'Customer' }}</a>
                @endif
                <form method="post" action="{{ route('logout') }}" data-confirm="Keluar dari akun? Perubahan pada form yang belum disimpan akan hilang." data-no-dirty-guard>
                    @csrf
                    <button class="btn btn-neutral" type="submit">Keluar</button>
                </form>
            @endguest
        </div>

        <button class="mobile-toggle" type="button" data-nav-toggle aria-label="Buka menu" aria-expanded="false">☰</button>
    </div>
</header>

<div class="toast-stack" aria-live="polite" aria-atomic="true">
    <div class="toast toast-processing" data-processing-toast hidden aria-hidden="true">
        <span class="loading-spinner" aria-hidden="true"></span>
        <div><strong data-processing-title>Memproses…</strong><div class="small muted">Jangan kirim form yang sama berulang kali.</div></div>
    </div>
    <div class="toast toast-info unsaved-toast" data-unsaved-toast hidden aria-hidden="true">
        <div><strong>Perubahan belum disimpan</strong><div class="small">Simpan form atau batalkan perubahan sebelum berpindah halaman.</div></div>
    </div>
    @if(session('success'))
        <div class="toast toast-success" data-toast data-timeout="4500">
            <div>{{ session('success') }} @if(session('cart_added')) <a class="toast-action" href="{{ route('cart.index') }}">Lihat Keranjang</a> @endif</div>
            <button type="button" class="toast-close" data-toast-close aria-label="Tutup notifikasi">×</button>
        </div>
    @endif

    @if(session('info'))
        <div class="toast toast-info" data-toast data-timeout="4500">
            <div>{{ session('info') }}</div>
            <button type="button" class="toast-close" data-toast-close aria-label="Tutup notifikasi">×</button>
        </div>
    @endif

    @if($errors->any())
        <div class="toast toast-error" data-toast data-timeout="7500">
            <div>
                <strong>Ada yang perlu diperiksa.</strong>
                <div class="small">
                    @foreach($errors->all() as $message)
                        <div>{{ $message }}</div>
                    @endforeach
                </div>
            </div>
            <button type="button" class="toast-close" data-toast-close aria-label="Tutup notifikasi">×</button>
        </div>
    @endif
</div>

<main class="site-main">
    @yield('content')
</main>

@if(!View::hasSection('hide_footer'))
<footer class="footer">
    <div class="container footer-inner">
        <div class="brand footer-brand">
            <img src="{{ asset('images/gbukpop.jpeg') }}" alt="GBUKPOP">
            <span class="divider"></span>
            <img src="{{ asset('images/krjastip.jpeg') }}" alt="KRJASTIP">
            <div class="brand-copy">
                <span class="brand-short">GBUKR</span>
                <span class="brand-sub">GBUKPOP x KRJASTIP</span>
            </div>
        </div>
        <nav class="footer-links" aria-label="Navigasi footer">
            <a href="{{ route('home') }}">Home</a>
            <a href="{{ route('catalog.index') }}">Shop</a>
            <a href="{{ route('calculator') }}">Kalkulator</a>
            <a href="{{ route('tracking') }}">Tracking</a>
        </nav>
    </div>
</footer>
@endif

<dialog id="confirm-dialog" class="dialog">
    <div class="dialog-body">
        <h3 id="confirm-title">Konfirmasi</h3>
        <p id="confirm-message" class="muted">Lanjutkan tindakan ini?</p>
        <div class="dialog-actions">
            <button id="confirm-no" class="btn btn-neutral" type="button">Batal</button>
            <button id="confirm-yes" class="btn btn-primary" type="button">Lanjutkan</button>
        </div>
    </div>
</dialog>

<script src="{{ asset('js/app.js') }}" defer></script>
@stack('scripts')
</body>
</html>
