# GBUKPOP x KRJASTIP v1.0.20

## Data lifecycle & conditional delete
- Master data yang belum pernah dipakai dapat dihapus permanen dengan konfirmasi styled yang menjelaskan dampaknya.
- Data yang sudah memiliki relasi histori diblokir dari hard delete di backend dan diarahkan menggunakan Nonaktifkan/Batal.
- Conditional delete mencakup negara/rate, shipping, warehouse, GO, rekening, status custom, produk, variasi, customer, Batch kosong, dan tracking manual sesuai aturan relasinya.
- Produk dan variasi sekarang memiliki aksi Aktif/Nonaktif terpisah dari Hapus permanen.
- Customer tanpa transaksi dan tanpa akses Admin dapat dihapus permanen; customer berhistori hanya dapat dinonaktifkan.

## Import GO
- Preview import memakai pola Post/Redirect/Get sehingga error validasi tidak kembali ke route POST.
- Import wajib memilih tepat satu tujuan: GO lama atau GO baru.
- Jika keduanya kosong atau keduanya terisi, import tidak dikirim dan dialog styled memberi instruksi yang jelas.
- Backend tetap memvalidasi aturan yang sama walau JavaScript dinonaktifkan/manipulasi request dilakukan.

## Kode Batch baru
- Batch yang dibuat langsung dari web memakai format `{NEGARA}-{GO/CONTEXT}-{URUTAN}`.
- Contoh: `KR-ENHYPEN-001`, `US-KRJASTIP-002`.
- Nama GO diprioritaskan sebagai context; jika Batch tidak memiliki GO, nama Batch digunakan sebagai fallback.
- Kode legacy hasil import tidak diubah.

## QA
- Regression test diperbarui untuk format kode Batch baru dan alur preview import PRG.
- Test baru untuk GO import wajib/eksklusif serta conditional delete master data.
