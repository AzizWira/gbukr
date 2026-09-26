# PRODUCT REQUIREMENTS DOCUMENT (PRD)
# GBUKPOP x KRJASTIP — Web Order, Tagihan, Kalkulator & Tracking

**Versi:** 1.1  
**Tanggal:** 25 September 2026  
**Status:** Baseline Scope Revisi / Siap Menjadi Acuan Desain & Pengembangan  
**Nama Produk:** GBUKPOP x KRJASTIP  

## Riwayat Revisi

### v1.1 — 25 September 2026

Penambahan requirement inti:

- login menggunakan email dan password;
- login/registrasi menggunakan Google;
- verifikasi email;
- forgot password dan reset password melalui email;
- notifikasi email menjadi fitur wajib, bukan add-on;
- aturan penyatuan akun agar login Google tidak membuat duplikasi customer;
- penyesuaian migrasi customer lama agar dapat dihubungkan ke akun terverifikasi.

---

## 1. Ringkasan Produk

GBUKPOP x KRJASTIP adalah website untuk membantu pengelolaan order, tagihan, pembayaran, kalkulator harga, dan tracking barang yang sebelumnya banyak dilakukan melalui Google Sheet/Spreadsheet dan komunikasi manual melalui GO.

Website bukan aplikasi “group order” tempat banyak customer bergabung dalam satu keranjang. GO tetap dapat berlangsung di aplikasi lain seperti WhatsApp, LINE, atau media lain. Website berfungsi sebagai pusat data dan layanan agar:

- customer dapat checkout produk PO/Ready Stock;
- customer dapat menghitung estimasi harga sendiri;
- customer dapat melihat barang yang dimiliki;
- customer dapat melihat tagihan dan riwayat pembayaran;
- customer dapat mengunggah bukti transfer;
- customer dapat melihat status dan tracking barang;
- Owner dapat mengelola PO, Batch, GO, customer, tagihan, pembayaran, rate, fee, warehouse, dan tracking dari satu tempat;
- Admin dapat membantu memperbarui status sesuai hak akses;
- data lama dari spreadsheet dapat dipindahkan ke website apabila diputuskan untuk dimigrasikan.

Website harus mempertahankan fleksibilitas cara kerja GBUKPOP x KRJASTIP, bukan memaksa seluruh order mengikuti pola e-commerce umum.

---

## 2. Latar Belakang

Pengelolaan saat ini menggunakan spreadsheet dengan struktur utama seperti:

- `STATUS BARANG`
- `TAGIHAN KR`
- `TAGIHAN CH`
- `TAGIHAN JP`

Spreadsheet status menyimpan informasi seperti Batch, Item Details, Keterangan, Qty, kode warehouse, tracking number, dan status.

Spreadsheet tagihan menyimpan informasi seperti Batch, Nama, Items, Details, Qty, Price/Tax, Full Payment, DP, Cicilan, Kenaikan, Pelunasan, Status, dan Keterangan.

Seiring bertambahnya jumlah order dan data customer, penggunaan spreadsheet mulai memiliki beberapa hambatan:

- customer perlu bertanya untuk mengetahui perhitungan harga;
- customer perlu mencari informasi tagihan secara manual;
- status barang dan tracking belum menjadi pengalaman yang terpusat;
- data customer, PO, Batch, tagihan, dan pembayaran tersebar;
- perubahan rate dan fee harus tetap mudah dikelola;
- data lama dapat mencapai 1000+ baris;
- satu customer dapat mengikuti lebih dari satu GO, PO, atau Batch;
- satu customer dapat memiliki beberapa tagihan dengan waktu pelunasan berbeda;
- status barang dalam satu Batch dapat sama, tetapi nominal tagihan setiap customer dapat berbeda.

Website baru dibuat untuk mengubah proses tersebut menjadi sistem terstruktur tanpa menghilangkan fleksibilitas operasional yang sudah berjalan.

---

## 3. Tujuan Produk

### 3.1 Tujuan Utama

1. Menggantikan sebagian besar fungsi spreadsheet dengan website yang lebih mudah digunakan.
2. Mengurangi pertanyaan berulang dari customer mengenai rate, estimasi harga, status barang, dan tagihan.
3. Memudahkan customer melihat seluruh order dari satu akun.
4. Memudahkan Owner mengelola order PO dan Batch dengan alur yang berbeda.
5. Memisahkan data tracking barang dari data tagihan agar satu Batch dapat memiliki status sama tetapi tagihan customer tetap berbeda.
6. Mempermudah pencatatan DP, cicilan, full payment, pelunasan, denda, dan bukti pembayaran.
7. Menyediakan kalkulator publik yang dapat digunakan tanpa checkout.
8. Menyediakan tracking yang mudah dipahami customer.
9. Menyediakan migrasi data lama dari spreadsheet secara terstruktur.
10. Memberikan Owner kontrol penuh terhadap data, Admin, dan perubahan status.

### 3.2 Sasaran Pengguna

- Customer GBUKPOP.
- Customer KRJASTIP.
- Customer dari berbagai GO yang dikelola Owner.
- Owner/Super Admin.
- Admin Staff yang membantu memperbarui status.

---

## 4. Prinsip Dasar Produk

1. **PO dan Batch adalah dua jalur order yang berbeda.**
2. **GO bukan grup di dalam website.** GO dapat tetap berjalan di WhatsApp, LINE, atau media lain.
3. **Customer wajib memiliki akun untuk checkout melalui website.**
4. **Kalkulator dapat digunakan tanpa login.**
5. **Rate ditentukan manual oleh Owner.**
6. **Rate order lama tidak boleh berubah ketika rate terbaru diubah.**
7. **Pembayaran langsung masuk ke rekening Owner**, bukan escrow.
8. **Pembayaran diverifikasi manual** melalui pengecekan mutasi oleh Owner.
9. **Status tracking dapat berlaku bersama untuk banyak customer**, sedangkan tagihan tetap per customer/per item.
10. **Warehouse Code adalah informasi internal** dan tidak boleh ditampilkan ke customer.
11. **Owner selalu mempunyai hak override** untuk kondisi operasional khusus.
12. **Informasi customer tidak boleh muncul di halaman tracking publik.**

---

# 5. Definisi Istilah

## 5.1 GO

GO adalah kelompok/aktivitas order yang dikelola di luar website, misalnya melalui WhatsApp atau LINE.

Satu customer dapat bergabung pada beberapa GO.

Website hanya menyimpan identitas/nama GO agar order dan tagihan dapat dibedakan.

Contoh:

- GBU GO
- KRJASTIP GO
- GO event tertentu
- GO produk tertentu

## 5.2 PO

PO digunakan ketika barang:

- dibeli langsung dari website di negara asal;
- berasal dari jastip;
- mempunyai periode order tertentu;
- di-drop sebagai produk pada website;
- dapat di-checkout langsung oleh customer melalui website.

Customer melakukan checkout sendiri dan datanya otomatis tercatat pada PO.

## 5.3 Batch

Batch digunakan ketika barang:

- dibeli dari tangan kedua/seller;
- barang biasanya sudah berada di tangan seller;
- take/order dilakukan melalui GO di luar website;
- Owner perlu memasukkan customer dan nomor Batch secara manual ke website.

Batch **bukan lanjutan dari PO**.

PO dan Batch adalah dua sumber order yang berbeda.

## 5.4 Ready Stock

Ready Stock adalah barang yang telah tersedia dan dapat dipesan melalui website selama stok masih ada.

## 5.5 Warehouse

Warehouse adalah alamat/tempat penerimaan barang di negara tertentu sebelum barang dikirim ke Indonesia.

Satu negara dapat memiliki beberapa warehouse.

Setiap warehouse memiliki kode internal yang hanya dapat dilihat Owner.

## 5.6 Tagihan

Tagihan adalah kewajiban pembayaran customer yang dapat berupa:

- DP;
- Cicilan;
- Full Payment;
- Pelunasan;
- Kenaikan;
- Denda;
- Penyesuaian;
- Harga barang + tax untuk claim ulang dalam kondisi tertentu.

---

# 6. Role dan Hak Akses

## 6.1 Guest

Guest adalah pengguna yang belum login.

Guest dapat:

- membuka halaman publik;
- melihat katalog yang diperbolehkan;
- melihat detail produk;
- menggunakan kalkulator;
- melihat tracking publik;
- login;
- registrasi.

Guest tidak dapat:

- checkout;
- melihat tagihan customer;
- mengunggah bukti pembayaran;
- melihat riwayat order pribadi.

