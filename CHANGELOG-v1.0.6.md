# GBUKPOP x KRJASTIP v1.0.6

## Perbaikan utama

- Memperbaiki ParseError pada halaman checkout dengan menulis ulang Blade checkout secara aman.
- Flash success/error sekarang tampil sebagai toast mengambang dan hilang otomatis sehingga tidak mendorong layout.
- Menambahkan proteksi double-submit pada checkout.
- Admin sekarang merupakan akses tambahan pada akun member/customer, bukan akun yang kehilangan kemampuan customer.
- Member yang diberi akses Admin akan memilih mode `Customer` atau `Admin` setiap kali login baru.
- Mode dapat diganti kembali dari navbar tanpa logout.
- Mode Admin tidak dapat memakai aksi customer seperti cart/checkout sampai beralih ke mode Customer.
- Tombol pembelian tidak ditampilkan untuk Owner dan tidak aktif pada mode Admin.
- Route Owner tetap hanya dapat diakses Owner; Admin hanya mendapat modul operasional yang memang diizinkan.
- Owner dapat memberikan/mencabut akses Admin dari member yang sudah ada.
- Menambahkan preview bukti transfer (gambar/PDF) di halaman verifikasi Owner, tetap menyediakan download.
- Menambahkan preview file bukti transfer sebelum customer mengirim pembayaran.
- Login, registrasi, dan forgot-password diberi rate limiting.
- Route Owner/Admin sekarang juga mewajibkan email terverifikasi.
- Notifikasi transaksi penting diubah menjadi queued dan kegagalan email tidak membatalkan transaksi bisnis yang sudah berhasil disimpan.
- Memperbaiki kontrol mode setelah akses Admin dicabut agar akun customer tetap dapat digunakan dengan aman.

## Data & migrasi

Migration baru `2026_09_25_000500_add_admin_capability_to_users.php` menambahkan `users.admin_enabled`.
Akun lama dengan `role=admin` otomatis dikonversi menjadi:

- `role=customer`
- `admin_enabled=true`

Dengan demikian satu akun dapat berfungsi sebagai customer sekaligus Admin tanpa membuat dua akun terpisah.

## QA tambahan

Ditambahkan test untuk:

- pilihan mode Customer/Admin;
- pembatasan Owner-only untuk Admin;
- promosi member menjadi Admin tanpa mengubah role customer;
- render halaman checkout;
- pembuatan order/invoice dari checkout;
- pengurangan stok Ready Stock;
- authorization customer terhadap area Owner.
