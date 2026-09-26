@extends('layouts.app')
@section('title','Kalkulator — GBUKPOP x KRJASTIP')

@section('content')
<section class="section">
    <div class="container split">
        <div>
            <div class="eyebrow">KALKULATOR</div>
            <h1 style="font-size:clamp(2rem,4vw,3.4rem)">Cek estimasi sebelum take.</h1>
            <p class="muted">Kalkulator ini terutama untuk memperkirakan harga Batch sebelum take. Masukkan harga bersih barang, pilihan shipping, dan jumlah barang yang ikut barengan.</p>

            <div id="calc-output" class="brand-panel" hidden>
                <div class="small muted">Estimasi</div>
                <div id="calc-total" class="calc-result">Rp0</div>
                <div id="calc-detail" class="small muted"></div>
                <div class="calc-note"><strong>Catatan:</strong> Harga menggunakan estimasi shipping sehingga dapat naik/turun mengikuti shipping aktual. Harga barang merupakan harga bersih negara asal dan belum termasuk tax/pajak.</div>
            </div>
        </div>

        <form id="calculator-form" class="card" action="{{ route('calculator.calculate') }}" data-async-form data-no-dirty-guard>
            @csrf
            <div class="field">
                <label>Negara</label>
                <select id="country_id" class="select" name="country_id" required>
                    @foreach($countries as $country)
                        <option
                            value="{{ $country->id }}"
                            data-currency-symbol="{{ $country->moneySymbol() }}"
                            data-currency-code="{{ $country->currency_code }}"
                        >{{ $country->name }} · {{ $country->currency_code }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field" style="margin-top:12px">
                <label>Harga barang</label>
                <div class="input-prefix-wrap">
                    <span class="input-prefix" data-currency-prefix>{{ $countries->first()?->moneySymbol() }}</span>
                    <input class="input input-with-prefix" name="item_price" type="number" step="0.01" min="0.01" required placeholder="Contoh 20000">
                </div>
            </div>

            <div class="field" style="margin-top:12px">
                <label>Fee shipping</label>
                <select id="shipping_option_id" class="select" name="shipping_option_id" required>
                    @foreach($countries as $country)
                        @foreach($country->shippingOptions as $shipping)
                            <option
                                data-country="{{ $country->id }}"
                                value="{{ $shipping->id }}"
                            >
                                {{ (float)$shipping->amount_foreign === 0.0 ? 'Free Shipping' : $country->moneySymbol().number_format((float)$shipping->amount_foreign, 0, ',', '.') }}
                            </option>
                        @endforeach
                    @endforeach
                </select>
            </div>

            <div class="field" style="margin-top:12px">
                <label>Jumlah barengan</label>
                <input class="input" name="together" type="number" min="1" max="1000" value="1" required>
            </div>

            <button class="btn btn-primary" type="submit" style="margin-top:18px;width:100%">Hitung estimasi</button>
        </form>
    </div>
</section>
@endsection
