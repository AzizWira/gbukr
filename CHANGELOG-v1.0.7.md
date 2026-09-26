# GBUKPOP x KRJASTIP v1.0.7

## Import / Migrasi
- Preview import menggunakan disk `local` secara eksplisit, tidak lagi bergantung pada `FILESYSTEM_DISK` aplikasi.
- Validasi file memakai extension + pembacaan workbook asli untuk menghindari false negative MIME XLSX pada beberapa instalasi Windows.
- Proses penyimpanan upload, pembacaan workbook, cleanup, dan preview sekarang dilindungi error handling lengkap.
- Error import ditulis ke `storage/logs/laravel.log` dan ditampilkan sebagai pesan form, bukan HTTP 500 kosong.
- Saat `APP_DEBUG=true`, detail exception lokal ditambahkan ke pesan agar debugging lebih cepat.
- PhpSpreadsheet reader menggunakan `readDataOnly` agar workbook legacy dengan formula menggunakan nilai hasil/cached value dan tidak memaksa kalkulasi formula saat import.
- Preview menandai sheet yang dikenali importer serta sheet expected yang tidak ditemukan.
- View migrasi ditulis ulang multiline untuk menghindari masalah kompilasi Blade dari directive yang terlalu rapat.
- Ditambah feature test untuk valid workbook dan invalid workbook.

## Workbook referensi yang diverifikasi
Importer tetap menargetkan pola:
- `STATUS BARANG`
- `TAGIHAN KR`
- `TAGIHAN CH`
- `TAGIHAN JP`
