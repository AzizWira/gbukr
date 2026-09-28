# GBUKPOP x KRJASTIP — GBUKR

GBUKR adalah aplikasi web operasional untuk mengelola PO, Ready Stock, Batch/GO, tagihan, pembayaran transfer manual, kalkulator harga, tracking barang, migrasi spreadsheet lama, dan backup data GBUKPOP x KRJASTIP.

## Role

### Customer
Customer dapat registrasi/login melalui email atau Google, memverifikasi email, menggunakan kalkulator, melihat katalog, memasukkan beberapa barang ke Keranjang, checkout, melihat order/tagihan, memilih rekening transfer, mengunggah bukti pembayaran, dan memantau tracking barang.

### Admin
Admin berasal dari akun Customer yang diberi akses Admin oleh Owner. Saat login user yang memiliki akses Admin dapat memilih masuk sebagai Customer atau Admin. Mode Admin hanya berfokus pada operasional Batch dan Tracking; akses finansial dan konfigurasi Owner tidak diberikan.

### Owner
Owner mengelola seluruh sistem: produk dan variasi, PO/Ready Stock, Batch, GO, warehouse, customer, tagihan, pembayaran, rekening, rate, shipping, status, akses Admin, migrasi spreadsheet, dan export/backup.

## PO, Ready Stock, dan Keranjang
- Produk dapat mempunyai beberapa variasi, website sumber, estimasi berat, harga foreign/IDR dan DP per variasi.
- Status tax produk dapat ditandai sudah termasuk estimasi tax atau belum termasuk tax.
- Gambar produk disimpan dalam format yang dioptimalkan jika server mendukung GD/WebP.
- Setelah Tambah ke Keranjang customer tetap berada di detail produk; Keranjang dapat dibuka dari navbar/sidebar.
- Ready Stock selalu mengecek stok kembali saat checkout.

## Batch dan kode otomatis
Batch manual yang dibuat Owner tidak membutuhkan input kode. Sistem membuat kode yang lebih mudah dibaca dengan pola:

```text
{NEGARA}-{GO/CONTEXT}-{URUTAN}
```

Contoh:

```text
KR-ENHYPEN-001
US-KRJASTIP-002
```

Bagian tengah memakai nama GO bila Batch terhubung ke GO. Jika GO kosong, sistem memakai nama Batch sebagai context dan menormalisasinya menjadi token singkat. Nomor urut dihitung per prefix sehingga kode tetap unik. Kode tidak berubah ketika metadata Batch diedit.

Batch hasil migrasi spreadsheet memakai pola berbeda agar referensi lama tetap dapat dilacak:

```text
{NEGARA}-LEG-G{ID_GO}-{REFERENSI_LAMA}
```

Contoh:

```text
CH-LEG-G3-311
```

Artinya:
- `CH` = negara China;
- `LEG` = data berasal dari migrasi legacy;
- `G3` = data terikat ke record GO dengan ID database 3;
- `311` = nilai Batch/referensi asli dari spreadsheet setelah dinormalisasi.

Jika sebuah baris workbook sama sekali tidak mempunyai Batch/referensi, importer membuat referensi fallback deterministik seperti `UNASSIGNED-ABC123`. Fallback dibedakan berdasarkan workbook dan negara. STATUS BARANG dan TAGIHAN dari workbook/negara yang sama dapat mengarah ke fallback Batch yang sama, sementara file atau GO berbeda tidak tercampur.

## Kalkulator Batch

```text
Fee per barang = ((Fee Shipping × Rate) + Fee Admin) ÷ Jumlah Barengan
Estimasi Total = (Harga Barang × Rate) + Fee per barang
```

Kalkulator menggunakan rate aktif dan simbol mata uang yang diatur Owner. Shipping dapat bernilai 0 untuk Free Shipping. Nilai kalkulator adalah estimasi; shipping aktual dapat berubah dan harga barang merupakan harga bersih negara asal yang belum termasuk tax kecuali dinyatakan lain.

## Tagihan Tambahan
Satu order atau Batch dapat memiliki beberapa Tagihan Tambahan sekaligus tanpa mengubah tagihan awal. Jenisnya meliputi Tax/Pajak, Shipping Aktual, Penyesuaian Berat, Penyesuaian Rate, Berat + Rate, dan Lainnya. Field form menyesuaikan jenis yang dipilih.

