# GBUKPOP x KRJASTIP v1.0.9

Hotfix berdasarkan hasil `php artisan test` v1.0.8.

## Fixed

- Flow pembuatan tagihan **Kekurangan** sekarang selalu redirect ke detail order setelah berhasil.
- Sanitasi nama worksheet export XLSX diperbaiki agar karakter terlarang Excel tidak memicu `preg_replace(): Unknown modifier '?'`.
- Model `User` sekarang memakai Laravel `HasFactory`.
- Ditambahkan `database/factories/UserFactory.php` agar feature tests dan pengembangan lokal dapat memakai `User::factory()`.

Tidak ada perubahan database/migration pada versi ini.
