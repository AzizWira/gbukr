@extends('layouts.dashboard')
@section('title', 'Tagihan Saya')

@section('dashboard')
@php
    $payableStatuses = ['unpaid', 'partial', 'overdue'];
    $invoiceGroups = $invoices->getCollection()->groupBy(function ($invoice) {
        return $invoice->order_id ? 'order-' . $invoice->order_id : 'manual-' . $invoice->id;
    });
@endphp

<div class="page-head">
    <div>
        <div class="eyebrow">TAGIHAN SAYA</div>
        <h1>Tagihan &amp; pelunasan</h1>
        <p class="muted">Tagihan dikelompokkan per Order supaya tagihan awal, Tax, Rate, shipping, atau kekurangan lain tetap mudah ditelusuri.</p>
    </div>
</div>

<form class="filters" method="get">
    <input class="input" name="q" value="{{ request('q') }}" placeholder="Invoice / order / barang / batch">
    @include('partials.filter-actions')
</form>

<form method="get" action="{{ route('customer.payments.create') }}" data-no-dirty-guard>
    <div class="invoice-ledger-list">
        @forelse($invoiceGroups as $group)
            @php
                $first = $group->first();
                $order = $first->order;
                $item = $order?->items?->first();
            @endphp
            <section class="card invoice-ledger-card">
                <div class="invoice-ledger-head">
                    <div>
                        <div class="eyebrow">{{ $order?->order_number ?: 'TAGIHAN MANUAL' }}</div>
                        <h3>{{ $item?->item_name ?: 'Tagihan' }}</h3>
                        <div class="small muted">
                            @if($order?->batch)
                                Batch {{ $order->batch->code }}
                            @elseif($order?->goGroup)
                                {{ $order->goGroup->name }}
                            @else
                                {{ $first->created_at->translatedFormat('d F Y') }}
                            @endif
                        </div>
                    </div>
                    <div class="ledger-total">
                        <span class="small muted">Sisa grup</span>
                        <strong>Rp{{ number_format($group->sum(fn($invoice) => $invoice->outstanding()),0,',','.') }}</strong>
                    </div>
                </div>

                <div class="ledger-rows">
                    @foreach($group as $invoice)
                        <div class="ledger-row">
                            <label class="invoice-check">
                                <input type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" @disabled(!in_array($invoice->status, $payableStatuses, true))>
                                <span>
                                    <strong>{{ $invoice->type === 'kekurangan' ? ($invoice->adjustment?->reasonLabel() ?? 'Tagihan Tambahan') : strtoupper($invoice->type) }}</strong>
                                    <span class="small muted" style="display:block">{{ $invoice->invoice_number }}@if($invoice->deadline_at) · batas {{ $invoice->deadline_at->translatedFormat('d F Y') }}@endif</span>
                                </span>
                            </label>
                            <div class="ledger-row-value">
                                <div class="money">Rp{{ number_format($invoice->outstanding(),0,',','.') }}</div>
                                @if($invoice->penalty_amount)
                                    <div class="small" style="color:var(--danger)">termasuk denda Rp{{ number_format($invoice->penalty_amount,0,',','.') }}</div>
                                @endif
                                <span class="badge {{ $invoice->status === 'paid' ? 'ok' : ($invoice->status === 'pending' ? 'warn' : '') }}">{{ ucfirst($invoice->status) }}</span>
                                <a class="small" href="{{ route('customer.invoices.show',$invoice) }}">Rincian</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="empty actionable-empty"><strong>Belum ada tagihan.</strong><span>Tagihan dari checkout, Batch, atau Tagihan Tambahan akan muncul di sini.</span></div>
        @endforelse
    </div>

    @if($invoices->getCollection()->contains(fn($invoice) => in_array($invoice->status,$payableStatuses,true)))
        <div class="sticky-payment-action">
            <div><strong>Pilih tagihan yang ingin dibayar</strong><div class="small muted">Beberapa tagihan dapat dibayar dalam satu transfer selama rekening tujuan yang dipilih sama.</div></div>
            <button class="btn btn-primary">Bayar tagihan terpilih</button>
        </div>
    @endif
</form>

@include('partials.pagination',['paginator'=>$invoices])
@endsection