## Pembayaran
Customer wajib memilih rekening tujuan yang benar sebelum mengirim bukti transfer. Bukti dapat berupa JPG, PNG, WEBP, atau PDF. Gambar bukti dioptimalkan saat disimpan jika server mendukung GD/WebP; PDF dipertahankan apa adanya. Owner dapat preview bukti lalu menerima atau menolak pembayaran setelah mencocokkan mutasi.

## Search, filter, dan pagination
Daftar utama seperti Batch, Tracking, Order, Tagihan, Pembayaran, Customer, Produk, serta daftar milik Customer mendukung pencarian. Untuk identifier seperti kode Batch/Order/Invoice/Payment, pencarian toleran terhadap perbedaan huruf besar-kecil dan tanda pemisah. Filter memiliki tombol Clear.

Jika hasil mempunyai lebih dari satu halaman, pagination menyediakan Previous/Next, input nomor halaman, informasi rentang data, serta pilihan 20/50/100 data per halaman sehingga user dapat langsung menuju halaman tertentu tanpa maju satu per satu.

## Siklus hidup data
Sistem memakai **conditional delete** untuk mencegah histori rusak. Data yang belum pernah dipakai dapat dihapus permanen dengan dialog konfirmasi yang menjelaskan dampaknya. Jika record sudah dipakai, hard delete diblokir dan Owner diarahkan memakai Nonaktifkan/Arsip/Batal sesuai jenis datanya.

- Negara/rate: dapat dihapus hanya jika belum terhubung ke produk, Batch, tracking, shipping, atau warehouse.
- Shipping: dapat dihapus permanen karena kalkulator tidak menyimpan foreign key shipping pada transaksi lama; nominal transaksi sudah menjadi snapshot.
- Warehouse: dapat dihapus jika belum dipakai Batch; jika sudah dipakai gunakan Nonaktifkan.
- GO: dapat dihapus jika belum memiliki Batch, order, atau riwayat import; jika sudah dipakai gunakan Nonaktifkan.
- Rekening: dapat dihapus jika belum pernah dipakai pembayaran; jika sudah dipakai gunakan Nonaktifkan agar rekening tujuan lama tetap terbaca.
- Status custom: dapat dihapus jika belum digunakan; status inti tidak dapat dihapus.
- Produk/variasi: dapat dihapus jika belum pernah dipakai item order; jika sudah dipakai gunakan Nonaktifkan.
- Customer: dapat dihapus jika belum mempunyai order, tagihan, pembayaran, dan tidak memiliki akses Admin; customer berhistori hanya dapat dinonaktifkan.
- Batch: dapat diedit dan dibersihkan. Order tanpa histori pembayaran dapat dihapus permanen. Order yang sudah memiliki histori pembayaran dapat dikeluarkan dari Batch tanpa menghapus order/tagihan/payment; setelah relasi operasional bersih, Batch dapat dihapus permanen.
- Tracking manual: dapat diedit/dihapus; Tracking yang berasal dari Batch dikelola melalui Batch agar sinkron.
- Tagihan: dapat diedit atau dibatalkan sebelum mempunyai proses pembayaran. Pembatalan menyimpan histori.
- Order: hard delete hanya tersedia bila belum memiliki histori pembayaran. Untuk Ready Stock yang aman dihapus, stok variasi dikembalikan otomatis. Order non-Batch yang sudah bergerak dari status Ordered tetap dilindungi sebagai histori operasional.
- Payment tidak dihapus sembarangan karena merupakan histori finansial.
- Import Run yang selesai/gagal dapat menjalankan Cleanup hasil import. Order tanpa histori finansial dihapus, sedangkan order berhistori pembayaran hanya dilepas dari Batch. File workbook asli tetap disimpan.
- Customer legacy dapat digabungkan ke akun Customer yang benar melalui fitur merge.

## Tracking dan status
Tracking publik tidak menampilkan identitas pribadi customer. Owner dapat mengatur label, urutan, aktif/nonaktif, dan warna status. Core status dijaga agar otomatisasi status Batch/Order dan Unclaimed tetap bekerja.

