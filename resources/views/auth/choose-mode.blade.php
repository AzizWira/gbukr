@extends('layouts.app')
@section('title', 'Pilih Mode — GBUKR')

@section('content')
<div class="auth-wrap">
    <div class="auth-card mode-card">
        <span class="auth-pill">GBUKR · Pilih Mode</span>
        <h1>Mau masuk sebagai apa?</h1>
        <p class="muted">Akunmu punya akses Customer dan Admin. Pilih mode sesuai yang ingin kamu kerjakan sekarang.</p>

        <div class="mode-grid">
            <form method="post" action="{{ route('auth.mode.store') }}" class="mode-option mode-customer">
                @csrf
                <input type="hidden" name="mode" value="customer">
                <div class="mode-icon">♡</div>
                <h3>Customer</h3>
                <p class="small muted">Belanja, keranjang, checkout, tagihan, pembayaran, dan order pribadi.</p>
                <button class="btn btn-primary" type="submit">Masuk sebagai Customer</button>
            </form>

            <form method="post" action="{{ route('auth.mode.store') }}" class="mode-option mode-admin">
                @csrf
                <input type="hidden" name="mode" value="admin">
                <div class="mode-icon">✦</div>
                <h3>Admin</h3>
                <p class="small muted">Masuk ke operasional Batch dan Tracking sesuai akses Admin.</p>
                <button class="btn btn-neutral" type="submit">Masuk sebagai Admin</button>
            </form>
        </div>
    </div>
</div>
@endsection
