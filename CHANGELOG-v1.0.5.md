# GBUKPOP x KRJASTIP v1.0.5

## Perbaikan utama

- Kode Batch sekarang auto-generate berdasarkan negara + tanggal + nomor urut.
- Tracking Number tetap manual/opsional karena harus mengikuti nomor asli dari seller/kurir.
- Batch otomatis membuat tracking publik sejak Batch disimpan.
- Tracking Batch tersinkron saat customer/item ditambahkan atau status Batch berubah.
- Perbaikan ParseError pada halaman detail Batch dengan Blade yang ditulis ulang lebih aman.
- Validasi warehouse wajib sesuai negara Batch.
- Migration repair untuk data Batch lama yang warehouse-nya beda negara dan tracking Batch lama.
- Validasi checkout/cart/produk/tagihan/pembayaran/master data diperketat.
- Menutup kondisi partial-save produk PO ketika validasi tanggal gagal.
- Mencegah pembayaran/reject ganda dan memperbaiki locking pembayaran.
- Memperbaiki query filter yang sebelumnya bisa lolos karena kombinasi `OR`.
- Import XLSX dibuat lebih tahan error dan lebih idempotent.
- Seeder demo diperluas dengan data yang konsisten dan saling terhubung.
- Penambahan feature tests untuk Batch, tracking, product validation, view smoke, dan demo seeder.

## Catatan upgrade

Setelah patch dari v1.0.4:

```bash
php artisan migrate
php artisan optimize:clear
```

Jika ingin reset database demo:

```bash
php artisan migrate:fresh --seed
```
