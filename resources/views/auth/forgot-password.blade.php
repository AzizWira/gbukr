@extends('layouts.app')
@section('title','Lupa Password')
@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <span class="auth-pill">GBUKR · Bantuan Akun</span>
        <h1>Lupa password?</h1>
        <p class="muted">Masukkan email akunmu. Kalau cocok, link untuk membuat password baru akan dikirim ke email tersebut.</p>
        <form method="post" action="{{ route('password.email') }}">
            @csrf
            <div class="field">
                <label>Email</label>
                <input class="input" type="email" name="email" value="{{ old('email') }}" required>
            </div>
            <button class="btn btn-primary" style="width:100%;margin-top:16px">Kirim link reset</button>
        </form>
    </div>
</div>
@endsection
