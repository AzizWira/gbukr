# GBUKPOP x KRJASTIP v1.0.26

## Perbaikan

- Memperbaiki HTTP 500 ketika Owner mencoba menghapus Customer yang masih direferensikan histori Invoice/Order/Payment yang sudah soft-deleted.
- Pemeriksaan aman sebelum hard delete Customer sekarang menghitung histori aktif **dan** histori yang sudah dihapus dari tampilan (`withTrashed`).
- Tombol **Hapus customer** hanya ditampilkan jika customer benar-benar tidak mempunyai histori Order, Tagihan, maupun Pembayaran dan tidak mempunyai akses Admin.
- Detail Customer menampilkan jumlah histori tersimpan yang membuat akun harus dipertahankan.
- Menambahkan fallback penanganan foreign-key constraint agar kasus relasi yang terlewat menghasilkan pesan UX yang aman, bukan halaman 500.
- Menambahkan regression test untuk customer kosong, customer dengan tagihan aktif, dan customer dengan tagihan soft-deleted.

## Database

Tidak ada migration baru.
