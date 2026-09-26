# GBUKPOP x KRJASTIP v1.0.10

## Fixed
- Processing toast `Memproses…` tidak lagi muncul terus pada setiap halaman.
- Atribut HTML `[hidden]` sekarang selalu dihormati oleh stylesheet aplikasi.
- Processing toast disembunyikan eksplisit saat page load dan diberi state ARIA yang sesuai.
- State loading form di-reset ketika halaman dipulihkan dari browser back/forward cache.
- Tombol submit yang sempat disable karena submit/download tidak lagi nyangkut setelah kembali ke halaman.
- Perbaikan berlaku global untuk tambah/edit data, login, checkout, export/backup, migrasi, kalkulator, dan preview yang memakai atribut hidden.
