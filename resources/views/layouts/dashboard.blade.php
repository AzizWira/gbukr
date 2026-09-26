@extends('layouts.app')
@section('hide_footer', '1')
@section('content')
@php
    $isStaff = auth()->user()->isStaff();
@endphp
<div class="dash-shell">
    <aside class="sidebar">
        <div class="side-brand">
            <div class="brand-copy">
                <span class="brand-short">GBUKR</span>
                <span class="brand-sub">GBUKPOP x KRJASTIP</span>
            </div>
        </div>

        <div class="side-title">{{ $isStaff ? 'Operasional' : 'Akun Saya' }}</div>

        <nav class="side-nav">
            @if($isStaff)
                <a class="{{ request()->routeIs('owner.dashboard') ? 'active' : '' }}" href="{{ route('owner.dashboard') }}">Ringkasan</a>
                <a class="{{ request()->routeIs('owner.batches.*') ? 'active' : '' }}" href="{{ route('owner.batches.index') }}">Batch</a>
                <a class="{{ request()->routeIs('owner.tracking.*') ? 'active' : '' }}" href="{{ route('owner.tracking.index') }}">Tracking</a>

                @if(auth()->user()->isOwner())
                    <a class="{{ request()->routeIs('owner.products.*') ? 'active' : '' }}" href="{{ route('owner.products.index') }}">Produk &amp; PO</a>
                    <a class="{{ request()->routeIs('owner.orders.*') ? 'active' : '' }}" href="{{ route('owner.orders.index') }}">Order</a>
                    <a class="{{ request()->routeIs('owner.customers.*') ? 'active' : '' }}" href="{{ route('owner.customers.index') }}">Customer</a>
                    <a class="{{ request()->routeIs('owner.invoices.*') ? 'active' : '' }}" href="{{ route('owner.invoices.index') }}">Tagihan</a>
                    <a class="{{ request()->routeIs('owner.payments.*') ? 'active' : '' }}" href="{{ route('owner.payments.index') }}">Pembayaran</a>
                    <a class="{{ request()->routeIs('owner.rate.*') ? 'active' : '' }}" href="{{ route('owner.rate.index') }}">Rate</a>
                    <a class="{{ request()->routeIs('owner.data.*') || request()->routeIs('owner.master.*') ? 'active' : '' }}" href="{{ route('owner.data.index') }}">Data Master</a>
                    <a class="{{ request()->routeIs('owner.admins.*') ? 'active' : '' }}" href="{{ route('owner.admins.index') }}">Admin</a>
                    <a class="{{ request()->routeIs('owner.import.*') ? 'active' : '' }}" href="{{ route('owner.import.index') }}">Migrasi</a>
                    <a class="{{ request()->routeIs('owner.export.*') ? 'active' : '' }}" href="{{ route('owner.export.index') }}">Export &amp; Backup</a>
                @endif
            @else
                <a class="{{ request()->routeIs('customer.dashboard') ? 'active' : '' }}" href="{{ route('customer.dashboard') }}">Ringkasan</a>
                <a class="{{ request()->routeIs('customer.orders.*') ? 'active' : '' }}" href="{{ route('customer.orders.index') }}">Order Saya</a>
                <a class="{{ request()->routeIs('customer.invoices.*') ? 'active' : '' }}" href="{{ route('customer.invoices.index') }}">Tagihan Saya</a>
                <a class="{{ request()->routeIs('cart.*') ? 'active' : '' }}" href="{{ route('cart.index') }}">Keranjang <span class="nav-count">{{ array_sum(session('cart', [])) }}</span></a>
                <a class="{{ request()->routeIs('customer.profile*') ? 'active' : '' }}" href="{{ route('customer.profile') }}">Profil</a>
                <a class="{{ request()->routeIs('catalog.*') ? 'active' : '' }}" href="{{ route('catalog.index') }}">Shop</a>
                <a class="{{ request()->routeIs('calculator') ? 'active' : '' }}" href="{{ route('calculator') }}">Kalkulator</a>
            @endif
        </nav>
    </aside>

    <section class="dash-main">
        @yield('dashboard')
    </section>
</div>
@endsection
