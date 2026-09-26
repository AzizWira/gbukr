@extends('layouts.dashboard')
@section('title', 'Tagihan Saya')

@section('dashboard')
@php
    $regularInvoices = $invoices->getCollection()->where('type', '!=', 'kekurangan');
    $adjustmentInvoices = $invoices->getCollection()->where('type', 'kekurangan');
    $payableStatuses = ['unpaid', 'partial', 'overdue'];
@endphp

<div class="page-head">
    <div>
        <div class="eyebrow">TAGIHAN SAYA</div>
        <h1>Tagihan &amp; pelunasan</h1>
        <p class="muted">Tagihan utama dan tagihan tambahan dipisahkan supaya perubahan harga tetap mudah dilacak.</p>
    </div>
</div>

<form method="get" action="{{ route('customer.payments.create') }}" data-no-dirty-guard>
    <div class="grid grid-2">
        <div class="card">
            <div class="section-head">
                <div>
                    <h3>Tagihan utama</h3>
                    <p>DP, cicilan, full payment, dan pelunasan.</p>
                </div>
            </div>

            @forelse($regularInvoices as $invoice)
                <div class="summary-row invoice-row">
                    <label class="invoice-check">
                        <input type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" @disabled(!in_array($invoice->status, $payableStatuses, true))>
                        <span>
                            <strong>{{ $invoice->invoice_number }}</strong>
                            <span class="small muted" style="display:block">
                                {{ strtoupper($invoice->type) }} · {{ $invoice->order?->items?->first()?->item_name ?? 'Tagihan' }}
                                @if($invoice->deadline_at)
                                    · batas {{ $invoice->deadline_at->translatedFormat('d F Y') }}
                                @endif
                            </span>
                        </span>
                    </label>
                    <div style="text-align:right">
                        <div class="money">Rp{{ number_format($invoice->outstanding(), 0, ',', '.') }}</div>
                        @if($invoice->penalty_amount)
                            <div class="small" style="color:var(--danger)">termasuk denda Rp{{ number_format($invoice->penalty_amount, 0, ',', '.') }}</div>
                        @endif
                        <span class="badge {{ $invoice->status === 'paid' ? 'ok' : ($invoice->status === 'pending' ? 'warn' : '') }}">{{ ucfirst($invoice->status) }}</span>
                    </div>
                </div>
            @empty
                <div class="empty">Belum ada tagihan utama.</div>
            @endforelse
        </div>

        <div class="card card-pink">
            <div class="section-head">
                <div>
                    <h3>Tagihan Tambahan</h3>
                    <p>Tax, shipping aktual, perubahan berat/rate, dan kekurangan lain tanpa mengubah tagihan awal.</p>
                </div>
            </div>

            @forelse($adjustmentInvoices as $invoice)
                <div class="summary-row invoice-row">
                    <label class="invoice-check">
                        <input type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" @disabled(!in_array($invoice->status, $payableStatuses, true))>
                        <span>
                            <strong>{{ $invoice->order?->items?->first()?->item_name ?? $invoice->invoice_number }}</strong>
                            <span class="small muted" style="display:block">
                                {{ $invoice->adjustment?->reasonLabel() ?? 'Penyesuaian harga' }}
                                @if($invoice->deadline_at)
                                    · batas {{ $invoice->deadline_at->translatedFormat('d F Y') }}
                                @endif
                            </span>
                        </span>
                    </label>
                    <div style="text-align:right">
                        <div class="money">Rp{{ number_format($invoice->outstanding(), 0, ',', '.') }}</div>
                        <a class="small" style="color:var(--blue);font-weight:800" href="{{ route('customer.invoices.show', $invoice) }}">Lihat rincian</a>
                    </div>
                </div>
            @empty
                <div class="empty">Belum ada tagihan tambahan.</div>
            @endforelse
        </div>
    </div>

    @if($invoices->count())
        <div class="actions" style="justify-content:flex-end;margin-top:18px">
            <button class="btn btn-primary">Bayar tagihan terpilih</button>
        </div>
    @endif
</form>

<div class="pagination">{{ $invoices->links() }}</div>
@endsection
