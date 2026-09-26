@extends('layouts.dashboard')
@section('title', 'Buat Batch')

@section('dashboard')
<div class="page-head">
    <div>
        <div class="eyebrow">BATCH BARU</div>
        <h1>Masukkan Batch GO</h1>
        <p class="muted">Kode Batch dibuat otomatis setelah data disimpan.</p>
    </div>
</div>

<form class="card" method="post" action="{{ route('owner.batches.store') }}">
    @csrf

    <div class="form-grid">
        <div class="field">
            <label>Nama Batch</label>
            <input class="input" name="name" value="{{ old('name') }}" required maxlength="160" placeholder="Contoh: CORTIS September">
        </div>

        <div class="field">
            <label>GO</label>
            <select class="select" name="go_group_id">
                <option value="">Tanpa GO</option>
                @foreach($groups as $group)
                    <option value="{{ $group->id }}" @selected(old('go_group_id') == $group->id)>{{ $group->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label>Negara</label>
            <select class="select" id="batch-country" name="country_id" required>
                @foreach($countries as $country)
                    <option value="{{ $country->id }}" @selected(old('country_id') == $country->id)>
                        {{ $country->name }} · {{ $country->currency_code }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label>Warehouse (internal)</label>
            <select class="select" id="batch-warehouse" name="warehouse_id">
                <option value="">Belum ditentukan</option>
                @foreach($warehouses as $warehouse)
                    <option
                        value="{{ $warehouse->id }}"
                        data-country="{{ $warehouse->country_id }}"
                        @selected(old('warehouse_id') == $warehouse->id)
                    >
                        {{ $warehouse->code }} · {{ $warehouse->name ?: $warehouse->country?->name }}
                    </option>
                @endforeach
            </select>
            <div class="help" id="warehouse-help">Hanya warehouse dari negara yang dipilih yang dapat digunakan.</div>
        </div>

        <div class="field">
            <label>Tracking Number</label>
            <input class="input" name="tracking_number" value="{{ old('tracking_number') }}" maxlength="180" placeholder="Boleh dikosongkan jika belum tersedia">
            <div class="help">Nomor tracking berasal dari seller/kurir, jadi tidak dibuat otomatis oleh sistem.</div>
        </div>

        <div class="field full">
            <label>Catatan</label>
            <textarea class="textarea" name="description" maxlength="1000" placeholder="Opsional">{{ old('description') }}</textarea>
        </div>
    </div>

    <button class="btn btn-primary" style="margin-top:18px">Buat Batch</button>
</form>
@endsection

@push('scripts')
<script>
(() => {
    const country = document.getElementById('batch-country');
    const warehouse = document.getElementById('batch-warehouse');
    const help = document.getElementById('warehouse-help');
    if (!country || !warehouse) return;

    const options = Array.from(warehouse.options);

    const syncWarehouseOptions = () => {
        const selectedCountry = country.value;
        let visibleCount = 0;

        options.forEach((option, index) => {
            if (index === 0) {
                option.hidden = false;
                option.disabled = false;
                return;
            }

            const matches = option.dataset.country === selectedCountry;
            option.hidden = !matches;
            option.disabled = !matches;
            if (matches) visibleCount++;
        });

        const selected = warehouse.selectedOptions[0];
        if (selected && selected.value && selected.dataset.country !== selectedCountry) {
            warehouse.value = '';
        }

        if (help) {
            help.textContent = visibleCount
                ? 'Hanya warehouse dari negara yang dipilih yang dapat digunakan.'
                : 'Belum ada warehouse aktif untuk negara ini. Batch tetap dapat dibuat tanpa warehouse.';
        }
    };

    country.addEventListener('change', syncWarehouseOptions);
    syncWarehouseOptions();
})();
</script>
@endpush
