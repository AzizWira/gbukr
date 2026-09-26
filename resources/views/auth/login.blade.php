@extends('layouts.app')
@section('title','Masuk — GBUKPOP x KRJASTIP')
@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <span class="auth-pill">GBUKR · GBUKPOP x KRJASTIP</span>
        <div class="logo-pair" style="margin-bottom:18px">
            <img src="{{ asset('images/gbukpop.jpeg') }}" alt="GBUKPOP">
            <img src="{{ asset('images/krjastip.jpeg') }}" alt="KRJASTIP">
        </div>
        <h1>Masuk ke akun</h1>
        <p class="muted">Cek order, tagihan, pembayaran, dan status barangmu dari satu tempat.</p>

        <a class="google-btn" href="{{ route('google.redirect') }}">@include('partials.google-icon') Lanjutkan dengan Google</a>

        <div class="divider-text">atau email</div>

        <form method="post" action="{{ route('login.store') }}">
            @csrf
            <div class="field">
                <label>Email</label>
                <input class="input" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
            </div>
            <div class="field" style="margin-top:12px">
                <label>Password</label>
                <input class="input" type="password" name="password" required autocomplete="current-password">
            </div>
            <div class="actions" style="justify-content:space-between;margin-top:10px">
                <label class="small"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
                <a class="small" style="color:var(--blue);font-weight:700" href="{{ route('password.request') }}">Lupa password?</a>
            </div>
            <button class="btn btn-primary" style="width:100%;margin-top:16px">Masuk</button>
        </form>

        <div class="auth-help">
            <strong>Setelah login kamu bisa:</strong>
            <ul>
                <li>cek order, tagihan, dan pembayaran dengan lebih cepat;</li>
                <li>lihat status barang dari dashboard akunmu;</li>
                <li>login cepat lewat Google kalau lebih nyaman.</li>
            </ul>
        </div>

        <p class="small muted" style="text-align:center;margin-top:18px">Belum punya akun? <a style="color:var(--blue);font-weight:800" href="{{ route('register') }}">Daftar</a></p>
    </div>
</div>
@endsection
