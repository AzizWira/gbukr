@extends('layouts.dashboard')
@section('title', 'Checkout')

@section('dashboard')
<div class="page-head">
    <div>
        <div class="eyebrow">CHECKOUT</div>
        <h1>Periksa sebelum membuat order</h1>
    </div>
</div>

<div class="split">
    <div class="card">
        @foreach($cart as $row)
            @php
                $variant = $row['variant'];
                $product = $variant->product;
                $unit = $variant->price_idr ?? (round($variant->price_foreign * $product->country->rate) + (int)$product->item_fee_idr);
            @endphp

            <div class="summary-row">
                <div>
                    <strong>{{ $product->name }}</strong>
                    <div class="small muted">{{ $variant->name }} · {{ $row['qty'] }} pcs</div>
                </div>
                <div class="money">Rp{{ number_format($unit * $row['qty'], 0, ',', '.') }}</div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <h3>Konfirmasi order</h3>
        <p class="muted small">Pastikan barang, variasi, dan jumlah sudah benar sebelum order dibuat.</p>
        <form method="post" action="{{ route('checkout.store') }}" data-submit-lock>
            @csrf
            <label class="invoice-check">
                <input type="checkbox" name="agree" value="1" required>
                <span>Saya sudah memeriksa barang, variasi, dan jumlah.</span>
            </label>
            <button class="btn btn-primary" style="width:100%;margin-top:18px">Buat order</button>
        </form>
    </div>
</div>
@endsection