## 6.2 Customer

Customer dapat:

- login;
- mengelola profil;
- melihat katalog;
- checkout produk PO;
- checkout Ready Stock;
- memilih varian;
- melihat keranjang;
- melihat riwayat order;
- melihat GO/PO/Batch terkait barangnya;
- melihat tagihan;
- melihat total pembayaran;
- melihat sisa pembayaran;
- melihat deadline;
- melihat denda;
- menggabungkan beberapa tagihan yang diperbolehkan untuk dibayar bersamaan;
- mengunggah bukti pembayaran;
- mengunggah ulang bukti jika pembayaran ditolak;
- melihat status verifikasi pembayaran;
- melihat status barang;
- melihat tracking;
- melihat status Unclaimed apabila berlaku.

Customer tidak dapat:

- membatalkan transaksi secara sepihak melalui website;
- mengubah rate;
- mengubah tagihan;
- mengubah status barang;
- melihat Warehouse Code;
- melihat data customer lain.

## 6.3 Admin Staff

Admin merupakan akun bantuan yang dikelola Owner.

Default hak akses Admin:

- login ke dashboard Admin;
- melihat data yang diperlukan untuk pekerjaan status;
- memperbarui status order/tracking sesuai kebutuhan operasional.

Admin tidak memiliki kontrol penuh atas website.

Admin tidak boleh secara default:

- mengubah role Owner;
- mengelola Admin lain;
- mengubah rekening;
- mengubah rate dan fee tanpa izin Owner;
- menghapus data penting;
- mengakses pengaturan sensitif Owner.

Owner dapat menonaktifkan akun Admin kapan saja.

Ketika Admin dinonaktifkan, Admin tersebut tidak dapat mengakses dashboard lagi.

## 6.4 Owner / Super Admin

Owner memiliki kontrol penuh.

Owner dapat:

- mengelola Admin;
- menambah Admin;
- menonaktifkan Admin;
- mengelola customer;
- mengelola GO;
- mengelola produk;
- mengelola PO;
- mengelola Ready Stock;
- mengelola Batch;
- mengelola order;
- mengelola negara dan mata uang;
- mengelola rate;
- mengelola Fee Shipping;
- mengelola Fee Admin;
- mengelola warehouse;
- melihat kode warehouse;
- mengelola tagihan;
- membuat tagihan tambahan;
- mengelola pembayaran;
- memverifikasi bukti transfer;
- menolak bukti transfer;
- mengelola tracking;
- mengubah status;
- melakukan override status;
- menangani barang Unclaimed;
- melakukan/import migrasi data;
- melihat rekap data.

---

# 7. Branding dan Arah Visual

## 7.1 Nama Brand pada Website

**GBUKPOP x KRJASTIP**

## 7.2 Logo

Website menggunakan identitas dari dua brand:

- GBU / GBUKPOP
- KRJASTIP

Arah komposisi:

- kedua logo ditampilkan berdampingan;
- dapat dibuat menjadi satu komposisi logo gabungan;
- proporsi kedua brand harus seimbang;
- logo tidak boleh gepeng atau terpotong;
- logo tetap jelas pada desktop dan mobile.

## 7.3 Color Palette

Color palette diambil dari kedua logo.

Arah warna:

- biru dari KRJASTIP sebagai warna utama/tegas;
- pastel pink dari identitas GBU;
- pastel lilac;
- pastel mint/cyan;
- warna netral terang/off-white untuk menjaga keterbacaan.

Warna harus digunakan secara terkontrol agar tetap menarik tetapi tidak ramai.

## 7.4 Prinsip Desain

Desain harus:

- menarik;
- mempunyai identitas brand;
- bersih;
- rapi;
- mudah dipahami;
- tidak terasa seperti template admin generik;
- tidak terlihat seperti “AI slop”;
- menghindari dekorasi yang tidak mempunyai fungsi;
- menghindari penggunaan gradient/glow/glass yang berlebihan;
- tidak menggunakan hero atau elemen dekoratif besar hanya untuk memenuhi ruang;
- memberi prioritas pada informasi order, tagihan, dan tracking.

---

# 8. Rules UI/UX Wajib

## 8.1 Responsive

Seluruh halaman wajib responsive untuk:

- desktop;
- laptop;
- tablet;
- smartphone.

Tidak boleh terjadi:

- elemen saling bertumpuk;
- tombol terpotong;
- tabel keluar layar tanpa solusi;
- teks terlalu besar pada mobile;
- teks terlalu kecil pada desktop;
- modal tidak dapat ditutup;
- form melampaui lebar viewport.

## 8.2 Typography

Ukuran font harus menyesuaikan ukuran layar.

Hierarki yang harus jelas:

- page title;
- section title;
- label;
- data;
- helper text;
- status;
- total pembayaran.

Font tidak boleh terasa terlalu besar atau terlalu kecil.

## 8.3 Tabel pada Mobile

Data tabular yang besar harus mempunyai salah satu atau kombinasi:

- responsive horizontal table;
- card representation pada mobile;
- sticky label/header apabila diperlukan;
- filtering agar data mudah ditemukan.

## 8.4 Popup, Alert, Modal, Toast

Dilarang menggunakan popup browser bawaan sebagai pengalaman utama, seperti:

- `alert()`;
- `confirm()`;
- `prompt()`.

Semua harus menggunakan modal/toast/dialog yang mengikuti style website.

Contoh:

- konfirmasi pembayaran;
- penolakan bukti;
- konfirmasi nonaktifkan Admin;
- warning deadline;
- warning Unclaimed;
- konfirmasi close PO;
- pesan validasi.

## 8.5 Format Tanggal

Semua tanggal yang ditampilkan ke user menggunakan format manusia.

Contoh:

- `24 September 2026`
- `24 September 2026, 19.30`
- `10 Oktober 2026 pukul 23.59`

Hindari tampilan tanggal teknis seperti:

- `2026-09-24`
- timestamp database;
- format ISO mentah.

## 8.6 Bahasa Antarmuka

Hindari teks teknis dan deskripsi yang tidak dibutuhkan customer.

Contoh yang harus dihindari pada UI:

- ID database;
- nama tabel;
- kode internal;
- istilah framework;
- debug message;
- penjelasan teknis sistem.

UI menggunakan istilah bisnis yang dipahami customer.

---

# 9. Struktur Navigasi Publik

Struktur menu publik yang direkomendasikan:

1. Beranda
2. Shop / Produk
3. PO
4. Ready Stock
5. Kalkulator
6. Tracking
7. Tentang / Informasi Singkat bila diperlukan
8. Login
9. Daftar

Website tidak perlu menampilkan deskripsi teknis sistem kepada customer.

---

# 10. Registrasi, Login, Verifikasi Email, dan Profil Customer

## 10.1 Metode Akun Customer

Customer dapat mempunyai akun melalui:

1. email + password; atau
2. **Login dengan Google**.

Customer harus login sebelum melakukan checkout atau membuka fitur pribadi seperti tagihan, pembayaran, dan riwayat order.

Kalkulator dan tracking publik tetap dapat digunakan tanpa login.

## 10.2 Registrasi dengan Email dan Password

Customer dapat membuat akun menggunakan email dan password.

Data awal akun minimal:

- nama;
- email;
- password.

Setelah akun dibuat, sistem mengirim **email verifikasi**.

Sebelum email terverifikasi, customer boleh masuk ke halaman akun terbatas untuk menyelesaikan verifikasi, tetapi belum boleh melakukan transaksi utama seperti checkout.

Setelah email terverifikasi, customer dapat melengkapi profil dan menggunakan fitur transaksi.

## 10.3 Login dengan Google

Halaman login dan registrasi menyediakan tombol **Lanjutkan dengan Google**.

Ketika customer masuk menggunakan Google:

- sistem menggunakan identitas Google yang sah untuk membuat atau menemukan akun customer;
- email Google yang sudah terverifikasi dianggap telah memenuhi verifikasi email;
- customer baru diarahkan untuk melengkapi profil bisnis yang belum tersedia, misalnya nomor WhatsApp atau LINE;
- jika email Google sama dengan email akun/customer yang sudah ada, sistem harus menghubungkan proses login ke akun yang sama dan tidak membuat customer duplikat.

Login Google tidak mengubah aturan bahwa data order, tagihan, dan pembayaran tetap terhubung ke satu profil customer yang sama.

## 10.4 Verifikasi Email

Verifikasi email merupakan requirement wajib untuk akun yang dibuat menggunakan email dan password.

Sistem menyediakan:

