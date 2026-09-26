@extends('layouts.dashboard')
@section('title', isset($batch) ? 'Edit Batch' : 'Buat Batch')

@section('dashboard')
@php($editing = isset($batch) && $batch->exists)
<div class="page-head">
    <div>
        <div class="eyebrow">{{ $editing ? 'EDIT BATCH' : 'BATCH BARU' }}</div>
        <h1>{{ $editing ? $batch->code : 'Masukkan Batch GO' }}</h1>
        <p class="muted">{{ $editing ? 'Kode Batch tidak berubah saat metadata diedit.' : 'Kode Batch dibuat otomatis dari negara + GO/nama Batch, contoh KR-ENHYPEN-001.' }}</p>
    </div>
</div>

<form class="card" method="post" action="{{ $editing ? route('owner.batches.update',$batch) : route('owner.batches.store') }}">
    @csrf
    @if($editing)
        @method('put')
    @endif
    <div class="form-grid">
        <div class="field"><label>Nama Batch</label><input class="input" name="name" value="{{ old('name',$batch->name ?? '') }}" required maxlength="160"></div>
        <div class="field"><label>GO</label><select class="select" name="go_group_id"><option value="">Tanpa GO</option>@foreach($groups as $group)<option value="{{ $group->id }}" @selected(old('go_group_id',$batch->go_group_id ?? null)==$group->id)>{{ $group->name }}</option>@endforeach</select></div>
        <div class="field"><label>Negara</label><select class="select" id="batch-country" name="country_id" required>@foreach($countries as $country)<option value="{{ $country->id }}" @selected(old('country_id',$batch->country_id ?? null)==$country->id)>{{ $country->name }} · {{ $country->currency_code }}</option>@endforeach</select></div>
        <div class="field"><label>Warehouse (internal)</label><select class="select" id="batch-warehouse" name="warehouse_id"><option value="">Belum ditentukan</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" data-country="{{ $warehouse->country_id }}" @selected(old('warehouse_id',$batch->warehouse_id ?? null)==$warehouse->id)>{{ $warehouse->code }} · {{ $warehouse->name ?: $warehouse->country?->name }}</option>@endforeach</select><div class="help" id="warehouse-help">Hanya warehouse dari negara yang dipilih yang dapat digunakan.</div></div>
        <div class="field"><label>Tracking Number</label><input class="input" name="tracking_number" value="{{ old('tracking_number',$batch->tracking_number ?? '') }}" maxlength="180" placeholder="Boleh dikosongkan jika belum tersedia"><div class="help">Nomor tracking berasal dari seller/kurir, jadi tidak dibuat otomatis.</div></div>
        <div class="field full"><label>Catatan</label><textarea class="textarea" name="description" maxlength="1000">{{ old('description',$batch->description ?? '') }}</textarea></div>
    </div>
    <button class="btn btn-primary" style="margin-top:18px">{{ $editing ? 'Simpan perubahan' : 'Buat Batch' }}</button>
</form>
@endsection

@push('scripts')
<script>
(()=>{const country=document.getElementById('batch-country'),warehouse=document.getElementById('batch-warehouse'),help=document.getElementById('warehouse-help');if(!country||!warehouse)return;const options=[...warehouse.options];const sync=()=>{let count=0;options.forEach((option,index)=>{if(index===0){option.hidden=false;option.disabled=false;return;}const match=option.dataset.country===country.value;option.hidden=!match;option.disabled=!match;if(match)count++;});const selected=warehouse.selectedOptions[0];if(selected?.value&&selected.dataset.country!==country.value)warehouse.value='';if(help)help.textContent=count?'Hanya warehouse dari negara yang dipilih yang dapat digunakan.':'Belum ada warehouse aktif untuk negara ini. Batch tetap dapat disimpan tanpa warehouse.';};country.addEventListener('change',sync);sync();})();
</script>
@endpush
