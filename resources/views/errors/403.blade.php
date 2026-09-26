@extends('layouts.app')
@section('title','Akses tidak tersedia')
@section('content')<div class="auth-wrap"><div class="auth-card"><div class="eyebrow">403</div><h1>Akses tidak tersedia</h1><p class="muted">Halaman atau link ini tidak dapat digunakan untuk akunmu. Jika ini link verifikasi, minta link baru dari halaman verifikasi email.</p><a class="btn btn-primary" href="{{ route('home') }}">Kembali ke beranda</a></div></div>@endsection
