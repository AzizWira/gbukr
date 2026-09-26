# v1.0.18

Hotfix QA:

- Memperbaiki validasi Tagihan Tambahan jenis Berat dan Berat + Rate. Berat aktual yang lebih kecil dari berat estimasi sekarang ditolak dengan pesan validasi yang jelas.
- Menyamakan rule validasi berat pada Tagihan Tambahan single order dan bulk Batch.
- Memperbaiki struktur Blade di halaman detail Batch yang menyebabkan ParseError pada blok daftar Tagihan Tambahan.
- Blok daftar Tagihan Tambahan ditulis ulang dengan directive Blade multiline yang aman.