- email verifikasi setelah registrasi;
- tombol/kontrol kirim ulang email verifikasi;
- halaman pemberitahuan bahwa email belum terverifikasi;
- pemberitahuan ketika verifikasi berhasil atau link tidak lagi berlaku.

Checkout dengan akun email/password hanya dapat dilakukan setelah email terverifikasi.

## 10.5 Forgot Password

Pada halaman login tersedia fitur **Lupa Password**.

Flow:

```text
Customer pilih Lupa Password
→ memasukkan email
→ sistem mengirim link reset password
→ customer membuka link
→ customer membuat password baru
→ password berhasil diperbarui
→ customer dapat login kembali
```

Halaman tidak boleh mengungkap secara berlebihan apakah suatu email terdaftar atau tidak. Pesan dibuat sederhana dan aman, misalnya bahwa instruksi akan dikirim apabila email sesuai dengan akun.

## 10.6 Reset Password

Link reset password:

- hanya dapat digunakan sesuai masa berlaku yang ditetapkan sistem;
- tidak dapat digunakan kembali setelah proses reset berhasil;
- mengarahkan customer ke form password baru yang menggunakan validasi yang jelas.

Setelah password berhasil diubah, customer mendapatkan konfirmasi melalui website dan email.

## 10.7 Profil Customer

Profil customer dapat menyimpan:

- nama;
- username;
- email;
- nomor WhatsApp;
- display name/ID LINE;
- catatan kontak lain yang diperlukan.

Tidak semua channel kontak harus wajib karena customer dapat berasal dari platform yang berbeda.

Customer dapat melihat dan memperbarui data kontak miliknya sesuai field yang diizinkan.

Jika perubahan email diizinkan, email baru harus diverifikasi sebelum menjadi email utama akun.

## 10.8 Customer Hasil Migrasi / Legacy

Customer hasil migrasi dari spreadsheet dapat lebih dulu tersimpan sebagai data customer tanpa akun aktif.

Ketika customer kemudian mendaftar atau login Google menggunakan email yang dapat dicocokkan secara aman dengan data legacy, sistem dapat menghubungkan akun tersebut ke profil customer yang benar agar:

- order lama tetap muncul;
- tagihan lama tetap muncul;
- tidak terbentuk customer ganda.

Jika kecocokan tidak dapat dipastikan secara aman, Owner harus dapat melakukan proses pengaitan akun secara terkontrol dari dashboard.

---

# 11. Katalog Produk

Owner dapat membuat produk dengan jenis:

- PO;
- Ready Stock.

Informasi produk minimal:

- nama produk;
- foto produk;
- deskripsi singkat;
- kategori/keterangan;
- negara;
- mata uang;
- variasi;
- harga;
- stok apabila Ready Stock;
- periode PO apabila PO;
- aturan pembayaran;
- status aktif/tidak aktif.

---

# 12. Variasi Produk

Satu produk dapat mempunyai variasi dinamis.

Contoh:

- member;
- warna;
- versi;
- jenis;
- bundle;
- tipe merchandise;
- variasi custom lainnya.

Setiap variasi dapat mempunyai:

- nama;
- harga berbeda;
- stok berbeda apabila diperlukan;
- status tersedia/tidak tersedia.

Owner tidak boleh membutuhkan perubahan coding hanya untuk menambah tipe variasi baru.

---

# 13. Sistem PO

## 13.1 Pembuatan PO

Owner dapat membuat PO dengan:

- nama PO;
- produk;
- negara;
- mata uang;
- tanggal/waktu Open PO;
- tanggal/waktu Close PO;
- variasi;
- harga;
- aturan pembayaran;
- catatan.

## 13.2 Open dan Close PO

Owner mengisi waktu penutupan PO secara manual.

Sistem otomatis menutup pemesanan setelah waktu Close PO terlewati.

Setelah PO ditutup:

- tombol checkout tidak dapat digunakan untuk order baru;
- customer tetap dapat melihat order yang sudah dibuat;
- data tagihan tetap aktif sesuai aturan.

## 13.3 Close PO Lebih Awal

Jika produk di website asal telah sold out sebelum deadline, Owner dapat menutup PO secara manual lebih awal.

Tidak ada requirement sinkronisasi stok otomatis dari website seller.

## 13.4 Order PO

Flow:

**Owner membuat PO → Customer memilih produk/varian → Customer checkout → Order otomatis tercatat pada PO**

Owner tidak perlu memasukkan nama customer satu per satu untuk order yang checkout langsung melalui website.

---

# 14. Sistem Ready Stock

Ready Stock mendukung:

- stok;
- variasi;
- jumlah pembelian;
- checkout;
- tagihan;
- pembayaran;
- riwayat order.

Jika stok habis, produk tidak dapat dipesan.

---

# 15. Sistem Batch

## 15.1 Definisi

Batch digunakan untuk order barang dari seller/tangan kedua yang di-take melalui GO di luar website.

## 15.2 Input Batch

Owner memasukkan data Batch secara manual.

Data Batch dapat meliputi:

- nomor/nama Batch;
- GO;
- customer;
- item;
- detail/variasi;
- qty;
- negara;
- warehouse;
- keterangan;
- tracking number;
- status;
- informasi tagihan.

## 15.3 Customer pada Batch

Saat input Batch, Owner dapat:

- memilih customer yang sudah mempunyai akun;
- menghubungkan order dengan customer terkait;
- menyimpan kontak customer jika akun belum tersedia sesuai kebutuhan migrasi/operasional.

## 15.4 Satu Batch Banyak Customer

Satu Batch dapat berisi banyak customer.

Ketika status Batch berubah, seluruh customer yang mempunyai item pada Batch tersebut melihat status terbaru yang sama.

Tagihan customer tetap dihitung secara terpisah.

---

# 16. Sistem GO

Owner dapat membuat daftar GO.

Informasi GO minimal:

- nama GO;
- status aktif/arsip;
- catatan internal jika diperlukan.

Setiap order Batch dapat dikaitkan dengan GO.

Setiap order PO juga dapat diberi informasi GO apabila secara bisnis diperlukan.

Satu customer dapat terhubung dengan banyak GO.

Dashboard customer harus menunjukkan asal GO agar customer tidak bingung ketika mempunyai beberapa order dari GO berbeda.

---

# 17. Negara, Mata Uang, dan Rate

## 17.1 Mata Uang Awal

Sistem minimal mendukung:

- KRW
- JPY
- CNY
- USD
- PHP
- THB / Baht

Struktur harus memungkinkan negara/mata uang lain ditambah di kemudian hari.

## 17.2 Rate

Rate tidak mengambil kurs internasional otomatis.

Owner mengubah rate sendiri berdasarkan rate/deposito yang digunakan.

Owner dapat mengatur rate per negara/mata uang melalui dashboard.

## 17.3 Rate Snapshot

Ketika customer checkout:

- rate saat checkout disimpan;
- order lama tetap menggunakan rate tersebut;
- perubahan rate setelah checkout tidak mengubah nilai transaksi lama.

Rate baru hanya digunakan oleh:

- kalkulator setelah rate diperbarui;
- checkout/order baru.

---

# 18. Fee Shipping

Fee Shipping berada dalam mata uang negara asal.

Owner dapat menyediakan beberapa pilihan Fee Shipping untuk setiap negara.

Contoh:

### Korea
- 3.000 KRW
- 5.000 KRW
- 12.000 KRW

### China
- 8 CNY
- 12 CNY
- 16 CNY

Nilai di atas hanya contoh operasional dan dapat berubah.

Owner harus dapat:

- menambah pilihan;
- mengubah pilihan;
- menonaktifkan pilihan;
- menghapus pilihan yang tidak lagi digunakan.

---

# 19. Fee Admin

Fee Admin:

- berbeda per negara;
- sudah berbentuk Rupiah;
- tidak dikalikan rate;
- dapat diubah oleh Owner.

Fee Admin digunakan pada kalkulator dan proses perhitungan sesuai kebutuhan bisnis.

---

# 20. Kalkulator Harga Publik

## 20.1 Tujuan

Kalkulator dibuat sebagai halaman terpisah.

Pengguna tidak perlu login.

Tujuannya agar customer dapat menghitung estimasi sebelum memutuskan take/order.

## 20.2 Input Customer

Customer mengisi:

- negara;
- harga barang dalam mata uang asal;
- pilihan Fee Shipping;
- jumlah barengan.

## 20.3 Data Otomatis

Sistem otomatis mengambil:

- rate negara;
- Fee Admin negara.

## 20.4 Rumus

