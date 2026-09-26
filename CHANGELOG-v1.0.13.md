# GBUKPOP x KRJASTIP v1.0.13

## Email
- Seluruh email memakai template branded GBUKR dengan logo GBUKPOP + KRJASTIP yang di-embed ke email.
- Template berlaku untuk verifikasi email, reset password, password berhasil diubah, tagihan, deadline, status order, bukti pembayaran, hasil verifikasi pembayaran, dan Unclaimed.

## Authentication security
- Link verifikasi hanya memberi efek satu kali; penggunaan ulang setelah akun terverifikasi ditolak dengan pesan yang jelas.
- Link reset password divalidasi sebelum form ditampilkan dan token tidak dapat dipakai kembali setelah reset berhasil.
- Form reset password tidak lagi mengirim `email` atau `token` dari browser.
- Backend mengambil user + token dari context session hasil validasi link, sehingga manipulasi email/token lewat Inspect Element diabaikan.
- Endpoint reset dan verifikasi diberi throttling tambahan.
- Pesan validasi password menggunakan bahasa manusia, termasuk syarat minimal satu angka/huruf.

## Produk / PO
- Produk memiliki `fee per barang`, Free Shipping, EMS Tax, Apply Fansign, lokasi, dan tanggal informasi/event.
- Variasi memiliki sumber website, detail versi, estimasi berat, pricelist, dan DP per variasi.
- Owner dapat mengedit detail variasi setelah dibuat.
- Detail publik mengelompokkan variasi berdasarkan website sumber.
- Detail publik menampilkan rate aktif + fee per barang dan disclaimer shipping/tax.
- Checkout memakai DP per variasi jika tersedia.
- Harga foreign pada checkout dihitung dengan rate aktif + fee per barang sebelum rate disnapshot.

## Shipping
- Opsi shipping bernilai 0 sekarang valid sehingga Owner dapat membuat opsi `Free Shipping`.
- Seeder demo menambahkan opsi Free Shipping Korea.

## Seeder / backup
- Seeder demo CORTIS dan ENHYPEN diperluas menjadi contoh PO yang realistis dengan sumber website, berat, pricelist, dan DP.
- Backup lengkap sheet PRODUK memuat metadata baru agar tidak hilang saat Owner mengunduh backup.
