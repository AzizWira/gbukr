@extends('layouts.dashboard')
@section('title','Rate & Data')

@section('dashboard')
<div class="page-head">
    <div>
        <div class="eyebrow">MASTER DATA</div>
        <h1>Rate, mata uang & data operasional</h1>
        <p class="muted">Rate, simbol mata uang, fee, shipping, warehouse, GO, dan rekening dapat diubah tanpa menyentuh kode.</p>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <div class="section-head">
            <div>
                <h3>Negara, mata uang & rate</h3>
                <p>Simbol digunakan langsung pada input harga dan tampilan nominal mata uang asal.</p>
            </div>
        </div>

        @foreach($countries as $country)
            <form method="post" action="{{ route('owner.master.country', $country) }}" class="master-country-row">
                @csrf
                <input type="hidden" name="code" value="{{ $country->code }}">
                <input type="hidden" name="name" value="{{ $country->name }}">
                <input type="hidden" name="currency_code" value="{{ $country->currency_code }}">

                <div class="master-country-head">
                    <strong>{{ $country->name }}</strong>
                    <span class="badge gray">{{ $country->currency_code }}</span>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label>Simbol mata uang</label>
                        <input class="input" name="currency_symbol" value="{{ old('currency_symbol', $country->moneySymbol()) }}" required maxlength="12" placeholder="₩">
                    </div>
                    <div class="field">
                        <label>Rate</label>
                        <input class="input" name="rate" type="number" step="0.0001" min="0.0001" value="{{ old('rate', $country->rate) }}" required>
                    </div>
                    <div class="field full">
                        <label>Fee Admin IDR</label>
                        <input class="input" type="number" min="0" name="admin_fee_idr" value="{{ old('admin_fee_idr', $country->admin_fee_idr) }}" required>
                    </div>
                </div>

                <button class="btn btn-soft btn-sm" style="margin-top:12px">Simpan {{ $country->code }}</button>
            </form>
        @endforeach

        <div class="form-section-title">Tambah negara / mata uang</div>
        <form method="post" action="{{ route('owner.master.country') }}">
            @csrf
            <div class="form-grid">
                <div class="field">
                    <label>Kode negara</label>
                    <input class="input" name="code" maxlength="8" required placeholder="KR">
                </div>
                <div class="field">
                    <label>Nama negara</label>
                    <input class="input" name="name" maxlength="80" required placeholder="Korea Selatan">
                </div>
                <div class="field">
                    <label>Kode mata uang</label>
                    <input class="input" name="currency_code" maxlength="8" required placeholder="KRW">
                </div>
                <div class="field">
                    <label>Simbol mata uang</label>
                    <input class="input" name="currency_symbol" maxlength="12" required placeholder="₩">
                </div>
                <div class="field">
                    <label>Rate</label>
                    <input class="input" type="number" step="0.0001" min="0.0001" name="rate" required>
                </div>
                <div class="field">
                    <label>Fee Admin IDR</label>
                    <input class="input" type="number" min="0" name="admin_fee_idr" value="0" required>
                </div>
            </div>
            <button class="btn btn-primary" style="margin-top:14px">Tambah negara</button>
        </form>
    </div>

    <div class="card">
        <h3>Opsi shipping</h3>
        @foreach($countries as $country)
            @foreach($country->shippingOptions as $shipping)
                <div class="summary-row">
                    <div>
                        <strong>{{ $shipping->label }}</strong>
                        <div class="small muted">
                            {{ $country->name }} · {{ (float)$shipping->amount_foreign === 0.0 ? 'Free Shipping' : $country->moneySymbol().number_format((float)$shipping->amount_foreign, 0, ',', '.') }}
                        </div>
                    </div>
                    <form method="post" action="{{ route('owner.master.shipping.toggle', $shipping) }}" data-confirm="{{ $shipping->active ? 'Nonaktifkan opsi shipping ini?' : 'Aktifkan kembali opsi shipping ini?' }}">
                        @csrf
                        <button class="btn btn-sm {{ $shipping->active ? 'btn-danger' : 'btn-soft' }}">{{ $shipping->active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                    </form>
                </div>
            @endforeach
        @endforeach
    </div>

    <form class="card" method="post" action="{{ route('owner.master.shipping') }}">
        @csrf
        <h3>Tambah opsi shipping</h3>
        <div class="field" style="margin-top:12px">
            <label>Negara</label>
            <select class="select" name="country_id" required>
                @foreach($countries as $country)
                    <option value="{{ $country->id }}">{{ $country->name }} · {{ $country->moneySymbol() }}</option>
                @endforeach
            </select>
        </div>
        <div class="field" style="margin-top:10px">
            <label>Label</label>
            <input class="input" name="label" required placeholder="Standard / Free Shipping">
        </div>
        <div class="field" style="margin-top:10px">
            <label>Nominal mata uang asal</label>
            <input class="input" type="number" step="0.01" min="0" name="amount_foreign" required>
            <div class="help">Isi 0 untuk Free Shipping.</div>
        </div>
        <button class="btn btn-primary" style="margin-top:14px">Tambah</button>
    </form>

    <div class="card">
        <h3>Warehouse internal</h3>
        @foreach($countries as $country)
            @foreach($country->warehouses as $warehouse)
                <div class="summary-row">
                    <div>
                        <strong>{{ $warehouse->code }}</strong>
                        <div class="small muted">{{ $country->name }} · {{ $warehouse->name }}</div>
                    </div>
                    <span class="badge">Internal</span>
                </div>
            @endforeach
        @endforeach

        <form method="post" action="{{ route('owner.master.warehouse') }}" style="margin-top:16px">
            @csrf
            <div class="form-grid">
                <div class="field">
                    <label>Negara</label>
                    <select class="select" name="country_id" required>
                        @foreach($countries as $country)<option value="{{ $country->id }}">{{ $country->name }}</option>@endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Kode</label>
                    <input class="input" name="code" required placeholder="KR-WH02">
                </div>
                <div class="field">
                    <label>Nama</label>
                    <input class="input" name="name">
                </div>
                <div class="field">
                    <label>Catatan</label>
                    <input class="input" name="notes">
                </div>
            </div>
            <button class="btn btn-primary" style="margin-top:14px">Tambah warehouse</button>
        </form>
    </div>

    <div class="card">
        <h3>GO</h3>
        @foreach($groups as $group)
            <div class="summary-row">
                <strong>{{ $group->name }}</strong>
                <span class="badge {{ $group->status === 'active' ? 'ok' : 'gray' }}">{{ $group->status }}</span>
            </div>
        @endforeach

        <form method="post" action="{{ route('owner.master.go') }}" style="margin-top:16px">
            @csrf
            <div class="field">
                <label>Nama GO</label>
                <input class="input" name="name" required>
            </div>
            <div class="field" style="margin-top:10px">
                <label>Catatan</label>
                <input class="input" name="notes">
            </div>
            <button class="btn btn-primary" style="margin-top:14px">Tambah GO</button>
        </form>
    </div>

    <div class="card" style="grid-column:1/-1">
        <h3>Rekening pembayaran</h3>
        @foreach($banks as $bank)
            <div class="summary-row">
                <div>
                    <strong>{{ $bank->bank_name }} · {{ $bank->account_number }}</strong>
                    <div class="small muted">{{ $bank->account_name }}</div>
                </div>
                <span class="badge {{ $bank->active ? 'ok' : 'gray' }}">{{ $bank->active ? 'Aktif' : 'Nonaktif' }}</span>
            </div>
        @endforeach

        <form method="post" action="{{ route('owner.master.bank') }}" style="margin-top:16px">
            @csrf
            <div class="form-grid">
                <div class="field">
                    <label>Bank</label>
                    <input class="input" name="bank_name" required>
                </div>
                <div class="field">
                    <label>Nomor rekening</label>
                    <input class="input" name="account_number" required>
                </div>
                <div class="field">
                    <label>Nama pemilik</label>
                    <input class="input" name="account_name" required>
                </div>
                <div class="field">
                    <label>Instruksi</label>
                    <input class="input" name="instructions">
                </div>
            </div>
            <button class="btn btn-primary" style="margin-top:14px">Tambah rekening</button>
        </form>
    </div>
</div>
@endsection