## Migrasi spreadsheet lama
- Upload workbook XLSX/XLS maksimal 5 MB.
- Satu workbook wajib dipetakan ke tepat satu GO: pilih GO yang sudah ada atau buat GO baru. Import tidak dapat dijalankan sebelum pilihan tersebut valid.
- Flow import dibuat bertahap: Upload → Preview/Review → Pilih/Buat GO → Import → Hasil.
- Import berjalan melalui queue agar workbook besar tidak timeout di request browser. Pembacaan XLSX/XLS dilakukan per chunk/sheet supaya tidak memuat seluruh workbook ke RAM sekaligus dan tetap aman pada memory limit yang ketat.
- Baris tanpa Batch tetap dapat diimport menggunakan fallback Batch otomatis.
- File workbook asli dipertahankan sebagai arsip karena XLSX sendiri sudah terkompresi dan perlu dapat diunduh kembali.
- Sheet yang belum mempunyai aturan bisnis jelas tidak dipaksakan masuk database.
- Hasil import dapat di-cleanup dari Riwayat Import. Histori finansial tidak dihapus: order yang sudah dibayar dilepas dari Batch dan tetap tersedia sebagai audit. Import gagal dapat dicoba ulang dari file yang sama tanpa menggandakan row legacy yang sudah sempat masuk.

## Kebijakan file
- Gambar produk: upload maksimal 5 MB, resize maksimal sekitar 1800 px dan disimpan sebagai WebP jika GD/WebP tersedia.
- Bukti pembayaran gambar: upload maksimal 5 MB dan dioptimalkan dengan kebijakan yang sama.
- Bukti pembayaran PDF: maksimal 5 MB dan disimpan apa adanya.
- Workbook migrasi: maksimal 5 MB dan dipertahankan asli.
- Semua input file gambar mempunyai preview di browser sebelum form dikirim.

## Email
Email verifikasi dan reset password dikirim langsung agar tidak bergantung pada worker queue. Notifikasi operasional/transaksi dapat menggunakan queue. Template email menggunakan branding GBUKR.

## Export dan backup
Owner dapat export data per GO atau backup lengkap ke XLSX dengan beberapa sheet agar struktur rekap tetap mudah dibaca. Backup mempertahankan informasi transaksi, rekening, status, dan metadata penting lain yang digunakan sistem.

## UX operasional
- Dashboard Owner memprioritaskan pekerjaan yang perlu ditangani: pembayaran pending, tagihan overdue, Batch tanpa tracking, Batch Arrived Indo, PO yang tutup hari ini, dan Unclaimed.
- Form yang berubah menampilkan indikator “Perubahan belum disimpan” dan memberi konfirmasi sebelum user meninggalkan halaman.
- Dangerous action selalu memakai dialog konfirmasi yang menjelaskan dampak data.
- Detail Order menampilkan timeline perubahan status.
- Keranjang memiliki badge, halaman ringkasan visual, serta mini-cart desktop; menambah barang tidak memaksa user keluar dari detail produk.
- Tabel isi Batch berubah menjadi card-like layout di layar kecil agar aksi cleanup tetap terbaca.

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

Jika menggunakan database queue:

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

SMTP dan Google OAuth diisi sesuai provider. Untuk server yang akan mengoptimalkan gambar, aktifkan extension PHP GD dengan dukungan WebP.

## Demo data
Seeder demo dibuat berdasarkan skenario sistem, bukan sekadar menambah banyak baris. Data demo mencakup beberapa negara, PO tax-included/tax-excluded, PO DP/Full Payment, Ready Stock, Free Shipping, Batch pada beberapa status, invoice unpaid/partial/pending/paid/overdue, denda, payment pending/approved/rejected, Unclaimed, rekening aktif/nonaktif, status custom, serta seluruh jenis Tagihan Tambahan termasuk satu order dengan Tax + Rate sekaligus.

Untuk production:

```env
SEED_DEMO_DATA=false
```


## Catatan deployment asset

Layout memberi version query otomatis pada `css/app.css` dan `js/app.js` berdasarkan waktu modifikasi file. Pada struktur Hostinger yang memisahkan `app/public` dan `public_html`, tetap sinkronkan CSS/JS/images ke `public_html` setelah `git pull`; query version mencegah browser/CDN mempertahankan asset versi sebelumnya.
