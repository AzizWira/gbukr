@extends('layouts.app')
@section('title','Buat Password Baru')
@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <span class="auth-pill">GBUKR · Reset Password</span>
        <h1>Buat password baru</h1>
        <p class="muted">Password baru hanya akan diterapkan ke akun yang menerima link reset ini.</p>

        <div class="auth-help" style="margin-bottom:16px">
            <strong>Email akun</strong>
            <div style="margin-top:4px;color:var(--muted)">{{ $email }}</div>
            <div class="help" style="margin-top:6px">Email dikunci oleh sistem dan tidak dikirim kembali dari form.</div>
        </div>

        <form method="post" action="{{ route('password.update') }}">
            @csrf
            <div class="field">
                <label>Password baru</label>
                <input class="input" type="password" name="password" required autocomplete="new-password">
                <div class="help">Minimal 8 karakter, mengandung huruf dan angka.</div>
            </div>
            <div class="field" style="margin-top:12px">
                <label>Ulangi password</label>
                <input class="input" type="password" name="password_confirmation" required autocomplete="new-password">
            </div>
            <button class="btn btn-primary" style="width:100%;margin-top:16px">Simpan password baru</button>
        </form>
    </div>
</div>
@endsection
