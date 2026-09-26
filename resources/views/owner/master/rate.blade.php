@extends('layouts.dashboard')
@section('title','Rate & Mata Uang')
@section('dashboard')
<div class="page-head"><div><div class="eyebrow">RATE</div><h1>Rate & mata uang</h1><p class="muted">Atur rate aktif, simbol mata uang, dan fee admin per negara.</p></div></div>
<div class="grid grid-2">
@foreach($countries as $country)
<form method="post" action="{{ route('owner.master.country',$country) }}" class="card">
@csrf
<input type="hidden" name="code" value="{{ $country->code }}"><input type="hidden" name="name" value="{{ $country->name }}"><input type="hidden" name="currency_code" value="{{ $country->currency_code }}">
<div class="section-head"><div><h3>{{ $country->name }}</h3><p>{{ $country->currency_code }}</p></div><span class="currency-bubble">{{ $country->moneySymbol() }}</span></div>
<div class="form-grid">
<div class="field"><label>Simbol mata uang</label><input class="input" name="currency_symbol" value="{{ $country->moneySymbol() }}" required maxlength="12"></div>
<div class="field"><label>Rate aktif</label><input class="input" name="rate" type="number" step="0.0001" min="0.0001" value="{{ $country->rate }}" required></div>
<div class="field full"><label>Fee Admin IDR</label><input class="input" type="number" min="0" name="admin_fee_idr" value="{{ $country->admin_fee_idr }}" required></div>
</div><button class="btn btn-primary" style="margin-top:14px">Simpan</button>
</form>
@endforeach
<form method="post" action="{{ route('owner.master.country') }}" class="card card-blue">@csrf
<h3>Tambah negara / mata uang</h3><div class="form-grid" style="margin-top:12px">
<div class="field"><label>Kode negara</label><input class="input" name="code" maxlength="8" required></div>
<div class="field"><label>Nama negara</label><input class="input" name="name" required></div>
<div class="field"><label>Kode mata uang</label><input class="input" name="currency_code" maxlength="8" required></div>
<div class="field"><label>Simbol</label><input class="input" name="currency_symbol" maxlength="12" required></div>
<div class="field"><label>Rate</label><input class="input" name="rate" type="number" step="0.0001" min="0.0001" required></div>
<div class="field"><label>Fee Admin IDR</label><input class="input" name="admin_fee_idr" type="number" min="0" value="0" required></div>
</div><button class="btn btn-primary" style="margin-top:14px">Tambah</button></form>
</div>
@endsection