Karena Fee Shipping masih menggunakan mata uang negara asal dan Fee Admin sudah Rupiah, rumus:

```text
Estimasi =
(Harga Barang + (Fee Shipping ÷ Jumlah Barengan)) × Rate
+ (Fee Admin ÷ Jumlah Barengan)
```

## 20.5 Contoh

Harga barang: 20.000 KRW  
Fee Shipping: 10.000 KRW  
Fee Admin: Rp25.000  
Jumlah barengan: 5  
Rate: Rp12/KRW  

Perhitungan:

```text
Shipping per bagian = 10.000 ÷ 5 = 2.000 KRW
Fee Admin per bagian = Rp25.000 ÷ 5 = Rp5.000

(20.000 + 2.000) × 12 + 5.000
= Rp269.000
```

## 20.6 Validasi

Kalkulator tidak boleh menerima:

- jumlah barengan 0;
- angka negatif;
- harga kosong;
- negara tanpa rate aktif;
- pilihan shipping yang sudah dinonaktifkan.

Hasil harus jelas dan mudah dibaca.

---

# 21. Keranjang

Customer dapat:

- menambahkan produk;
- memilih variasi;
- menentukan jumlah;
- mengubah jumlah;
- menghapus item;
- melanjutkan checkout.

Keranjang digunakan untuk produk yang memang dijual melalui website seperti PO dan Ready Stock.

Batch tidak melalui keranjang customer.

---

# 22. Checkout

Saat checkout, sistem menyimpan snapshot informasi penting:

- produk;
- variasi;
- qty;
- harga;
- rate;
- negara;
- jenis order;
- aturan pembayaran;
- customer;
- waktu checkout.

Data order lama tidak boleh berubah karena Owner mengubah harga/rate produk untuk order berikutnya.

---

# 23. Skema Pembayaran Fleksibel

Setiap produk/order dapat mempunyai skema pembayaran berbeda.

Owner tidak boleh dipaksa menggunakan template pembayaran yang sama untuk semua produk.

Skema yang didukung:

- DP;
- Cicilan;
- Full Payment;
- Pelunasan.

Contoh:

### Produk A
- DP Rp100.000
- Pelunasan kemudian

### Produk B
- Full Payment

### Produk C
- DP
- Cicilan
- Pelunasan

Owner harus dapat mengatur skema dari dashboard dengan cara yang sederhana.

---

# 24. Tagihan

## 24.1 Informasi Tagihan

Tagihan dapat memuat:

- customer;
- GO;
- PO atau Batch;
- barang;
- variasi/detail;
- qty;
- jenis tagihan;
- nominal;
- jumlah telah dibayar;
- sisa;
- deadline;
- status;
- denda;
- keterangan.

## 24.2 Jenis Tagihan

Sistem mendukung:

- Full Payment;
- DP;
- Cicilan;
- Pelunasan;
- Kenaikan;
- Denda;
- Penyesuaian;
- Claim ulang + tax apabila diperlukan.

## 24.3 Status Tagihan

Contoh status:

- Belum Dibayar;
- Menunggu Verifikasi;
- Dibayar Sebagian;
- Lunas;
- Melewati Deadline;
- Bukti Ditolak.

Status akhir dapat disesuaikan pada desain UI.

---

# 25. Penggabungan Beberapa Tagihan

Satu customer dapat mempunyai tagihan dari beberapa:

- GO;
- PO;
- Batch.

Customer dapat memilih beberapa tagihan yang diperbolehkan untuk dibayar bersamaan.

Contoh:

- GO A / Batch KR01 / Album A → Rp120.000
- GO B / Batch KR03 / Photocard B → Rp80.000
- GO C / PO JP01 / Album C → Rp200.000

Total pembayaran:

**Rp400.000**

Customer melakukan satu transfer dan mengunggah satu bukti pembayaran.

Tagihan asli tetap terpisah dan tetap mempunyai rincian masing-masing.

Sistem hanya menggabungkan proses pembayarannya.

---

# 26. Deadline DP dan Denda

## 26.1 Deadline DP

DP dapat mempunyai deadline.

## 26.2 Denda

Jika DP melewati deadline:

- denda dihitung otomatis;
- nominal denda adalah **Rp2.000 per hari keterlambatan**;
- denda otomatis ditambahkan ke total tagihan.

Contoh:

DP: Rp100.000  
Terlambat: 3 hari  
Denda: Rp6.000  

Total menjadi:

**Rp106.000**

Perhitungan harus berdasarkan tanggal sistem dan tidak boleh menggandakan denda yang sama saat halaman dibuka ulang.

---

# 27. Deadline Pelunasan

Pelunasan dapat mempunyai deadline.

Deadline pelunasan bersifat sebagai peringatan.

Jika deadline terlewati:

- sistem menampilkan bahwa pelunasan sudah melewati batas waktu;
- order tidak otomatis dibatalkan hanya karena deadline terlewati.

Owner tetap memegang keputusan operasional.

---

# 28. Pembayaran Transfer Manual

Website tidak menggunakan escrow.

Flow pembayaran:

**Customer Checkout/Tagihan → Nomor Rekening → Customer Transfer → Upload Bukti → Menunggu Verifikasi → Owner Cek Mutasi → Valid/Lunas**

## 28.1 Rekening

Website dapat menampilkan rekening Owner sesuai pengaturan.

## 28.2 Upload Bukti

Customer dapat mengunggah foto/file bukti pembayaran.

## 28.3 Verifikasi

Owner mengecek mutasi secara manual.

Owner dapat:

- menerima bukti;
- menolak bukti.

Jika diterima:

- pembayaran tercatat valid;
- tagihan diperbarui.

Jika ditolak:

- status menjadi tidak valid/ditolak;
- customer dapat upload ulang.

## 28.4 Tidak Ada Auto-Lunas dari Upload

Mengunggah bukti tidak langsung membuat pembayaran Lunas.

Lunas hanya setelah diverifikasi.

---

# 29. Pembatalan Order

Customer tidak mempunyai tombol untuk membatalkan transaksi sendiri dari website setelah order dibuat.

Penanganan perubahan atau pembatalan khusus menjadi keputusan Owner.

---

# 30. Dashboard Customer

Dashboard customer menjadi pusat informasi pribadi.

Informasi yang dapat ditampilkan:

- ringkasan order aktif;
- order selesai;
- barang yang pernah di-take;
- nama GO;
- jenis order: PO/Batch/Ready Stock;
- nomor/nama Batch apabila ada;
- produk;
- variasi/detail;
- qty;
- negara;
- harga;
- rate snapshot pada checkout;
- skema pembayaran;
- total tagihan;
- total sudah dibayar;
- sisa tagihan;
- deadline;
- denda;
- status pembayaran;
- status barang;
- tracking;
- riwayat bukti pembayaran.

Dashboard harus menyediakan pencarian/filter bila data customer banyak.

---

# 31. Tracking dan Status Barang

## 31.1 Status Utama

Status yang digunakan:

1. `Ordered`
2. `Arrived WH`
3. `OTW Indo`
4. `Arrived Indo`
5. `Arrived GBU/krjastip`
6. `Send to Customer`
7. `Selesai`

## 31.2 Status Khusus

Status tambahan:

- `Unclaimed`

Status khusus tidak harus mengikuti urutan normal.

## 31.3 Update Status Batch

Jika banyak customer berada pada Batch yang sama:

- Owner/Admin cukup mengubah status Batch;
- seluruh order yang terkait Batch tersebut menampilkan status terbaru.

Nominal tagihan customer tidak ikut disamakan.

---

# 32. Halaman Tracking Publik

Tracking dapat dilihat secara publik sesuai kebijakan Owner.

Informasi publik:

1. Batch/PO
2. Detail Barang
3. **Keterangan**
4. Negara
5. Tracking Number
6. Status

Keterangan diletakkan sebelum Negara.

Contoh Keterangan:

- Album
- Photocard
- Merchandise
- Magazine
- Skincare
- Keyring
- kategori lain

Tracking Number bersifat opsional.

Jika belum tersedia, UI menampilkan informasi yang wajar seperti:

- Belum tersedia
- Menunggu nomor tracking

Bukan nilai kosong yang membingungkan.

---

# 33. Warehouse

## 33.1 Multi Warehouse

Satu negara dapat mempunyai lebih dari satu warehouse.

## 33.2 Warehouse Code

Contoh:

- KR-WH01
- KR-WH02
- CN-WH01

Kode aktual mengikuti data Owner.

## 33.3 Privasi Warehouse

Warehouse Code:

