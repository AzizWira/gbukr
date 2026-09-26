# GBUKPOP x KRJASTIP — GBUKR

GBUKR adalah aplikasi web untuk mengelola PO, Ready Stock, Batch/GO, tagihan, pembayaran transfer manual, kalkulator harga, tracking barang, migrasi spreadsheet lama, dan backup data GBUKPOP x KRJASTIP.

## Role

### Customer
- registrasi/login email atau Google;
- verifikasi email dan reset password;
- melihat produk PO dan Ready Stock;
- keranjang dan checkout;
- melihat order, tagihan, denda, dan Tagihan Tambahan;
- memilih rekening tujuan sebelum mengirim bukti pembayaran;
- upload bukti transfer;
- melihat tracking dan status barang.

### Admin
Admin berasal dari akun customer yang diberi akses Admin oleh Owner. Saat login, user dapat memilih mode Customer atau Admin.

Mode Admin difokuskan pada:
- Batch;
- Tracking;
- pembaruan status operasional.

Admin tidak memiliki akses ke konfigurasi finansial, customer global, produk, migrasi, backup, atau pengaturan Owner.

### Owner
Owner memiliki kontrol penuh terhadap:
- produk, PO, Ready Stock dan variasi;
- Batch dan customer di dalam Batch;
- GO dan warehouse;
- tagihan, pembayaran dan bukti transfer;
- Tagihan Tambahan (Tax, Shipping Aktual, Berat, Rate, Berat + Rate, Lainnya);
- Rate dan mata uang;
- Data Master: shipping, rekening, GO, warehouse dan status;
- status custom beserta warna;
- Admin;
- migrasi spreadsheet;
- export dan backup XLSX.

## Alur utama

### PO / Ready Stock
Customer memilih variasi → tambah ke Keranjang → checkout → invoice dibuat → customer transfer ke rekening yang dipilih → upload bukti → Owner verifikasi → order berjalan sampai selesai.

### Batch
Order diambil dari GO luar website → Owner membuat Batch → memasukkan customer/order → status Batch berlaku ke seluruh order terkait → bila ada Tax/Rate/Berat/Shipping tambahan, Owner membuat Tagihan Tambahan tanpa mengubah tagihan awal.

### Tagihan Tambahan
Satu order atau satu Batch dapat memiliki lebih dari satu tambahan sekaligus. Setiap tambahan disimpan terpisah agar histori tetap jelas.

Jenis:
- Tax / Pajak;
- Shipping Aktual;
- Penyesuaian Berat;
- Penyesuaian Rate;
- Berat + Rate;
- Lainnya.

Field form berubah mengikuti jenis yang dipilih.

## Kalkulator Batch
Rumus:

```text
Fee per barang = ((Fee Shipping × Rate) + Fee Admin) ÷ Jumlah Barengan
Estimasi Total = (Harga Barang × Rate) + Fee per barang
```

Harga kalkulator merupakan estimasi shipping dan harga bersih negara asal; tax/pajak belum termasuk kecuali dinyatakan lain.

## Pembayaran
- transfer langsung ke rekening Owner;
- customer wajib memilih rekening yang digunakan;
- bukti pembayaran dapat berupa gambar atau PDF;
- preview tersedia sebelum upload dan pada dashboard Owner;
- Owner menerima/menolak setelah mencocokkan mutasi.

## Tracking & Status
Owner dapat mengubah nama, urutan, aktif/nonaktif, dan warna status dari Data Master. Core status tetap mempunyai kode internal agar otomatisasi seperti Arrived GBU/KRJASTIP dan Unclaimed tidak rusak.

## Migrasi & Backup
Importer mendukung workbook legacy GBUKPOP/KRJASTIP dan memproses file besar melalui queue. Backup dapat diekspor ke XLSX per GO atau backup lengkap dengan beberapa sheet.

## Setup lokal

```powershell
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

Jalankan aplikasi:

```powershell
php artisan serve
```

Scheduler:

```powershell
php artisan schedule:work
```

Jika `QUEUE_CONNECTION=database`:

```powershell
php artisan queue:work --timeout=900 --tries=3
```

## Environment penting

```env
APP_URL=http://127.0.0.1:8000
QUEUE_CONNECTION=database
QUEUE_RETRY_AFTER=1200

OWNER_NAME="Owner GBUKR"
OWNER_EMAIL=owner@example.com
OWNER_PASSWORD=ubah-password-minimal-12-karakter

SEED_DEMO_DATA=true
DEMO_PASSWORD=DemoGBUKR2026!
```

SMTP dan Google OAuth diisi sesuai provider yang digunakan.

## Demo data
Seeder demo bersifat scenario-driven. Data demo mencakup beberapa negara, PO, Ready Stock, Batch di beberapa tahap status, invoice unpaid/partial/pending/paid/overdue, payment pending/approved/rejected, Unclaimed, rekening aktif/nonaktif, status custom, serta seluruh jenis Tagihan Tambahan termasuk satu order dengan Tax + Rate sekaligus.

Untuk production, gunakan:

```env
SEED_DEMO_DATA=false
```
