@extends('layouts.app')
@section('title','Daftar — GBUKPOP x KRJASTIP')
@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <span class="auth-pill">GBUKR · GBUKPOP x KRJASTIP</span>
        <h1>Buat akun</h1>
        <p class="muted">Satu akun untuk semua order, tagihan, pembayaran, dan tracking di GBUKPOP x KRJASTIP.</p>

        <a class="google-btn" href="{{ route('google.redirect') }}">@include('partials.google-icon') Daftar dengan Google</a>

        <div class="divider-text">atau email</div>

        <form method="post" action="{{ route('register.store') }}">
            @csrf
            <div class="field">
                <label>Nama</label>
                <input class="input" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="field" style="margin-top:12px">
                <label>Email</label>
                <input class="input" type="email" name="email" value="{{ old('email') }}" required>
            </div>
            <div class="field" style="margin-top:12px">
                <label>Password</label>
                <input class="input" type="password" name="password" required>
                <div class="help">Minimal 8 karakter, berisi huruf dan angka.</div>
            </div>
            <div class="field" style="margin-top:12px">
                <label>Ulangi password</label>
                <input class="input" type="password" name="password_confirmation" required>
            </div>
            <button class="btn btn-primary" style="width:100%;margin-top:18px">Daftar</button>
        </form>

        <div class="auth-help">
            <strong>Kenapa pakai akun?</strong>
            <ul>
                <li>biar semua order milikmu tersimpan di satu dashboard;</li>
                <li>lebih gampang melihat tagihan dan upload bukti bayar;</li>
                <li>lebih mudah cek status barang kapan saja.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
