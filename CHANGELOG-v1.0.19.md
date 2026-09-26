# v1.0.19

- Import legacy tanpa kode Batch tidak lagi gagal; fallback Batch dibuat otomatis per workbook dan negara sehingga STATUS BARANG dan TAGIHAN terkait tetap dapat tersambung.
- Search Batch/Tracking/Order/Tagihan/Pembayaran/Customer/Produk diperluas dan identifier dibuat lebih toleran terhadap kapital serta tanda pemisah.
- Filter utama memiliki tombol Clear.
- Pagination sekarang mendukung input nomor halaman untuk lompat langsung ke halaman tertentu.
- Batch dapat diedit; Batch kosong dapat dihapus, sedangkan Batch yang sudah memiliki order dipertahankan.
- Customer dapat diedit dan diaktifkan/nonaktifkan.
- Tracking manual dapat diedit/dihapus; tracking Batch tetap dikelola lewat Batch.
- Tagihan sebelum proses pembayaran dapat diedit atau dibatalkan tanpa menghapus histori.
- Master data Rate/Negara, Shipping, Warehouse, GO, rekening, dan status dapat diedit serta menggunakan aktif/nonaktif sesuai jenis data.
- Upload workbook migrasi dibatasi 5 MB.
- Gambar produk dan bukti transfer gambar di-resize/dikompres ke WebP saat server mendukung GD/WebP; PDF dan XLSX dipertahankan apa adanya.
- README dirapikan menjadi dokumentasi sistem aplikasi dan menjelaskan pola kode Batch manual/legacy, siklus hidup data, pencarian, pagination, serta kebijakan file.
- Ditambahkan regression test untuk import tanpa Batch, pencarian kode Batch legacy, jump page/Clear, dan lifecycle master data.
