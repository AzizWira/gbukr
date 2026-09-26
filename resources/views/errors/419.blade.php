@extends('layouts.app')
@section('title','Sesi berakhir')
@section('content')<div class="auth-wrap"><div class="auth-card"><div class="eyebrow">SESI BERAKHIR</div><h1>Silakan coba lagi</h1><p class="muted">Sesi halaman ini sudah berakhir. Muat ulang halaman lalu ulangi tindakanmu.</p><a class="btn btn-primary" href="{{ url()->previous() }}">Kembali</a></div></div>@endsection
