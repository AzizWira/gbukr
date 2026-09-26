@extends('layouts.dashboard')
@section('title', 'Detail Tagihan')

@section('dashboard')
<div class="page-head">
    <div>
        <div class="eyebrow">{{ $invoice->type === 'kekurangan' ? 'TAGIHAN TAMBAHAN' : strtoupper($invoice->type) }}</div>
        <h1>{{ $invoice->invoice_number }}</h1>
    </div>
    <span class="badge">{{ ucfirst($invoice->status) }}</span>
</div>

<div class="grid grid-2">
    <div class="card">
        <div class="summary-row"><span>Nominal</span><strong>Rp{{ number_format($invoice->amount, 0, ',', '.') }}</strong></div>
        <div class="summary-row"><span>Denda</span><strong>Rp{{ number_format($invoice->penalty_amount, 0, ',', '.') }}</strong></div>
        <div class="summary-row"><span>Sudah dibayar</span><strong>Rp{{ number_format($invoice->paid_amount, 0, ',', '.') }}</strong></div>
        <div class="summary-row"><span>Sisa tagihan</span><strong class="money">Rp{{ number_format($invoice->outstanding(), 0, ',', '.') }}</strong></div>
        <div class="summary-row"><span>Deadline</span><strong>{{ $invoice->deadline_at?->translatedFormat('d F Y, H.i') ?: 'Tidak ada' }}</strong></div>
    </div>

    <div class="card">
        <h3>Order</h3>
        <p><strong>{{ $invoice->order?->order_number ?: 'Tagihan tanpa order' }}</strong></p>
        @foreach($invoice->order?->items ?? [] as $item)
            <div class="summary-row"><span>{{ $item->item_name }}</span><span>{{ $item->qty }} pcs</span></div>
        @endforeach

        @if(in_array($invoice->status, ['unpaid', 'partial', 'overdue'], true))
            <a class="btn btn-primary" style="margin-top:16px" href="{{ route('customer.payments.create', ['invoice_ids' => [$invoice->id]]) }}">Bayar tagihan</a>
        @endif
    </div>

    @if($invoice->type === 'kekurangan' && $invoice->adjustment)
        <div class="card card-pink" style="grid-column:1/-1">
            <div class="section-head">
                <div>
                    <h3>Rincian tagihan tambahan</h3>
                    <p>Informasi perubahan dari estimasi awal sampai kondisi aktual.</p>
                </div>
            </div>
            <div class="grid grid-2">
                <div>
                    <div class="summary-row"><span>Alasan</span><strong>{{ $invoice->adjustment->reasonLabel() }}</strong></div>
                    <div class="summary-row"><span>Berat estimasi</span><strong>{{ $invoice->adjustment->estimated_weight_grams ? number_format($invoice->adjustment->estimated_weight_grams, 0, ',', '.') . ' gr' : '-' }}</strong></div>
                    <div class="summary-row"><span>Berat aktual</span><strong>{{ $invoice->adjustment->actual_weight_grams ? number_format($invoice->adjustment->actual_weight_grams, 0, ',', '.') . ' gr' : '-' }}</strong></div>
                </div>
                <div>
                    <div class="summary-row"><span>Rate awal</span><strong>{{ $invoice->adjustment->original_rate ?: '-' }}</strong></div>
                    <div class="summary-row"><span>Rate akhir</span><strong>{{ $invoice->adjustment->final_rate ?: '-' }}</strong></div>
                    <div class="summary-row"><span>Catatan</span><strong>{{ $invoice->adjustment->notes ?: '-' }}</strong></div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
