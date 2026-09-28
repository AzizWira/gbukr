@extends('layouts.dashboard')
@section('title', 'Migrasi Data')

@section('dashboard')
<div class="page-head">
    <div>
        <div class="eyebrow">MIGRASI</div>
        <h1>Import spreadsheet lama</h1>
        <p class="muted">Satu workbook lama diperlakukan sebagai satu sumber GO agar data dari file berbeda tidak tercampur tanpa sengaja.</p>
    </div>
</div>

<div class="import-steps"><span class="active">1 Upload</span><span class="{{ isset($preview) ? 'active' : '' }}">2 Review</span><span>3 Pilih GO</span><span>4 Import</span><span>5 Hasil</span></div>

<div class="grid grid-2">
    <form class="card" method="post" action="{{ route('owner.import.preview') }}" enctype="multipart/form-data" data-loading-text="Membaca workbook…">
        @csrf
        <h3>1. Pilih workbook</h3>
        <p class="small muted">Gunakan file XLSX/XLS asli. Maksimal 5 MB. Preview belum mengubah database.</p>

        <div class="field" style="margin-top:14px">
            <label>File spreadsheet</label>
            <input class="input" type="file" name="file" accept=".xlsx,.xls" required>
            @error('file')
                <div class="help" style="color:var(--danger)">{{ $message }}</div>
            @enderror
        </div>

        <button class="btn btn-primary" style="margin-top:14px">Preview workbook</button>
    </form>

    <div class="card">
        <h3>2. Preview &amp; tentukan GO</h3>

        @if(isset($preview))
            <div class="notice small" style="margin:12px 0 14px">
                File: <strong>{{ $importName ?? session('legacy_import_name') }}</strong> · sekitar {{ number_format($preview['estimated_rows'] ?? 0, 0, ',', '.') }} baris akan diperiksa.
            </div>

            @foreach($preview['sheets'] as $row)
                <div class="summary-row">
                    <div>
                        <strong>{{ $row['sheet'] }}</strong>
                        <div class="small {{ $row['recognized'] ? '' : 'muted' }}" style="{{ $row['recognized'] ? 'color:var(--ok)' : '' }}">
                            {{ $row['mode'] }}
                        </div>
                    </div>
                    <span>{{ number_format($row['rows'], 0, ',', '.') }} baris · {{ $row['columns'] }}</span>
                </div>
            @endforeach

            <form method="post" action="{{ route('owner.import.run') }}" style="margin-top:18px" data-go-choice-form data-confirm="Jalankan migrasi workbook ini? Data akan dimasukkan ke GO yang dipilih dan diproses di background." data-loading-text="Menjadwalkan import…">
                @csrf
                <div class="field">
                    <label>Masukkan data ke GO</label>
                    <select class="select" name="go_group_id" data-go-existing>
                        <option value="">Pilih GO yang sudah ada</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" @selected(old('go_group_id') == $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="divider-text">atau</div>

                <div class="field">
                    <label>Buat nama GO baru dari workbook ini</label>
                    <input class="input" name="new_go_name" value="{{ old('new_go_name') }}" maxlength="120" placeholder="Contoh: CORTIS / SEVENTEEN / GO September" data-go-new>
                    <div class="help">Isi salah satu saja: pilih GO lama atau buat nama GO baru.</div>
                </div>

                @error('go_group_id')<div class="help" style="color:var(--danger);margin-top:8px">{{ $message }}</div>@enderror
                @error('new_go_name')<div class="help" style="color:var(--danger);margin-top:8px">{{ $message }}</div>@enderror

                <div class="import-review-box">
                    <strong>Sebelum dilanjutkan</strong>
                    <ul>
                        <li>{{ number_format($preview['estimated_rows'] ?? 0,0,',','.') }} baris akan diperiksa.</li>
                        <li>{{ $preview['recognized_count'] ?? 0 }} sheet dikenali importer.</li>
                        <li>Baris tanpa kode Batch akan dibuatkan Batch legacy otomatis.</li>
                        <li>File asli tetap disimpan sebagai arsip dan dapat diunduh kembali.</li>
                    </ul>
                </div>
                <button class="btn btn-primary" style="margin-top:16px">Jalankan import</button>
            </form>
        @else
            <div class="empty">Pilih workbook untuk melihat struktur dan menentukan GO tujuan.</div>
        @endif
    </div>
