@extends('layouts.dashboard')
@section('title', 'Tracking')

@section('dashboard')
<div class="page-head">
    <div>
        <div class="eyebrow">TRACKING</div>
        <h1>Status barang publik</h1>
        <p class="muted">Tracking Batch dibuat dan disinkronkan otomatis dari halaman Batch. Form manual di bawah dipakai untuk tracking PO atau data non-Batch.</p>
    </div>
</div>

<form class="filters">
    <input class="input" name="q" value="{{ request('q') }}" placeholder="Batch / PO / item / tracking">
    <button class="btn btn-primary">Cari</button>
</form>

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Ref</th>
                <th>Barang</th>
                <th>Keterangan</th>
                <th>Negara</th>
                <th>Tracking</th>
                <th>Status</th>
                <th>Publik</th>
            </tr>
        </thead>
        <tbody>
            @forelse($shipments as $shipment)
                <tr>
                    <td><strong>{{ $shipment->reference }}</strong></td>
                    <td>{{ $shipment->item_details }}</td>
                    <td>{{ $shipment->description_type }}</td>
                    <td>{{ $shipment->country->name }}</td>
                    <td>{{ $shipment->tracking_number ?: 'Belum tersedia' }}</td>
                    <td><span class="badge status-colored" style="--status-color:{{ \App\Services\OrderStatusService::color($shipment->status) }}">{{ \App\Services\OrderStatusService::label($shipment->status) }}</span></td>
                    <td>{{ $shipment->visible_publicly ? 'Ya' : 'Tidak' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">Belum ada tracking.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination">{{ $shipments->links() }}</div>

@if(auth()->user()->isOwner())
<form class="card" style="margin-top:18px" method="post" action="{{ route('owner.tracking.store') }}">
    @csrf
    <h3>Tambah tracking manual</h3>

    <div class="form-grid" style="margin-top:12px">
        <div class="field">
            <label>Sumber</label>
            <select class="select" name="source_type">
                <option value="po" @selected(old('source_type', 'po') === 'po')>PO</option>
                <option value="batch" @selected(old('source_type') === 'batch')>Batch non-sistem / legacy</option>
            </select>
        </div>

        <div class="field">
            <label>Batch / PO</label>
            <input class="input" name="reference" value="{{ old('reference') }}" required maxlength="100">
        </div>

        <div class="field">
            <label>Detail barang</label>
            <input class="input" name="item_details" value="{{ old('item_details') }}" required maxlength="180">
        </div>

        <div class="field">
            <label>Keterangan</label>
            <input class="input" name="description_type" value="{{ old('description_type') }}" required maxlength="100" placeholder="Album / Photocard">
        </div>

        <div class="field">
            <label>Info (opsional)</label>
            <input class="input" name="info" value="{{ old('info') }}" maxlength="1000">
        </div>

        <div class="field">
            <label>Negara</label>
            <select class="select" name="country_id" required>
                @foreach($countries as $country)
                    <option value="{{ $country->id }}" @selected(old('country_id') == $country->id)>{{ $country->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label>Tracking Number</label>
            <input class="input" name="tracking_number" value="{{ old('tracking_number') }}" maxlength="180" placeholder="Boleh kosong">
        </div>

        <div class="field">
            <label>Status</label>
            <select class="select" name="status" required>
                @foreach(\App\Services\OrderStatusService::statuses() as $status)
                    <option value="{{ $status }}" @selected(old('status', 'ordered') === $status)>{{ \App\Services\OrderStatusService::label($status) }}</option>
                @endforeach
            </select>
        </div>

        <div class="field full">
            <label><input type="checkbox" name="visible_publicly" value="1" @checked(old('visible_publicly', true))> Tampilkan publik</label>
        </div>
    </div>

    <button class="btn btn-primary" style="margin-top:14px">Tambah tracking</button>
</form>
@endif
@endsection
