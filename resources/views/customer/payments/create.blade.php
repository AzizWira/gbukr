@extends('layouts.dashboard')
@section('title','Kirim Pembayaran')
@section('dashboard')
<div class="page-head"><div><div class="eyebrow">PEMBAYARAN</div><h1>Transfer & kirim bukti</h1><p class="muted">Pilih rekening yang benar-benar kamu gunakan saat transfer. Pembayaran valid setelah Owner mencocokkannya dengan mutasi.</p></div></div>
<div class="split">
<div class="card"><h3>Tagihan yang dibayar</h3>@foreach($invoices as $invoice)<div class="summary-row"><div><strong>{{ $invoice->invoice_number }}</strong><div class="small muted">{{ strtoupper($invoice->type) }}</div></div><div class="money">Rp{{ number_format($invoice->outstanding(),0,',','.') }}</div></div>@endforeach<div class="summary-row" style="font-size:1.1rem"><strong>Total transfer</strong><strong>Rp{{ number_format($total,0,',','.') }}</strong></div></div>
<div>
<form class="card" method="post" action="{{ route('customer.payments.store') }}" enctype="multipart/form-data">@csrf
@foreach($invoices as $invoice)<input type="hidden" name="invoice_ids[]" value="{{ $invoice->id }}">@endforeach
<h3>Pilih rekening tujuan</h3>
<div class="field"><label>Rekening tujuan</label><div class="bank-choice-list">@forelse($banks as $bank)<label class="bank-choice"><input type="radio" name="bank_account_id" value="{{ $bank->id }}" required><span class="bank-choice-body"><strong>{{ $bank->bank_name }}</strong><span class="money">{{ $bank->account_number }}</span><span>{{ $bank->account_name }}</span>@if($bank->instructions)<span class="small muted">{{ $bank->instructions }}</span>@endif</span></label>@empty<div class="alert error">Rekening pembayaran belum tersedia. Hubungi Owner sebelum transfer.</div>@endforelse</div></div>
<div class="field" style="margin-top:18px"><label>Bukti transfer</label><input class="input" type="file" name="proof" accept="image/jpeg,image/png,image/webp,application/pdf" required data-proof-input><div class="help">JPG, PNG, WEBP, atau PDF · maksimal 5 MB.</div></div><div class="proof-upload-preview" data-proof-preview hidden></div><button class="btn btn-primary" style="width:100%;margin-top:16px" @disabled($banks->isEmpty())>Kirim untuk verifikasi</button>
</form></div></div>
@endsection
