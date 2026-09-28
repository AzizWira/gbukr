@extends('layouts.dashboard')
@section('title','Batch')
@section('dashboard')
<div class="page-head">
    <div><div class="eyebrow">BATCH</div><h1>Order dari GO / seller</h1><p class="muted">Kode Batch manual dibuat otomatis. Batch hasil migrasi mempertahankan referensi file lama.</p></div>
    @if(auth()->user()->isOwner())<a class="btn btn-primary" href="{{ route('owner.batches.create') }}">Buat Batch</a>@endif
</div>
<form class="filters">
    <input class="input" name="q" value="{{ request('q') }}" placeholder="Kode, nama, GO, negara, warehouse, tracking">
    <select class="select" name="status"><option value="">Semua status</option>@foreach(\App\Services\OrderStatusService::statuses() as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ \App\Services\OrderStatusService::label($status) }}</option>@endforeach</select>
    @include('partials.filter-actions')
</form>
<div class="table-wrap"><table class="table"><thead><tr><th>Batch</th><th>GO</th><th>Negara</th><th>Warehouse</th><th>Tracking</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($batches as $batch)
<tr><td><strong>{{ $batch->code }}</strong><div class="small muted">{{ $batch->name }}</div></td><td>{{ $batch->goGroup?->name ?: '-' }}</td><td>{{ $batch->country->name }}</td><td>{{ $batch->warehouse?->code ?: '-' }}</td><td>{{ $batch->tracking_number ?: 'Belum tersedia' }}</td><td><span class="badge status-colored" style="--status-color:{{ \App\Services\OrderStatusService::color($batch->status) }}">{{ \App\Services\OrderStatusService::label($batch->status) }}</span></td><td><div class="actions"><a class="btn btn-soft btn-sm" href="{{ route('owner.batches.show',$batch) }}">Buka</a>@if(auth()->user()->isOwner())<a class="btn btn-neutral btn-sm" href="{{ route('owner.batches.edit',$batch) }}">Edit</a>@if($batch->orders_count === 0)<form method="post" action="{{ route('owner.batches.destroy',$batch) }}" data-confirm-title="Hapus permanen" data-confirm="Hapus Batch kosong {{ $batch->code }} secara permanen? Tracking otomatis Batch ikut dihapus. Karena Batch ini belum memiliki order, tidak ada histori customer/tagihan yang terdampak. Tindakan tidak dapat dibatalkan.">@csrf @method('delete')<button class="btn btn-danger btn-sm">Hapus</button></form>@endif @endif</div></td></tr>
@empty<tr><td colspan="7" class="empty">Tidak ada Batch yang cocok dengan pencarian/filter ini.</td></tr>@endforelse
</tbody></table></div>
@include('partials.pagination',['paginator'=>$batches])
@endsection
