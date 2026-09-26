@extends('layouts.dashboard')
@section('title', 'Batch')

@section('dashboard')
<div class="page-head">
    <div>
        <div class="eyebrow">BATCH</div>
        <h1>Order dari GO / seller</h1>
        <p class="muted">Batch diinput manual, sedangkan kode Batch dibuat otomatis oleh sistem.</p>
    </div>

    @if(auth()->user()->isOwner())
        <a class="btn btn-primary" href="{{ route('owner.batches.create') }}">Buat Batch</a>
    @endif
</div>

<form class="filters">
    <input class="input" name="q" value="{{ request('q') }}" placeholder="Cari kode, nama, atau tracking">
    <button class="btn btn-primary">Cari</button>
</form>

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Batch</th>
                <th>GO</th>
                <th>Negara</th>
                <th>Warehouse</th>
                <th>Tracking</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($batches as $batch)
                <tr>
                    <td>
                        <strong>{{ $batch->code }}</strong>
                        <div class="small muted">{{ $batch->name }}</div>
                    </td>
                    <td>{{ $batch->goGroup?->name ?: '-' }}</td>
                    <td>{{ $batch->country->name }}</td>
                    <td>{{ $batch->warehouse?->code ?: '-' }}</td>
                    <td>{{ $batch->tracking_number ?: 'Belum tersedia' }}</td>
                    <td><span class="badge status-colored" style="--status-color:{{ \App\Services\OrderStatusService::color($batch->status) }}">{{ \App\Services\OrderStatusService::label($batch->status) }}</span></td>
                    <td><a class="btn btn-soft btn-sm" href="{{ route('owner.batches.show', $batch) }}">Buka</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty">Belum ada Batch.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination">{{ $batches->links() }}</div>
@endsection
