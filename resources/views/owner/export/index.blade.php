@extends('layouts.dashboard')
@section('title', 'Export & Backup')

@section('dashboard')
<div class="page-head">
    <div>
        <div class="eyebrow">EXPORT &amp; BACKUP</div>
        <h1>Download rekap data</h1>
        <p class="muted">Gunakan export per GO untuk rekap operasional atau backup lengkap untuk menyimpan seluruh data utama.</p>
    </div>
    <a class="btn btn-neutral" href="{{ route('owner.import.index') }}">Arsip workbook lama</a>
</div>

<div class="grid grid-2">
    <form class="card" method="post" action="{{ route('owner.export.go') }}" data-loading-text="Menyiapkan file GO…" data-download-form data-no-dirty-guard>
        @csrf
        <div class="eyebrow">PER GO</div>
        <h3 style="margin-top:6px">Export workbook per GO</h3>
        <p class="small muted">File berisi beberapa sheet seperti RINGKASAN, STATUS BARANG, TAGIHAN per negara, TAGIHAN TAMBAHAN, PEMBAYARAN, dan ARSIP IMPORT.</p>
        <div class="field" style="margin-top:16px">
            <label>GO yang ingin diexport</label>
            <select class="select" name="go_group_id" required>
                <option value="">Pilih GO</option>
                @foreach($groups as $group)
                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-primary" style="margin-top:16px">Download rekap GO</button>
    </form>

    <form class="card card-lilac" method="post" action="{{ route('owner.export.full') }}" data-confirm="Download backup lengkap seluruh data utama sekarang?" data-loading-text="Menyiapkan backup lengkap…" data-download-form data-no-dirty-guard>
        @csrf
        <div class="eyebrow">BACKUP LENGKAP</div>
        <h3 style="margin-top:6px">Export seluruh data</h3>
        <p class="small muted">Berisi GO, customer, batch, order, item, tagihan, tagihan tambahan, pembayaran, metadata bukti pembayaran, arsip import, tracking, produk, dan warehouse dalam sheet terpisah.</p>
        <div class="notice small" style="margin-top:16px">File gambar/PDF bukti transfer dan foto produk tidak ditempel ke workbook. Metadata bukti tetap ikut diexport, sedangkan workbook sumber migrasi dapat diunduh kembali dari menu Migrasi.</div>
        <button class="btn btn-primary" style="margin-top:16px">Download backup lengkap</button>
    </form>
</div>
@endsection
