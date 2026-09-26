@extends('layouts.dashboard')
@section('title', 'Periksa Pembayaran')

@section('dashboard')
<div class="page-head">
    <div>
        <div class="eyebrow">{{ strtoupper($payment->status) }}</div>
        <h1>{{ $payment->payment_number }}</h1>
        <p class="muted">{{ $payment->customer->name }} · {{ $payment->submitted_at?->translatedFormat('d F Y, H.i') }}</p>
    </div>
    <div class="money" style="font-size:1.5rem">Rp{{ number_format($payment->amount, 0, ',', '.') }}</div>
</div>

<div class="card card-blue" style="margin-bottom:18px"><div class="summary-row"><span>Rekening yang dipilih customer</span><strong>{{ $payment->bankAccount ? $payment->bankAccount->bank_name.' · '.$payment->bankAccount->account_number : 'Data lama / tidak tercatat' }}</strong></div></div>

<div class="grid grid-2">
    <div class="card">
        <h3>Rincian tagihan</h3>
        @foreach($payment->invoices as $invoice)
            <div class="summary-row">
                <div>
                    <strong>{{ $invoice->invoice_number }}</strong>
                    <div class="small muted">{{ $invoice->order?->items?->first()?->item_name ?? strtoupper($invoice->type) }}</div>
                </div>
                <span class="money">Rp{{ number_format($invoice->pivot->allocated_amount, 0, ',', '.') }}</span>
            </div>
        @endforeach
    </div>

    <div class="card">
        <h3>Bukti transfer</h3>

        @forelse($payment->proofs as $proof)
            <div class="proof-card">
                @if(str_starts_with((string) $proof->mime_type, 'image/'))
                    <img class="proof-image" src="{{ route('owner.payments.proof.preview', $proof) }}" alt="Bukti transfer {{ $proof->original_name }}">
                @elseif($proof->mime_type === 'application/pdf')
                    <iframe class="proof-frame" src="{{ route('owner.payments.proof.preview', $proof) }}#toolbar=0" title="Bukti transfer {{ $proof->original_name }}"></iframe>
                @else
                    <div class="empty">Preview tidak tersedia untuk tipe file ini.</div>
                @endif

                <div class="proof-meta">
                    <div>
                        <strong>{{ $proof->original_name }}</strong>
                        <div class="small muted">{{ $proof->mime_type ?: 'File' }} · {{ number_format(($proof->size ?? 0) / 1024, 0, ',', '.') }} KB</div>
                    </div>
                    <a class="btn btn-neutral btn-sm" href="{{ route('owner.payments.proof', $proof) }}">Download</a>
                </div>
            </div>
        @empty
            <div class="empty">Bukti tidak tersedia.</div>
        @endforelse

        @if($payment->status === 'pending')
            <div class="actions" style="margin-top:20px">
                <form method="post" action="{{ route('owner.payments.approve', $payment) }}" data-confirm="Pastikan mutasi sudah cocok. Verifikasi pembayaran ini?">
                    @csrf
                    <button class="btn btn-primary">Verifikasi</button>
                </form>
                <button class="btn btn-danger" type="button" onclick="document.getElementById('reject-payment').showModal()">Tolak</button>
            </div>
        @endif

        @if($payment->status === 'rejected')
            <div class="alert error" style="margin-top:14px">{{ $payment->rejection_reason }}</div>
        @endif
    </div>
</div>

<dialog id="reject-payment" class="dialog">
    <form class="dialog-body" method="post" action="{{ route('owner.payments.reject', $payment) }}">
        @csrf
        <h3>Tolak bukti pembayaran</h3>
        <div class="field" style="margin-top:12px">
            <label>Alasan</label>
            <textarea class="textarea" name="reason" required></textarea>
        </div>
        <div class="dialog-actions">
            <button class="btn btn-neutral" type="button" data-dialog-close>Batal</button>
            <button class="btn btn-danger">Tolak bukti</button>
        </div>
    </form>
</dialog>
@endsection
