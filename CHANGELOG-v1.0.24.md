# v1.0.24

- Mengubah lifecycle penghapusan Order menjadi berbasis kondisi akun dan pembayaran.
- Order `Belum terhubung akun` dapat dibersihkan langsung; histori finansial tetap disimpan sebagai audit internal melalui soft delete.
- Order terhubung akun tanpa pembayaran dapat dihapus langsung dan customer menerima notifikasi.
- Order terhubung akun dengan pembayaran pending/rejected/approved atau riwayat pembayaran legacy wajib melalui persetujuan customer.
- Menambahkan halaman customer untuk Setujui/Tolak permintaan penghapusan dan indikator permintaan pada dashboard customer.
- Menambahkan audit `order_deletion_requests` berisi alasan dan snapshot Order/Invoice/Payment sebelum penghapusan.
- Menambahkan soft delete pada Order, Invoice, dan Payment agar transaksi yang pernah mempunyai histori finansial hilang dari operasional tanpa menghilangkan jejak audit.
- Cleanup import kini mempunyai tahap review sebelum eksekusi. Data yang sudah berubah tidak otomatis diblokir.
- Data hasil import yang belum terhubung akun tetapi pernah diedit (termasuk perubahan nama customer) masuk Review Manual dan tetap dapat dipilih untuk cleanup.
- Popup Cleanup Import dibuat besar, scrollable, sticky header/footer, mobile-safe, serta memiliki search dan filter.
- Data review harus dipilih satu per satu; tidak tersedia Select All untuk kelompok berisiko.
- Cleanup menjalankan tindakan berbeda per Order: hapus langsung, hapus + notifikasi customer, atau kirim permintaan approval.
- Menambahkan baseline snapshot import untuk import baru agar perubahan setelah import dapat dideteksi lebih akurat.
- Data yang sudah dibersihkan sebelumnya dilewati dan dihitung dari audit, sehingga cleanup dapat dijalankan ulang secara aman.
- Batch cleanup mengikuti aturan approval baru dan tidak lagi melepas Order berhistori pembayaran diam-diam.
- Memerlukan `php artisan migrate`.
