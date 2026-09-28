# v1.0.25

## Cleanup Import

- Menambahkan tombol **Pilih semua** pada popup **Tinjau Data Sebelum Cleanup**.
- Tombol memilih seluruh data yang membutuhkan review manual, termasuk data yang sedang tersembunyi oleh filter/search.
- Jika seluruh data sudah terpilih, tombol berubah menjadi **Batalkan pilih semua**.
- Counter pilihan menampilkan `dipilih / total` agar Owner mengetahui jumlah data yang akan diproses.
- Memilih semua tidak mengubah aturan keamanan: setiap Order tetap diproses berdasarkan kondisi aktualnya (hapus langsung, notifikasi customer, atau permintaan approval).
- Modal tetap scrollable dan header/footer tetap berada di dalam batas viewport.
