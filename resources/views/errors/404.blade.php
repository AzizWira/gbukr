@extends('layouts.app')
@section('title','Halaman tidak ditemukan')
@section('content')<div class="auth-wrap"><div class="auth-card"><div class="eyebrow">404</div><h1>Halaman tidak ditemukan</h1><p class="muted">Halaman yang kamu cari mungkin sudah dipindahkan atau tidak tersedia.</p><a class="btn btn-primary" href="{{ route('home') }}">Kembali ke beranda</a></div></div>@endsection
