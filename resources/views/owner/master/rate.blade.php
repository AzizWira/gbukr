@extends('layouts.dashboard')
@section('title','Rate & Mata Uang')

@section('dashboard')
<div class="page-head">
    <div>
        <div class="eyebrow">RATE</div>
        <h1>Rate & mata uang</h1>
        <p class="muted">Atur rate aktif, simbol mata uang, dan fee admin per negara.</p>
    </div>
</div>

<div class="lifecycle-note small">
    <strong>Aturan hapus:</strong> negara hanya dapat dihapus permanen jika belum terhubung ke produk, Batch, tracking, shipping, atau warehouse. Jika sudah dipakai, gunakan <strong>Nonaktifkan</strong> agar histori tetap aman.
</div>

<div class="grid grid-2">
    @foreach($countries as $country)
        @php
            $usage = collect($countryUsage->get($country->id, []))->filter(fn($count) => (int)$count > 0);
            $used = (int) $usage->sum();
        @endphp
        <div class="card">
            <form method="post" action="{{ route('owner.master.country',$country) }}">
                @csrf
                <input type="hidden" name="code" value="{{ $country->code }}">
                <input type="hidden" name="name" value="{{ $country->name }}">
                <input type="hidden" name="currency_code" value="{{ $country->currency_code }}">
                <div class="section-head">
                    <div><h3>{{ $country->name }}</h3><p>{{ $country->currency_code }} · <span class="badge {{ $country->active ? 'ok' : 'gray' }}">{{ $country->active ? 'Aktif' : 'Nonaktif' }}</span></p></div>
                    <span class="currency-bubble">{{ $country->moneySymbol() }}</span>
                </div>
                <div class="form-grid">
                    <div class="field"><label>Simbol mata uang</label><input class="input" name="currency_symbol" value="{{ $country->moneySymbol() }}" required maxlength="12"></div>
                    <div class="field"><label>Rate aktif</label><input class="input" name="rate" type="number" step="0.0001" min="0.0001" value="{{ $country->rate }}" required></div>
                    <div class="field full"><label>Fee Admin IDR</label><input class="input" type="number" min="0" name="admin_fee_idr" value="{{ $country->admin_fee_idr }}" required></div>
                </div>
                <button class="btn btn-primary" style="margin-top:14px">Simpan rate</button>
            </form>
            <div class="actions" style="margin-top:10px">
                <form method="post" action="{{ route('owner.master.country.toggle',$country) }}" data-confirm="{{ $country->active ? 'Nonaktifkan negara ini? Negara tidak akan muncul untuk data baru, tetapi histori lama tetap aman.' : 'Aktifkan kembali negara ini?' }}">
                    @csrf
                    <button class="btn btn-sm {{ $country->active ? 'btn-danger' : 'btn-soft' }}">{{ $country->active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                </form>
                @if($used === 0)
                    <form method="post" action="{{ route('owner.master.country.destroy',$country) }}" data-confirm-title="Hapus permanen" data-confirm="Hapus negara {{ $country->name }} ({{ $country->currency_code }}) secara permanen? Data ini belum memiliki relasi ke produk, Batch, tracking, shipping, atau warehouse. Tindakan tidak dapat dibatalkan.">
                        @csrf
                        @method('delete')
                        <button class="btn btn-sm btn-danger">Hapus</button>
                    </form>
                @endif
            </div>
            @if($used > 0)
                <div class="delete-note">Hapus permanen tidak tersedia karena masih dipakai: {{ $usage->map(fn($count,$label) => $count.' '.$label)->implode(', ') }}.</div>
            @endif
        </div>
    @endforeach

    <form method="post" action="{{ route('owner.master.country') }}" class="card card-blue">
        @csrf
        <h3>Tambah negara / mata uang</h3>
        <div class="form-grid" style="margin-top:12px">
            <div class="field"><label>Kode negara</label><input class="input" name="code" maxlength="8" required></div>
            <div class="field"><label>Nama negara</label><input class="input" name="name" required></div>
            <div class="field"><label>Kode mata uang</label><input class="input" name="currency_code" maxlength="8" required></div>
            <div class="field"><label>Simbol</label><input class="input" name="currency_symbol" maxlength="12" required></div>
            <div class="field"><label>Rate</label><input class="input" name="rate" type="number" step="0.0001" min="0.0001" required></div>
            <div class="field"><label>Fee Admin IDR</label><input class="input" name="admin_fee_idr" type="number" min="0" value="0" required></div>
        </div>
        <button class="btn btn-primary" style="margin-top:14px">Tambah</button>
    </form>
</div>
@endsection
