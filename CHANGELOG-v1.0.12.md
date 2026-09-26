# GBUKPOP x KRJASTIP v1.0.12

- Email verifikasi akun sekarang dikirim langsung dan tidak bergantung pada queue worker.
- Email link reset password sekarang dikirim langsung dan tidak bergantung pada queue worker.
- Notifikasi transaksi/non-kritis tetap dapat menggunakan database queue.
- Registrasi tetap berhasil bila provider email sementara gagal; user diarahkan ke halaman verifikasi dan dapat mencoba Kirim Ulang.
- Kirim ulang verifikasi dan forgot password menangani kegagalan mail dengan pesan UI, bukan HTTP 500.
- Menambahkan automated test untuk notification verifikasi registrasi dan reset password.
