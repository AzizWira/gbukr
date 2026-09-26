@extends('layouts.app')
@section('title','Verifikasi Email')
@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <span class="auth-pill">GBUKR · Verifikasi Email</span>
        <div class="eyebrow">SATU LANGKAH LAGI</div>
        <h1>Verifikasi emailmu</h1>
        <p class="muted">Kami sudah mengirim link verifikasi ke <strong>{{ auth()->user()->email }}</strong>. Buka link tersebut sebelum melanjutkan checkout dan dashboard.</p>
        <form method="post" action="{{ route('verification.send') }}">
            @csrf
            <button class="btn btn-primary" style="width:100%">Kirim ulang email verifikasi</button>
        </form>
    </div>
</div>
@endsection