- hanya dapat dilihat Owner;
- tidak tampil di halaman publik;
- tidak tampil pada dashboard customer.

Jika Admin tidak membutuhkan kode tersebut untuk tugas status, kode juga tidak perlu ditampilkan kepada Admin.

---

# 34. Aturan Barang Tidak Di-claim

## 34.1 Titik Awal Perhitungan

Perhitungan 3 bulan dimulai dari tanggal barang berubah menjadi:

**Arrived GBU/krjastip**

## 34.2 Setelah 3 Bulan

Jika barang belum di-claim selama 3 bulan:

- sistem menandai order sebagai `Unclaimed`;
- sistem dapat menampilkan peringatan;
- data tidak langsung dihapus.

## 34.3 Override Owner

Owner dapat melakukan override agar tidak terjadi perbedaan antara kondisi website dan keputusan operasional.

Owner dapat:

- mengembalikan status;
- melanjutkan proses claim;
- menandai barang menjadi milik GO sesuai kebijakan;
- membuat tagihan tambahan bila customer diperbolehkan claim kembali.

## 34.4 Claim Setelah Batas

Jika Owner mengizinkan claim setelah batas:

- customer dapat dikenakan harga barang kembali + tax;
- Owner dapat membuat tagihan tambahan;
- nominal tidak harus dihitung otomatis jika kondisi berbeda-beda.

---

# 35. Dashboard Owner

Dashboard Owner harus berorientasi pada operasional, bukan sekadar statistik dekoratif.

Ringkasan yang relevan dapat berupa:

- order aktif;
- PO aktif;
- Batch aktif;
- tagihan belum lunas;
- pembayaran menunggu verifikasi;
- barang Arrived GBU/krjastip;
- barang mendekati/masuk Unclaimed;
- PO yang mendekati Close PO.

Owner harus dapat masuk ke pekerjaan utama dengan cepat.

---

# 36. Modul Owner

Menu Owner yang direkomendasikan:

1. Dashboard
2. Produk
3. PO
4. Ready Stock
5. GO
6. Batch
7. Customer
8. Order
9. Tagihan
10. Pembayaran
11. Tracking / Status Barang
12. Negara & Rate
13. Fee Shipping
14. Fee Admin
15. Warehouse
16. Admin
17. Import / Migrasi Data
18. Pengaturan
19. Email / Notifikasi

---

# 37. Pengelolaan Admin

Owner dapat:

- membuat akun Admin;
- melihat Admin aktif;
- menonaktifkan Admin;
- mengaktifkan kembali apabila diperlukan.

Admin yang dinonaktifkan:

- tidak boleh dapat login;
- sesi aktif harus tidak dapat digunakan lagi setelah mekanisme keamanan berjalan.

---

# 38. Pencarian, Filter, dan Pengurutan

Karena data dapat mencapai ribuan record, halaman data harus menyediakan pencarian/filter yang relevan.

Contoh filter:

- customer;
- GO;
- PO;
- Batch;
- negara;
- status barang;
- status pembayaran;
- jenis tagihan;
- tanggal;
- produk.

Tidak semua filter harus tampil sekaligus. Filter disesuaikan dengan konteks halaman.

---

# 39. Migrasi Data Spreadsheet

## 39.1 Tujuan

Data lama dapat mencapai 1000+ baris.

Migrasi tidak dilakukan dengan input manual satu per satu.

## 39.2 Sumber Data Awal

Struktur workbook yang telah diberikan mempunyai:

- `STATUS BARANG`
- `TAGIHAN KR`
- `TAGIHAN CH`
- `TAGIHAN JP`

## 39.3 Mapping Awal

### STATUS BARANG

Contoh field sumber:

- Batch
- Item Details
- Keterangan
- Info
- Qty
- WH Code per negara
- Tracking Number
- Status

Target data:

- Batch/PO;
- item;
- keterangan;
- qty;
- negara;
- warehouse;
- tracking;
- status.

### TAGIHAN KR / CH / JP

Contoh field sumber:

- Batch
- Nama
- Items
- Details
- Qty
- Price/Tax
- Fullpay
- DP
- Cicilan
- Kenaikan
- Pelunasan
- Status
- Keterangan

Target data:

- customer;
- negara;
- order;
- item;
- detail;
- qty;
- tagihan;
- pembayaran lama;
- sisa/pelunasan;
- status;
- catatan.

## 39.4 Normalisasi Data

Sebelum import, proses migrasi harus menangani kondisi seperti:

- nama customer yang tidak konsisten;
- baris kosong sebagai pemisah;
- Batch/PO yang hanya ditulis pada baris pertama lalu baris berikutnya mengikuti kelompok sebelumnya;
- field kosong;
- variasi penulisan status;
- data nominal sebagai angka;
- data nominal sebagai teks;
- tracking number berupa angka panjang atau teks;
- kode warehouse berbeda tiap negara;
- duplikasi customer;
- customer yang belum mempunyai akun website.

## 39.5 Customer Legacy

Untuk customer lama:

- data dapat dibuat sebagai record customer legacy;
- order/tagihan tetap dapat disimpan meskipun customer belum mempunyai akun aktif;
- kemudian data dapat dihubungkan ke akun customer jika sudah tersedia.

Metode aktivasi akun legacy ditentukan saat implementasi.

## 39.6 Data Tidak Boleh Hilang

Migrasi harus mempertahankan:

- nominal pembayaran;
- sisa tagihan;
- detail barang;
- Batch/PO;
- status;
- tracking;
- catatan penting.

Data asli sebaiknya tetap disimpan sebagai backup sebelum migrasi.

---

# 40. Notifikasi Email

Notifikasi email merupakan **requirement inti** website.

Email digunakan untuk kebutuhan akun serta informasi transaksi penting.

## 40.1 Email Akun

Sistem mengirim email untuk:

- verifikasi email setelah registrasi;
- kirim ulang verifikasi;
- forgot/reset password;
- konfirmasi perubahan password;
- informasi penting terkait keamanan akun apabila diperlukan.

## 40.2 Email Tagihan dan Pembayaran

Sistem dapat mengirim email kepada customer ketika:

- tagihan baru dibuat;
- terdapat tagihan pelunasan baru;
- bukti pembayaran berhasil dikirim dan menunggu verifikasi;
- pembayaran diterima/valid;
- bukti pembayaran ditolak dan perlu diunggah ulang;
- deadline pembayaran mendekat apabila reminder diterapkan;
- DP melewati deadline dan denda mulai bertambah.

## 40.3 Email Status Barang

Sistem dapat mengirim email ketika terjadi perubahan status penting, termasuk:

- Ordered;
- Arrived WH;
- OTW Indo;
- Arrived Indo;
- Arrived GBU/krjastip;
- Send to Customer;
- Selesai;
- Unclaimed atau peringatan terkait batas claim.

Untuk mencegah email berlebihan, implementasi dapat membatasi pengiriman pada perubahan status yang relevan dan tidak mengirim email berulang untuk status yang sama.

## 40.4 Email untuk Owner

Owner dapat menerima email operasional penting, misalnya:

- terdapat bukti pembayaran baru yang perlu diverifikasi;
- terdapat kondisi yang memerlukan perhatian seperti pembayaran bermasalah atau item memasuki status Unclaimed.

## 40.5 Tampilan Email

Email harus:

- menggunakan identitas GBUKPOP x KRJASTIP;
- mempunyai subjek yang jelas;
- menggunakan bahasa yang mudah dipahami;
- tidak menampilkan detail teknis;
- mempunyai CTA yang langsung menuju halaman website yang relevan jika diperlukan.

---

# 41. Informasi Pembayaran di Website

Owner dapat mengatur informasi pembayaran yang ditampilkan kepada customer, seperti:

- nama bank;
- nomor rekening;
- nama pemilik rekening;
- instruksi singkat.

Informasi ini tidak perlu hardcoded sehingga dapat diperbarui jika rekening berubah.

---

# 42. Data Model Logis

Bagian ini merupakan struktur logis agar seluruh fitur mempunyai hubungan data yang jelas.

## 42.1 User

Menyimpan akun login.

Field logis:

- identitas user;
- role;
- status akun;
- nama;
- email;
- password credential untuk akun email/password;
- waktu email terverifikasi;
- identitas/provider Google apabila akun terhubung Google;
- status aktif/nonaktif;
- waktu pembuatan.

Satu profil customer tidak boleh terduplikasi hanya karena customer menggunakan metode login yang berbeda.

## 42.2 Customer Profile

Menyimpan:

