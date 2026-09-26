@extends('layouts.dashboard')
@section('title', 'Data Master')

@section('dashboard')
<div class="page-head">
    <div>
        <div class="eyebrow">DATA MASTER</div>
        <h1>Data operasional</h1>
        <p class="muted">Shipping, warehouse, GO, rekening, dan status perjalanan.</p>
    </div>
</div>

<div class="lifecycle-note small">
    <strong>Aturan hapus:</strong> data yang belum pernah dipakai boleh dihapus permanen. Jika sudah menjadi referensi transaksi/histori, tombol hapus tidak tersedia dan gunakan <strong>Nonaktifkan</strong> agar data lama tetap konsisten.
</div>

<div class="grid grid-2">
    <div class="card">
        <h3>Opsi shipping</h3>
        @foreach($countries as $country)
            @foreach($country->shippingOptions as $shipping)
                <div class="master-item">
                    <div class="master-item-main">
                        <div>
                            <strong>{{ $shipping->label }}</strong>
                            <div class="small muted">
                                {{ $country->name }} ·
                                {{ (float) $shipping->amount_foreign === 0.0 ? 'Free Shipping' : $country->moneySymbol() . number_format($shipping->amount_foreign, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="actions">
                            <span class="badge {{ $shipping->active ? 'ok' : 'gray' }}">
                                {{ $shipping->active ? 'Aktif' : 'Nonaktif' }}
                            </span>

                            <form method="post" action="{{ route('owner.master.shipping.toggle', $shipping) }}" data-confirm="{{ $shipping->active ? 'Nonaktifkan opsi shipping ini? Opsi tidak tampil untuk kalkulasi baru, tetapi data lain tidak dihapus.' : 'Aktifkan kembali opsi shipping ini?' }}">
                                @csrf
                                <button class="btn btn-sm {{ $shipping->active ? 'btn-danger' : 'btn-soft' }}">
                                    {{ $shipping->active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>

                            <form method="post" action="{{ route('owner.master.shipping.destroy', $shipping) }}" data-confirm-title="Hapus permanen" data-confirm="Hapus opsi shipping '{{ $shipping->label }}' secara permanen? Kalkulator tidak menyimpan foreign key opsi shipping pada transaksi lama, sehingga penghapusan ini tidak mengubah histori nominal yang sudah tercatat. Tindakan tidak dapat dibatalkan.">
                                @csrf
                                @method('delete')
                                <button class="btn btn-sm btn-danger">Hapus</button>
                            </form>
                        </div>
                    </div>

                    <details>
                        <summary>Edit shipping</summary>
                        <form method="post" action="{{ route('owner.master.shipping', $shipping) }}" style="margin-top:10px">
                            @csrf
                            <div class="form-grid">
                                <div class="field">
                                    <label>Negara</label>
                                    <select class="select" name="country_id" required>
                                        @foreach($countries as $countryOption)
                                            <option value="{{ $countryOption->id }}" @selected($shipping->country_id === $countryOption->id)>
                                                {{ $countryOption->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="field">
                                    <label>Label</label>
                                    <input class="input" name="label" value="{{ $shipping->label }}" required>
                                </div>
                                <div class="field">
                                    <label>Nominal</label>
                                    <input class="input" type="number" step="0.01" min="0" name="amount_foreign" value="{{ $shipping->amount_foreign }}" required>
                                </div>
                            </div>
                            <button class="btn btn-primary btn-sm" style="margin-top:10px">Simpan</button>
                        </form>
                    </details>
                </div>
            @endforeach
        @endforeach
    </div>

    <form class="card" method="post" action="{{ route('owner.master.shipping') }}">
        @csrf
        <h3>Tambah shipping</h3>
        <div class="field">
            <label>Negara</label>
            <select class="select" name="country_id" required>
                @foreach($countries->where('active', true) as $country)
                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label>Label</label>
            <input class="input" name="label" required>
        </div>
        <div class="field">
            <label>Nominal mata uang asal</label>
            <input class="input" type="number" step="0.01" min="0" name="amount_foreign" required>
            <div class="help">Isi 0 untuk Free Shipping.</div>
        </div>
        <button class="btn btn-primary">Tambah</button>
    </form>

    <div class="card">
        <h3>Warehouse</h3>
        @foreach($countries as $country)
            @foreach($country->warehouses as $warehouse)
                @php
                    $warehouseUsed = (int) (($warehouseUsage->get($warehouse->id, [])['Batch'] ?? 0));
                @endphp

                <div class="master-item">
                    <div class="master-item-main">
                        <div>
                            <strong>{{ $warehouse->code }}</strong>
                            <div class="small muted">{{ $country->name }} · {{ $warehouse->name ?: '-' }}</div>
                        </div>
                        <div class="actions">
                            <span class="badge {{ $warehouse->active ? 'ok' : 'gray' }}">
                                {{ $warehouse->active ? 'Aktif' : 'Nonaktif' }}
                            </span>

                            <form method="post" action="{{ route('owner.master.warehouse.toggle', $warehouse) }}" data-confirm="{{ $warehouse->active ? 'Nonaktifkan warehouse ini? Batch lama tetap menyimpan relasinya, tetapi warehouse tidak bisa dipilih untuk Batch baru.' : 'Aktifkan kembali warehouse ini?' }}">
                                @csrf
                                <button class="btn btn-sm {{ $warehouse->active ? 'btn-danger' : 'btn-soft' }}">
                                    {{ $warehouse->active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>

                            @if($warehouseUsed === 0)
                                <form method="post" action="{{ route('owner.master.warehouse.destroy', $warehouse) }}" data-confirm-title="Hapus permanen" data-confirm="Hapus warehouse '{{ $warehouse->code }}' secara permanen? Warehouse ini belum pernah dipakai oleh Batch. Tindakan tidak dapat dibatalkan.">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    @if($warehouseUsed > 0)
                        <div class="delete-note">
                            Tidak dapat dihapus permanen karena dipakai oleh {{ $warehouseUsed }} Batch. Gunakan Nonaktifkan jika tidak dipakai lagi.
                        </div>
                    @endif

                    <details>
                        <summary>Edit warehouse</summary>
                        <form method="post" action="{{ route('owner.master.warehouse', $warehouse) }}" style="margin-top:10px">
                            @csrf
                            <div class="form-grid">
                                <div class="field">
                                    <label>Negara</label>
                                    <select class="select" name="country_id" required>
                                        @foreach($countries as $countryOption)
                                            <option value="{{ $countryOption->id }}" @selected($warehouse->country_id === $countryOption->id)>
                                                {{ $countryOption->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="field">
                                    <label>Kode</label>
                                    <input class="input" name="code" value="{{ $warehouse->code }}" required>
                                </div>
                                <div class="field">
                                    <label>Nama</label>
                                    <input class="input" name="name" value="{{ $warehouse->name }}">
                                </div>
                                <div class="field">
                                    <label>Catatan</label>
                                    <input class="input" name="notes" value="{{ $warehouse->notes }}">
                                </div>
                            </div>
                            <button class="btn btn-primary btn-sm" style="margin-top:10px">Simpan</button>
                        </form>
                    </details>
                </div>
            @endforeach
        @endforeach
    </div>

    <form class="card" method="post" action="{{ route('owner.master.warehouse') }}">
        @csrf
        <h3>Tambah warehouse</h3>
        <div class="form-grid">
            <div class="field">
                <label>Negara</label>
                <select class="select" name="country_id" required>
                    @foreach($countries->where('active', true) as $country)
                        <option value="{{ $country->id }}">{{ $country->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Kode</label>
                <input class="input" name="code" required>
            </div>
            <div class="field">
                <label>Nama</label>
                <input class="input" name="name">
            </div>
            <div class="field">
                <label>Catatan</label>
                <input class="input" name="notes">
            </div>
        </div>
        <button class="btn btn-primary">Tambah warehouse</button>
    </form>

    <div class="card">
        <h3>GO</h3>
        @foreach($groups as $group)
            @php
                $goUsage = collect($groupUsage->get($group->id, []))->filter(function ($count) {
                    return (int) $count > 0;
                });
                $goUsed = (int) $goUsage->sum();
                $goUsageText = $goUsage->map(function ($count, $label) {
                    return $count . ' ' . $label;
                })->implode(', ');
            @endphp

            <div class="master-item">
                <div class="master-item-main">
                    <strong>{{ $group->name }}</strong>
                    <div class="actions">
                        <span class="badge {{ $group->status === 'active' ? 'ok' : 'gray' }}">
                            {{ $group->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                        </span>

                        <form method="post" action="{{ route('owner.master.go.toggle', $group) }}" data-confirm="{{ $group->status === 'active' ? 'Nonaktifkan GO ini? Data historis tetap tersimpan, tetapi GO tidak dapat dipilih untuk data baru.' : 'Aktifkan kembali GO ini?' }}">
                            @csrf
                            <button class="btn btn-sm {{ $group->status === 'active' ? 'btn-danger' : 'btn-soft' }}">
                                {{ $group->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>

                        @if($goUsed === 0)
                            <form method="post" action="{{ route('owner.master.go.destroy', $group) }}" data-confirm-title="Hapus permanen" data-confirm="Hapus GO '{{ $group->name }}' secara permanen? GO ini belum memiliki Batch, order, atau riwayat import. Tindakan tidak dapat dibatalkan.">
                                @csrf
                                @method('delete')
                                <button class="btn btn-sm btn-danger">Hapus</button>
                            </form>
                        @endif
                    </div>
                </div>

                @if($goUsed > 0)
                    <div class="delete-note">
                        Tidak dapat dihapus: {{ $goUsageText }}. Gunakan Nonaktifkan agar histori tetap aman.
                    </div>
                @endif

                <details>
                    <summary>Edit GO</summary>
                    <form method="post" action="{{ route('owner.master.go', $group) }}" style="margin-top:10px">
                        @csrf
                        <div class="field">
                            <label>Nama GO</label>
                            <input class="input" name="name" value="{{ $group->name }}" required>
                        </div>
                        <div class="field">
                            <label>Catatan</label>
                            <input class="input" name="notes" value="{{ $group->notes }}">
                        </div>
                        <button class="btn btn-primary btn-sm">Simpan</button>
                    </form>
                </details>
            </div>
        @endforeach
    </div>

    <form class="card" method="post" action="{{ route('owner.master.go') }}">
        @csrf
        <h3>Tambah GO</h3>
        <div class="field">
            <label>Nama GO</label>
            <input class="input" name="name" required>
        </div>
        <div class="field">
            <label>Catatan</label>
            <input class="input" name="notes">
        </div>
        <button class="btn btn-primary">Tambah GO</button>
    </form>

    <div class="card" style="grid-column:1/-1">
        <div class="section-head">
            <div>
                <h3>Rekening pembayaran</h3>
                <p>Rekening aktif tampil saat customer mengirim bukti pembayaran.</p>
            </div>
        </div>

        <div class="bank-admin-list">
            @foreach($banks as $bank)
                @php
                    $bankUsed = (int) (($bankUsage->get($bank->id, [])['pembayaran'] ?? 0));
                @endphp

                <div class="bank-admin-card">
                    <div>
                        <strong>{{ $bank->bank_name }} · {{ $bank->account_number }}</strong>
                        <div class="small muted">{{ $bank->account_name }}</div>
                    </div>

                    <div class="actions">
                        <span class="badge {{ $bank->active ? 'ok' : 'gray' }}">
                            {{ $bank->active ? 'Aktif' : 'Nonaktif' }}
                        </span>

                        <form method="post" action="{{ route('owner.master.bank.toggle', $bank) }}" data-confirm="{{ $bank->active ? 'Nonaktifkan rekening ini? Rekening tidak dapat dipilih untuk pembayaran baru, tetapi histori pembayaran lama tetap tersimpan.' : 'Aktifkan rekening ini?' }}">
                            @csrf
                            <button class="btn btn-sm {{ $bank->active ? 'btn-danger' : 'btn-soft' }}">
                                {{ $bank->active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>

                        @if($bankUsed === 0)
                            <form method="post" action="{{ route('owner.master.bank.destroy', $bank) }}" data-confirm-title="Hapus permanen" data-confirm="Hapus rekening {{ $bank->bank_name }} {{ $bank->account_number }} secara permanen? Rekening ini belum pernah dipakai pada pembayaran. Tindakan tidak dapat dibatalkan.">
                                @csrf
                                @method('delete')
                                <button class="btn btn-sm btn-danger">Hapus</button>
                            </form>
                        @endif
                    </div>

                    @if($bankUsed > 0)
                        <div class="delete-note">
                            Tidak dapat dihapus karena dipakai oleh {{ $bankUsed }} pembayaran. Nonaktifkan rekening agar tidak dipilih lagi tanpa menghilangkan histori rekening tujuan.
                        </div>
                    @endif

                    <details style="width:100%">
                        <summary>Edit rekening</summary>
                        <form method="post" action="{{ route('owner.master.bank', $bank) }}" style="margin-top:8px">
                            @csrf
                            <div class="form-grid">
                                <div class="field">
                                    <label>Bank</label>
                                    <input class="input" name="bank_name" value="{{ $bank->bank_name }}" required>
                                </div>
                                <div class="field">
                                    <label>Nomor rekening</label>
                                    <input class="input" name="account_number" value="{{ $bank->account_number }}" required>
                                </div>
                                <div class="field">
                                    <label>Nama pemilik</label>
                                    <input class="input" name="account_name" value="{{ $bank->account_name }}" required>
                                </div>
                                <div class="field">
                                    <label>Instruksi</label>
                                    <input class="input" name="instructions" value="{{ $bank->instructions }}">
                                </div>
                            </div>
                            <button class="btn btn-primary btn-sm">Simpan</button>
                        </form>
                    </details>
                </div>
            @endforeach
        </div>

        <form method="post" action="{{ route('owner.master.bank') }}" style="margin-top:18px">
            @csrf
            <div class="form-grid">
                <div class="field">
                    <label>Bank</label>
                    <input class="input" name="bank_name" required>
                </div>
                <div class="field">
                    <label>Nomor rekening</label>
                    <input class="input" name="account_number" required>
                </div>
                <div class="field">
                    <label>Nama pemilik</label>
                    <input class="input" name="account_name" required>
                </div>
                <div class="field">
                    <label>Instruksi</label>
                    <input class="input" name="instructions">
                </div>
            </div>
            <button class="btn btn-primary">Tambah rekening</button>
        </form>
    </div>

    <div class="card" style="grid-column:1/-1">
        <div class="section-head">
            <div>
                <h3>Status perjalanan</h3>
                <p>Label dan warna dapat disesuaikan. Status inti tidak dapat dihapus/nonaktif karena digunakan otomatis oleh sistem.</p>
            </div>
        </div>

        <div class="status-admin-grid">
            @foreach($statuses as $status)
                @php
                    $currentUsage = collect($statusUsage->get($status->id, []))->filter(function ($count) {
                        return (int) $count > 0;
                    });
                    $statusUsed = (int) $currentUsage->sum();
                    $statusUsageText = $currentUsage->map(function ($count, $label) {
                        return $count . ' ' . $label;
                    })->implode(', ');
                @endphp

                <div class="status-admin-card">
                    <form method="post" action="{{ route('owner.master.status', $status) }}">
                        @csrf
                        <div class="status-preview">
                            <span class="status-dot" style="background:{{ $status->color }}"></span>
                            <strong>{{ $status->label }}</strong>
                            <span class="small muted">{{ $status->code }}</span>
                        </div>

                        <div class="form-grid">
                            <div class="field">
                                <label>Nama status</label>
                                <input class="input" name="label" value="{{ $status->label }}" required>
                            </div>
                            <div class="field">
                                <label>Warna</label>
                                <div class="color-control">
                                    <input type="color" name="color" value="{{ $status->color }}" required>
                                    <span>{{ $status->color }}</span>
                                </div>
                            </div>
                            <div class="field">
                                <label>Urutan</label>
                                <input class="input" type="number" name="sort_order" min="0" value="{{ $status->sort_order }}" required>
                            </div>
                            <div class="field">
                                @if($status->system)
                                    <input type="hidden" name="active" value="1">
                                    <label><input type="checkbox" checked disabled> Aktif (status inti)</label>
                                @else
                                    <label><input type="checkbox" name="active" value="1" @checked($status->active)> Aktif</label>
                                @endif
                            </div>
                        </div>
                        <button class="btn btn-soft btn-sm">Simpan status</button>
                    </form>

                    @if(!$status->system && $statusUsed === 0)
                        <form method="post" action="{{ route('owner.master.status.destroy', $status) }}" data-confirm-title="Hapus permanen" data-confirm="Hapus status custom '{{ $status->label }}' secara permanen? Status ini belum pernah digunakan oleh Batch, order, tracking, atau histori status. Tindakan tidak dapat dibatalkan." style="margin-top:8px">
                            @csrf
                            @method('delete')
                            <button class="btn btn-danger btn-sm">Hapus status</button>
                        </form>
                    @elseif(!$status->system && $statusUsed > 0)
                        <div class="delete-note">
                            Tidak dapat dihapus karena sudah dipakai: {{ $statusUsageText }}. Nonaktifkan jika tidak ingin status dipakai lagi.
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <form class="card card-blue" method="post" action="{{ route('owner.master.status') }}" style="margin-top:16px">
            @csrf
            <h3>Tambah status custom</h3>
            <div class="form-grid">
                <div class="field">
                    <label>Kode</label>
                    <input class="input" name="code" placeholder="quality_check" required>
                </div>
                <div class="field">
                    <label>Nama status</label>
                    <input class="input" name="label" placeholder="Quality Check" required>
                </div>
                <div class="field">
                    <label>Warna</label>
                    <input type="color" name="color" value="#2d63d7" required>
                </div>
                <div class="field">
                    <label>Urutan</label>
                    <input class="input" type="number" name="sort_order" min="0" value="90" required>
                </div>
                <input type="hidden" name="active" value="1">
            </div>
            <button class="btn btn-primary">Tambah status</button>
        </form>
    </div>
</div>
@endsection
