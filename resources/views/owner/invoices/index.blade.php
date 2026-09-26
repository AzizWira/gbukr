@extends('layouts.dashboard')
@section('title', 'Tagihan')

@section('dashboard')
<div class="page-head">
    <div>
        <div class="eyebrow">TAGIHAN</div>
        <h1>Tagihan customer</h1>
    </div>
</div>

<form class="filters">
    <input class="input" name="q" value="{{ request('q') }}" placeholder="Nomor invoice / nama customer">
    <button class="btn btn-primary">Cari</button>
</form>

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Customer</th>
                <th>Jenis</th>
                <th>Total</th>
                <th>Dibayar</th>
                <th>Sisa</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $invoice)
                @php($invoice->recalculatePenalty())
                <tr>
                    <td><strong>{{ $invoice->invoice_number }}</strong></td>
                    <td>{{ $invoice->customer->name }}</td>
                    <td>{{ strtoupper($invoice->type) }}</td>
                    <td>Rp{{ number_format($invoice->amount + $invoice->penalty_amount, 0, ',', '.') }}</td>
                    <td>Rp{{ number_format($invoice->paid_amount, 0, ',', '.') }}</td>
                    <td class="money">Rp{{ number_format($invoice->outstanding(), 0, ',', '.') }}</td>
                    <td><span class="badge">{{ ucfirst($invoice->status) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">Belum ada tagihan.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination">{{ $invoices->links() }}</div>

<form class="card" style="margin-top:18px" method="post" action="{{ route('owner.invoices.store') }}">
    @csrf
    <h3>Buat tagihan manual</h3>
    <div class="form-grid" style="margin-top:12px">
        <div class="field">
            <label>Customer</label>
            <select class="select" name="customer_id" required>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}">{{ $customer->name }} · {{ str_ends_with($customer->email, '@placeholder.local') ? 'Belum terhubung akun' : $customer->email }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label>Order ID (opsional)</label>
            <input class="input" type="number" name="order_id" min="1">
        </div>
        <div class="field">
            <label>Jenis</label>
            <select class="select" name="type">
                <option value="pelunasan">Pelunasan</option>
                <option value="dp">DP</option>
                <option value="cicilan">Cicilan</option>
                <option value="full">Full Payment</option>
                <option value="kenaikan">Kenaikan</option>
                <option value="penyesuaian">Penyesuaian</option>
            </select>
        </div>
        <div class="field">
            <label>Nominal</label>
            <input class="input" type="number" name="amount" min="1" required>
        </div>
        <div class="field">
            <label>Deadline</label>
            <input class="input" type="datetime-local" name="deadline_at">
        </div>
        <div class="field">
            <label>Catatan</label>
            <input class="input" name="notes">
        </div>
    </div>
    <button class="btn btn-primary" style="margin-top:16px">Buat tagihan</button>
</form>
@endsection