- nama;
- username;
- email;
- WhatsApp;
- LINE;
- informasi kontak lain.

## 42.3 Admin Account

Menyimpan:

- user;
- status aktif;
- level/hak akses;
- waktu dinonaktifkan jika ada.

## 42.4 GO

Menyimpan:

- nama GO;
- status;
- catatan.

## 42.5 Country / Currency

Menyimpan:

- negara;
- kode mata uang;
- status aktif.

## 42.6 Rate

Menyimpan:

- mata uang;
- rate saat ini;
- waktu perubahan.

Order menyimpan snapshot rate masing-masing.

## 42.7 Fee Admin

Menyimpan:

- negara;
- nominal Rupiah;
- status aktif.

## 42.8 Fee Shipping Option

Menyimpan:

- negara;
- label pilihan;
- nominal mata uang asal;
- status aktif.

## 42.9 Warehouse

Menyimpan:

- negara;
- kode warehouse;
- nama/catatan internal;
- status.

## 42.10 Product

Menyimpan:

- tipe produk;
- nama;
- deskripsi;
- negara;
- mata uang;
- foto;
- status.

## 42.11 Product Variant

Menyimpan:

- produk;
- nama variasi;
- nilai variasi;
- harga;
- stok apabila diperlukan;
- status.

## 42.12 PO

Menyimpan:

- produk;
- periode open;
- periode close;
- status;
- pengaturan pembayaran.

## 42.13 Batch

Menyimpan:

- nomor/nama Batch;
- GO;
- negara;
- warehouse internal;
- status;
- tracking.

## 42.14 Order

Menyimpan:

- customer;
- sumber order;
- PO/Batch;
- GO;
- tanggal;
- status.

Sumber order minimal:

- PO
- Batch
- Ready Stock

## 42.15 Order Item

Menyimpan:

- order;
- produk/item;
- detail;
- variasi;
- qty;
- harga;
- rate snapshot;
- keterangan.

## 42.16 Invoice / Tagihan

Menyimpan:

- customer;
- order/order item;
- jenis;
- nominal;
- deadline;
- denda;
- sisa;
- status;
- keterangan.

## 42.17 Payment

Menyimpan:

- customer;
- total pembayaran;
- waktu pembayaran;
- status verifikasi.

Satu Payment dapat terhubung ke beberapa Invoice.

## 42.18 Payment Proof

Menyimpan:

- file bukti;
- waktu upload;
- status;
- catatan penolakan jika ada.

## 42.19 Tracking / Shipment

Menyimpan:

- Batch/PO terkait;
- detail barang;
- keterangan;
- negara;
- tracking number;
- status.

## 42.20 Status History

Menyimpan riwayat perubahan status agar tanggal `Arrived GBU/krjastip` dapat diketahui dengan tepat.

---

# 43. Relasi Data Utama

Relasi konseptual:

```text
Customer
 ├── banyak Order
 │    ├── PO
 │    ├── Batch
 │    └── Ready Stock
 │
 ├── banyak Invoice
 │
 └── banyak Payment

GO
 └── banyak Batch / Order terkait

PO
 └── banyak Order customer

Batch
 └── banyak Order customer

Order
 └── banyak Order Item

Payment
 └── dapat membayar banyak Invoice

Country
 ├── Rate
 ├── Fee Admin
 ├── Fee Shipping
 └── Warehouse
```

---

# 44. Flow Registrasi & Login

## 44.1 Registrasi Email/Password

```text
Customer daftar dengan nama + email + password
→ sistem membuat akun
→ email verifikasi dikirim
→ customer membuka link verifikasi
→ email terverifikasi
→ customer melengkapi profil
→ akun siap digunakan untuk checkout
```

## 44.2 Login dengan Google

```text
Customer pilih Lanjutkan dengan Google
→ Google mengonfirmasi identitas
→ sistem mencari customer berdasarkan identitas/email yang aman
→ jika sudah ada, gunakan profil customer yang sama
→ jika baru, buat akun customer
→ customer melengkapi data bisnis yang belum tersedia
→ akun siap digunakan
```

## 44.3 Forgot Password

```text
Customer pilih Lupa Password
→ input email
→ email reset dikirim
→ customer membuka link
→ menentukan password baru
→ sistem menyimpan password baru
→ email konfirmasi dikirim
→ customer login kembali
```

---

# 45. Flow Utama Customer PO

```text
Customer buka produk
→ pilih varian
→ tambah ke keranjang
→ login jika belum login
→ checkout
→ rate & harga disnapshot
→ tagihan dibuat sesuai aturan produk
→ customer transfer
→ upload bukti
→ Owner verifikasi
→ pembayaran diperbarui
→ customer memantau status/tracking
→ selesai
```

---

# 46. Flow Utama Batch

```text
Barang di-drop melalui GO di luar website
→ customer take melalui GO
→ Owner membuat/memilih Batch
→ Owner input customer + barang + qty + detail
→ order dihubungkan ke customer
→ tagihan dibuat
→ customer melihat tagihan dari akunnya
→ customer transfer & upload bukti
→ Owner verifikasi
→ status Batch diperbarui
→ semua customer pada Batch melihat status terbaru
→ selesai
```

---

# 47. Flow Pembayaran Gabungan

```text
Customer membuka Tagihan Saya
→ memilih beberapa tagihan yang dapat dibayar
→ sistem menghitung total
→ sistem tetap menampilkan rincian tiap tagihan
→ customer transfer satu kali
→ upload satu bukti
→ Owner verifikasi
→ pembayaran dialokasikan ke tagihan terkait
→ status tiap tagihan diperbarui
```

---

# 48. Flow Unclaimed

```text
Status berubah menjadi Arrived GBU/krjastip
→ sistem menyimpan tanggal
→ 3 bulan berlalu
→ belum di-claim
→ status/peringatan Unclaimed
→ Owner meninjau
→ Owner dapat override
→ jika claim ulang diizinkan, Owner membuat tagihan harga barang + tax
```

---

# 49. Flow Kalkulator

```text
Guest/Customer buka Kalkulator
→ pilih negara
→ sistem memuat Rate + Fee Admin
→ input harga barang
→ pilih Fee Shipping
→ input jumlah barengan
→ sistem menghitung
→ tampil estimasi Rupiah
```

---

# 50. Halaman Customer

Halaman yang direkomendasikan:

1. Beranda
2. Shop
3. Detail Produk
4. PO
5. Ready Stock
6. Keranjang
7. Checkout
8. Kalkulator
9. Tracking Publik
10. Login
11. Registrasi
12. Dashboard Saya
13. Order Saya
14. Detail Order
15. Tagihan Saya
16. Detail Tagihan
17. Pembayaran / Upload Bukti
18. Riwayat Pembayaran
19. Profil

---

# 51. Halaman Owner

Halaman yang direkomendasikan:

1. Dashboard Owner
2. Produk
3. Tambah/Edit Produk
4. Variasi Produk
5. PO
6. Tambah/Edit PO
7. Ready Stock
8. GO
9. Batch
10. Tambah/Edit Batch
11. Customer
12. Detail Customer
13. Order
14. Detail Order
15. Tagihan
16. Buat/Edit Tagihan
17. Pembayaran
18. Verifikasi Pembayaran
19. Tracking
20. Status Barang
21. Negara & Mata Uang
22. Rate
23. Fee Shipping
24. Fee Admin
25. Warehouse
26. Admin
27. Import/Migrasi
28. Rekening/Pengaturan Pembayaran
29. Pengaturan Website
30. Email / Notifikasi

---

# 52. Halaman Admin Staff

Minimal:

1. Login Admin
2. Daftar status/order yang dapat dikelola
3. Detail tracking/order
4. Update status
5. Logout

Menu Admin tidak perlu penuh apabila pekerjaannya hanya update status.

---

# 53. Rules Privasi

1. Customer hanya dapat melihat data miliknya.
2. Bukti pembayaran hanya dapat diakses pihak berwenang.
3. Warehouse Code hanya Owner.
4. Halaman tracking publik tidak menampilkan:
   - nama customer;
   - nomor WhatsApp;
   - LINE;
   - email;
   - bukti pembayaran;
   - kode warehouse internal.
5. Admin yang dinonaktifkan tidak boleh dapat mengakses dashboard.
6. Data legacy yang belum terhubung akun tetap tidak boleh terekspos publik.
7. Link verifikasi email dan reset password tidak boleh dapat digunakan untuk mengakses data customer lain.
8. Proses login Google tidak boleh membuat customer duplikat ketika identitas akun yang sama sudah ada.
9. Informasi akun, token autentikasi, dan credential tidak boleh ditampilkan pada halaman publik atau pesan error.

