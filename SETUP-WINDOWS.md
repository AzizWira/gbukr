# Setup Windows — GBUKPOP x KRJASTIP v1.0.13

## Prasyarat

- PHP 8.3+
- Composer 2.x
- MySQL/MariaDB
- Extension PHP yang diperlukan PhpSpreadsheet, terutama `zip`, `gd`, `mbstring`, `xml`, `xmlreader`, `xmlwriter`, dan `fileinfo`

## Instalasi pertama

```powershell
cp .env.example .env
composer update --prefer-dist
php artisan key:generate
```

Jika Composer mengalami DNS/IPv6 timeout:

```powershell
$env:COMPOSER_IPRESOLVE="4"
composer update --prefer-dist --no-interaction
```

Setelah `composer.lock` tersedia, instalasi berikutnya gunakan:

```powershell
composer install
```

## `.env`

```env
OWNER_NAME="Owner GBUKPOP x KRJASTIP"
OWNER_EMAIL="owner@example.com"
OWNER_PASSWORD="password-minimal-12-karakter"

QUEUE_CONNECTION=database
QUEUE_RETRY_AFTER=1200
```

Demo:

```env
SEED_DEMO_DATA=true
DEMO_PASSWORD="DemoGBUKR2026!"
```

Production:

```env
SEED_DEMO_DATA=false
```

## Database

Fresh setup:

```powershell
php artisan migrate --seed
php artisan storage:link
```

Upgrade ke v1.0.8:

```powershell
php artisan optimize:clear
php artisan migrate
php artisan queue:restart
```

Migration v1.0.8 menambahkan data Kekurangan, riwayat import, dan Qty tracking untuk mempertahankan informasi dari spreadsheet lama.

## Menjalankan lokal

Terminal 1:

```powershell
php artisan serve
```

Terminal 2:

```powershell
php artisan schedule:work
```

Terminal 3:

```powershell
php artisan queue:work --timeout=900 --tries=1
```

Jangan memakai worker dengan timeout 60/90 detik untuk workbook besar. Import sengaja memiliki timeout sampai 900 detik.

## Jika import tidak bergerak

Cek apakah worker hidup:

```powershell
php artisan queue:work --timeout=900 --tries=1
```

Cek job gagal:

```powershell
php artisan queue:failed
```

Retry job yang memang aman untuk dicoba ulang:

```powershell
php artisan queue:retry all
```

Lihat log:

```powershell
Get-Content .\storage\logs\laravel.log -Tail 150
```

## Extension spreadsheet

```powershell
php -m | findstr /I "zip gd mbstring xml xmlreader xmlwriter fileinfo"
```

## Testing

```powershell
php artisan test
```