</div>

<div class="card" style="margin-top:18px">
    @error('import')
        <div class="alert error" style="margin-bottom:14px">{{ $message }}</div>
    @enderror
    <div class="section-head">
        <div>
            <h3>Riwayat import</h3>
            <p>Import besar berjalan di background sehingga browser tidak perlu menunggu sampai selesai.</p>
        </div>
    </div>

    @forelse($runs as $run)
        <div class="import-run" data-import-run data-status-url="{{ route('owner.import.status', $run) }}">
            <div class="summary-row">
                <div>
                    <strong>{{ $run->original_name }}</strong>
                    <div class="small muted">GO: {{ $run->goGroup?->name ?: '-' }} · dibuat {{ $run->created_at->translatedFormat('d F Y, H.i') }}</div>
                </div>
                <span class="badge import-run-status {{ $run->status === 'completed' ? 'ok' : ($run->status === 'failed' ? 'danger' : ($run->status === 'rolled_back' ? 'gray' : 'warn')) }}" data-import-status="{{ $run->status }}">{{ $run->statusLabel() }}</span>
            </div>
            <div class="import-progress" aria-label="Progress import">
                <span class="import-progress-bar" style="width:{{ $run->progressPercent() }}%"></span>
            </div>
            <div class="small muted import-run-detail" style="margin-top:7px">
                {{ number_format($run->processed_rows, 0, ',', '.') }} / {{ number_format($run->total_rows, 0, ',', '.') }} baris
                @if($run->status === 'completed' && $run->summary)
                    · {{ $run->summary['orders'] ?? 0 }} order · {{ $run->summary['invoices'] ?? 0 }} tagihan · {{ $run->summary['adjustments'] ?? 0 }} kekurangan
                @elseif($run->status === 'failed')
                    · {{ $run->friendlyErrorMessage() }}
                @endif
            </div>
            <div class="actions" style="margin-top:10px">
                <a class="btn btn-neutral btn-sm" href="{{ route('owner.import.source', $run) }}" data-no-dirty-guard>Download file asli</a>
                @if($run->status === 'failed')
                    <form method="post" action="{{ route('owner.import.retry',$run) }}" data-confirm-title="Coba ulang import?" data-confirm="Import akan dimulai lagi dari file yang sama. Data yang sudah sempat masuk menggunakan identitas legacy yang sama sehingga akan diperbarui, bukan digandakan." data-loading-text="Menjadwalkan ulang…">
                        @csrf
                        <button class="btn btn-primary btn-sm" type="submit">Coba lagi</button>
                    </form>
                @endif
                @if(in_array($run->status,['completed','failed'],true))
                    <form method="post" action="{{ route('owner.import.cleanup',$run) }}" data-confirm-title="Cleanup hasil import?" data-confirm="Order tanpa histori pembayaran akan dihapus. Order yang sudah memiliki histori pembayaran hanya akan dikeluarkan dari Batch agar tagihan dan pembayaran tetap tersimpan. Batch legacy yang kemudian kosong akan dihapus. File workbook asli tetap disimpan.">
                        @csrf
                        @method('delete')
                        <button class="btn btn-danger btn-sm" type="submit">Cleanup hasil import</button>
                    </form>
                @elseif($run->status === 'rolled_back')
                    <span class="badge gray">Hasil import sudah dibersihkan</span>
                @endif
            </div>
        </div>
    @empty
        <div class="empty">Belum ada riwayat import.</div>
    @endforelse
</div>

<div class="notice small" style="margin-top:18px">
    Importer mendukung STATUS BARANG serta TAGIHAN KR, CH, JP/JPN, THAI, PH, MY, SG, TW, USA, INA, dan TAGIHAN PO. Sheet lain seperti FREEBIES atau HANDCARRY tetap terlihat di preview tetapi tidak dipaksa masuk database sebelum aturan bisnisnya jelas. File workbook asli tetap disimpan sebagai arsip dan dapat diunduh kembali dari Riwayat import.
</div>
@endsection