---

# 54. Rules Data dan Validasi

## 53.1 Monetary

Nominal Rupiah harus ditampilkan dengan format manusia.

Contoh:

`Rp125.000`

Bukan:

`125000.00`

## 53.2 Qty

Qty minimal 1 kecuali ada kebutuhan khusus.

## 53.3 Rate

Rate harus bernilai positif.

## 53.4 Jumlah Barengan

Jumlah barengan minimal 1.

## 53.5 Tracking Number

Disimpan sebagai teks agar nomor panjang/karakter awal nol tidak rusak.

## 53.6 Deadline

Deadline disimpan secara konsisten dan ditampilkan dalam format manusia.

## 53.7 Close PO

Checkout baru ditolak setelah PO melewati waktu penutupan.

---

# 55. Search dan Data Besar

Sistem dirancang untuk menangani 1000+ data tanpa menampilkan semua record sekaligus.

Daftar panjang harus menggunakan:

- pagination atau lazy listing;
- search;
- filter;
- sort;
- indikator total data bila berguna.

Halaman tidak boleh menjadi lambat hanya karena data lama telah dimigrasikan.

---

# 56. Media dan File

File yang dapat diunggah:

- foto produk;
- bukti pembayaran.

Requirement:

- preview sebelum/sesudah upload;
- validasi tipe file;
- batas ukuran yang masuk akal;
- file gagal tidak boleh dianggap sukses;
- bukti pembayaran tidak dapat diakses publik.

---

# 57. Empty State

Setiap halaman dengan data kosong harus mempunyai empty state yang jelas.

Contoh:

- Belum ada tagihan.
- Belum ada order.
- Tracking belum tersedia.
- Belum ada pembayaran yang perlu diverifikasi.

Hindari tabel kosong tanpa penjelasan.

---

# 58. Error Handling

Error harus:

- menggunakan bahasa yang mudah dipahami;
- menjelaskan apa yang perlu dilakukan;
- tidak menampilkan stack trace;
- tidak menampilkan error database;
- tidak menampilkan kode teknis.

Contoh:

**Baik:**  
“Bukti pembayaran belum berhasil diunggah. Silakan coba kembali.”

**Tidak sesuai:**  
“SQLSTATE...” atau “500 Internal Server Error”.

---

# 59. Loading State dan Double Submit

Aksi yang menghasilkan perubahan data harus mempunyai state loading.

Contoh:

- checkout;
- upload bukti;
- verifikasi pembayaran;
- membuat Batch;
- menyimpan PO;
- update status.

Tombol harus mencegah klik berulang yang menghasilkan data ganda.

---

# 60. Notifikasi Dalam Website

Minimal sistem dapat menggunakan toast/banner/in-app indicator untuk:

- data berhasil disimpan;
- pembayaran berhasil dikirim untuk verifikasi;
- bukti ditolak;
- PO sudah ditutup;
- deadline terlewati;
- error validasi.

Email berjalan sebagai kanal notifikasi wajib untuk event yang ditentukan pada bagian Notifikasi Email.

---

# 61. Non-Functional Requirements

## 60.1 Responsive

Seluruh flow utama harus dapat diselesaikan melalui smartphone.

## 60.2 Performance

Daftar data besar harus menggunakan pagination/filter.

## 60.3 Reliability

Aksi pembayaran dan checkout tidak boleh menghasilkan duplikasi saat tombol ditekan berulang.

## 60.4 Security

- access control berdasarkan role;
- file bukti pembayaran privat;
- password disimpan secara aman;
- email verification diterapkan pada akun email/password sebelum transaksi;
- forgot/reset password menggunakan alur reset yang aman;
- autentikasi Google hanya menerima identitas dari proses Google Sign-In yang valid;
- admin nonaktif kehilangan akses;
- endpoint Owner tidak dapat dibuka Customer.

## 60.5 Data Integrity

Perubahan:

- rate;
- harga produk;
- fee;
- nama variasi;

tidak boleh mengubah nilai historis order lama jika nilai tersebut sudah menjadi bagian transaksi.

---

# 62. Out of Scope / Bukan Requirement Inti

Berikut tidak termasuk requirement inti versi saat ini:

1. Group chat/GO di dalam website.
2. Keranjang bersama seperti GrabFood/GoFood Group Order.
3. Payment gateway escrow seperti Shopee.
4. Uang ditahan platform sebelum diteruskan ke penjual.
5. Pengecekan mutasi bank otomatis.
6. Sinkronisasi stok otomatis dari seller luar negeri.
7. Scraping website seller untuk mendeteksi sold out.
8. Tracking pengiriman domestik Shopee.
9. Integrasi otomatis Shopee.
11. Auto currency rate dari pasar forex.
12. Warehouse Code untuk customer.

Fitur-fitur tersebut dapat menjadi pengembangan terpisah apabila dibutuhkan.

---

# 63. Prioritas Versi Pertama

## Must Have

- branding GBUKPOP x KRJASTIP;
- responsive UI;
- akun customer;
- login email + password;
- Login dengan Google;
- verifikasi email;
- forgot password;
- reset password melalui email;
- notifikasi email;
- katalog;
- PO;
- Ready Stock;
- Batch manual;
- GO;
- negara/mata uang;
- rate manual;
- Fee Shipping;
- Fee Admin;
- kalkulator;
- keranjang;
- checkout;
- pembayaran fleksibel;
- tagihan;
- denda DP;
- pembayaran transfer;
- upload bukti;
- verifikasi Owner;
- tracking;
- status barang;
- warehouse internal;
- Owner;
- Admin status;
- customer dashboard;
- Unclaimed + override;
- search/filter;
- migrasi spreadsheet jika disepakati dalam scope pengerjaan.

## Optional / Pengembangan Berikutnya

- fitur tambahan lain di luar requirement inti.

---

# 64. Acceptance Criteria Utama

Produk dianggap memenuhi requirement utama apabila:

1. Customer dapat registrasi menggunakan email dan password.
2. Sistem mengirim email verifikasi setelah registrasi email/password.
3. Akun email/password yang belum terverifikasi tidak dapat melakukan checkout.
4. Customer dapat login menggunakan Google.
5. Login Google dengan email customer yang sudah ada tidak membuat customer duplikat.
6. Customer dapat menggunakan fitur Forgot Password dan menerima link reset melalui email.
7. Customer dapat membuat password baru melalui flow reset password yang valid.
8. Sistem mengirim notifikasi email untuk event akun dan transaksi yang ditentukan dalam PRD.
9. Customer dapat menggunakan kalkulator tanpa login.
10. Kalkulator menghasilkan formula yang sesuai aturan bisnis.
11. Owner dapat mengganti rate tanpa mengubah order lama.
12. Owner dapat mengganti Fee Admin dan pilihan Fee Shipping.
13. Customer dapat checkout PO sebelum Close PO.
14. PO otomatis tidak menerima checkout baru setelah Close PO.
15. Owner dapat menutup PO lebih awal.
16. Batch dapat dibuat manual tanpa checkout customer.
17. Owner dapat menghubungkan Batch dengan customer.
18. Satu Batch dapat berisi banyak customer.
19. Perubahan status Batch terlihat pada order terkait.
20. Tagihan customer tetap terpisah meski berada pada Batch sama.
21. Produk dapat mempunyai variasi dengan harga berbeda.
22. Owner dapat mengatur pembayaran berbeda per produk.
23. DP dapat mempunyai deadline.
24. Denda Rp2.000/hari bertambah otomatis setelah deadline.
25. Pelunasan dapat mempunyai deadline tanpa auto-cancel.
26. Customer dapat upload bukti pembayaran.
27. Upload bukti tidak langsung membuat tagihan Lunas.
28. Owner dapat menerima/menolak bukti.
29. Customer dapat upload ulang jika bukti ditolak.
30. Customer dapat melihat sisa tagihan.
31. Customer dapat membayar beberapa tagihan dalam satu transfer apabila dipilih.
32. Pembayaran gabungan tetap mempunyai rincian masing-masing tagihan.
33. Tracking publik menampilkan Keterangan sebelum Negara.
34. Tracking Number boleh kosong.
35. Warehouse Code tidak terlihat customer.
36. Status menggunakan alur yang disepakati.
37. Sistem menyimpan tanggal Arrived GBU/krjastip.
38. Setelah 3 bulan belum claim, sistem menandai Unclaimed.
39. Owner dapat override Unclaimed.
40. Admin dapat dinonaktifkan Owner.
41. Admin nonaktif tidak dapat login kembali.
42. Semua popup/alert menggunakan style website.
43. Semua tanggal user-facing menggunakan format manusia.
44. UI responsive pada desktop dan mobile.
45. Customer tidak melihat informasi teknis/internal.
46. Data lama dapat dimapping ke struktur website apabila migrasi diaktifkan.
47. Data daftar tetap nyaman digunakan pada skala 1000+ record.

