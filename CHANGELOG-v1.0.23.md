# v1.0.23

- Memperbaiki regression test cleanup Order: pivot Invoice–Payment menggunakan tabel canonical `invoice_payment`, sesuai migration produksi.
- Relasi `Invoice::payments()` dan `Payment::invoices()` sekarang menyebut tabel pivot `invoice_payment` secara eksplisit agar konsisten lintas database/Laravel.
- Pagination sekarang tetap tampil pada daftar kosong, sehingga pilihan 20/50/100 data per halaman dan input lompat halaman tidak menghilang saat hasil filter belum memiliki data.
- State pagination kosong menampilkan “Tidak ada data” dengan tombol Sebelumnya/Berikutnya nonaktif dan halaman tetap 1 dari 1.
- Tidak ada perubahan schema database dan tidak memerlukan migration baru.