---


# 65. Data Migrasi — Checklist Sebelum Import Produksi

Sebelum melakukan migrasi final:

- backup file spreadsheet asli;
- identifikasi semua sheet sumber;
- identifikasi nama customer duplikat;
- tentukan strategi akun customer legacy;
- normalisasi nama GO;
- normalisasi PO dan Batch;
- normalisasi mata uang;
- normalisasi status;
- validasi nominal;
- validasi pembayaran lama;
- validasi sisa pelunasan;
- pastikan tracking number sebagai teks;
- mapping warehouse;
- uji import sebagian;
- bandingkan total data sebelum/sesudah;
- lakukan import penuh setelah hasil sampling valid.

---

# 66. Catatan Data Spreadsheet Saat Ini

Workbook referensi memperlihatkan pola data yang perlu dipertahankan secara fungsional.

## STATUS BARANG

Struktur utama:

```text
Batch
Item Details
Keterangan
Info
Qty
WH Code
Tracking Number
Status
```

Terdapat penggunaan beberapa negara/warehouse pada spreadsheet lama sehingga sistem baru harus menggunakan model warehouse yang fleksibel, bukan kolom negara yang kaku.

## TAGIHAN

Struktur utama:

```text
Batch
Nama
Items
Details
Qty
Price/Tax
Full Payment
DP
Cicilan
Kenaikan
Pelunasan
Status
Keterangan
```

Sistem baru harus mempertahankan kemampuan pencatatan tersebut tetapi memecahnya menjadi data order, tagihan, dan pembayaran yang lebih terstruktur.

---

# 67. Keputusan Produk yang Sudah Dikunci

1. Nama web: **GBUKPOP x KRJASTIP**.
2. PO dan Batch berbeda.
3. PO masuk otomatis dari checkout customer.
4. Batch dimasukkan manual Owner.
5. GO tetap berada di luar website.
6. Customer login untuk checkout.
7. Customer dapat menggunakan email + password atau Login dengan Google.
8. Akun email/password wajib melakukan verifikasi email sebelum checkout.
9. Forgot Password dan Reset Password melalui email merupakan fitur wajib.
10. Notifikasi Email merupakan requirement inti.
11. Kalkulator tidak perlu login.
12. Rate manual Owner.
13. Rate lama terkunci pada order lama.
14. Mata uang awal: KRW, JPY, CNY, USD, PHP, THB.
15. Fee Shipping menggunakan mata uang asal.
16. Fee Admin menggunakan Rupiah.
17. Fee Shipping dan Fee Admin dibagi berdasarkan jumlah barengan.
18. Rumus kalkulator mengikuti formula PRD.
19. Skema pembayaran fleksibel per produk.
20. DP dapat didenda Rp2.000/hari.
21. Denda masuk otomatis ke tagihan.
22. Pelunasan mempunyai deadline reminder.
23. Transfer langsung ke rekening Owner.
24. Bukti pembayaran diverifikasi manual.
25. Customer tidak dapat cancel sendiri dari website.
26. Status barang:
    - Ordered
    - Arrived WH
    - OTW Indo
    - Arrived Indo
    - Arrived GBU/krjastip
    - Send to Customer
    - Selesai
27. Unclaimed setelah 3 bulan dari Arrived GBU/krjastip.
28. Owner dapat override Unclaimed.
29. Tracking publik mempunyai Keterangan sebelum Negara.
30. Warehouse Code hanya Owner.
31. Customer dapat mempunyai beberapa GO.
32. Customer dapat mempunyai beberapa PO/Batch.
33. Beberapa tagihan dapat dibayar bersama tetapi rincian tidak digabung secara permanen.
34. Migrasi 1000+ data diinginkan apabila memungkinkan.
35. Semua tanggal user-facing menggunakan format manusia.
36. Semua popup/alert menggunakan style website.
37. Desain responsive.
38. Desain menggabungkan identitas GBU dan KRJASTIP tanpa terlihat generik/AI-generated.

---


# 68. Roadmap Pengembangan yang Direkomendasikan

Urutan implementasi yang paling aman:

### Fase 1 — Foundation & Authentication
- authentication email/password;
- Login dengan Google;
- verifikasi email;
- forgot/reset password;
- email delivery untuk kebutuhan akun;
- role;
- customer;
- negara/rate/fee;
- warehouse;
- GO.

### Fase 2 — Commerce
- produk;
- variasi;
- PO;
- Ready Stock;
- keranjang;
- checkout.

### Fase 3 — Batch & Legacy Workflow
- Batch manual;
- order manual;
- mapping customer;
- status.

### Fase 4 — Billing
- tagihan;
- DP;
- cicilan;
- full payment;
- pelunasan;
- deadline;
- denda;
- gabung pembayaran.

### Fase 5 — Payment
- rekening;
- upload bukti;
- verifikasi;
- reject/resubmit.

### Fase 6 — Tracking
- tracking publik;
- Keterangan;
- status;
- status history;
- Unclaimed;
- override.

### Fase 7 — Migration
- data cleaning;
- import;
- validation;
- reconciliation.

### Fase 8 — Email Transactional & Finalization
- template email transaksi;
- notifikasi tagihan/pembayaran;
- notifikasi perubahan status;
- reminder yang ditentukan;
- final QA notifikasi.

---

# 69. Batasan Scope dan Change Request

PRD ini menjadi baseline requirement.

Fitur baru setelah baseline dikunci harus dinilai sebagai perubahan scope apabila:

- menambah integrasi eksternal;
- menambah payment gateway;
- menambah otomatisasi seller;
- menambah sistem shipping domestik;
- mengubah alur bisnis PO/Batch secara besar;
- menambah role baru dengan permission kompleks;
- menambah fitur yang tidak tercantum di PRD.

Perubahan kecil pada teks, label, urutan UI, dan detail visual tetap dapat dilakukan selama tidak mengubah logika utama.

---

# 70. Penutup

GBUKPOP x KRJASTIP dirancang sebagai evolusi dari workflow spreadsheet menjadi satu website terpusat tanpa menghilangkan fleksibilitas operasional yang sudah digunakan.

Tiga fondasi utamanya adalah:

1. **Order Management** — PO otomatis dari website dan Batch manual dari GO.
2. **Billing & Payment** — tagihan fleksibel, DP/cicilan/pelunasan, denda, pembayaran gabungan, dan verifikasi transfer.
3. **Tracking & Customer Self-Service** — customer dapat memeriksa barang, tagihan, pembayaran, dan status tanpa harus bertanya satu per satu.

Sistem harus terasa sederhana bagi customer, fleksibel bagi Owner, dan tetap mampu menangani data lama dalam jumlah besar.

---

**End of PRD — GBUKPOP x KRJASTIP v1.1**
---

## Amendment v1.0.15 — Kalkulator Batch, Status Tax, dan Tagihan Tambahan Batch

1. Kalkulator publik berorientasi utama pada Batch dan menampilkan `Rate hari ini` serta `Fee per barang`.
2. `Fee per barang = ((Fee Shipping × Rate) + Fee Admin) ÷ Jumlah Barengan`.
3. `Estimasi Total = (Harga Barang × Rate) + Fee per Barang`.
4. Kalkulator wajib menegaskan bahwa shipping masih estimasi dan harga barang merupakan harga bersih negara asal yang belum termasuk tax/pajak.
5. Produk mempunyai status tax eksplisit: `Belum termasuk tax` atau `Sudah termasuk estimasi tax`.
6. Owner dapat membuat Tagihan Tambahan dari Batch tanpa input customer ulang.
7. Jenis Tagihan Tambahan: Tax/Pajak, Shipping Aktual, Berat, Rate, Berat+Rate, dan Lainnya.
8. Mode nominal bulk: per barang × Qty, per customer, atau berbeda per customer.
9. Tagihan utama tidak ditimpa; Tagihan Tambahan dibuat sebagai invoice terpisah dan tetap dapat dibayar bersama invoice lain.
10. Pembuatan Tagihan Tambahan Batch hanya tersedia untuk Owner, bukan Admin.
