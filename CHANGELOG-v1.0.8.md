# Changelog v1.0.8

## Import spreadsheet

- Import besar dipindahkan ke background job database queue dengan timeout worker sampai 900 detik.
- Tidak lagi membuat password acak untuk ribuan customer placeholder; password legacy disimpan `null` sampai rekonsiliasi akun.
- Satu workbook wajib ditautkan ke satu GO agar data workbook berbeda tidak bercampur.
- Customer legacy diberi scope berdasarkan GO + nama normalized.
- Dukungan TAGIHAN diperluas: KR, CH, JP/JPN, THAI, PH, MY, SG, TW, USA, INA, dan PO.
- Deteksi otomatis dua format tagihan lama: payment-stage dan shipping/tax.
- Kolom Kenaikan lama dipetakan sebagai tagihan `kekurangan` terpisah.
- STATUS BARANG menyimpan Qty dan mapping tracking/status lebih lengkap.
- Progress import dapat dipantau dari dashboard Migrasi.
- Source workbook dipertahankan sebagai arsip dan dapat didownload ulang.
- Proteksi duplicate queue untuk file upload yang sama.
- Failure/timeout queue dicatat ke ImportRun dan source file tidak dihapus.
- Partial import dari v1.0.7 yang sempat timeout dideteksi/adopsi berdasarkan fingerprint lama agar rerun tidak menggandakan order/invoice yang sudah sempat tersimpan.
- Import hanya memuat sheet yang dikenali saat proses background untuk mengurangi memori/waktu pada workbook dengan banyak HANDCARRY/FREEBIES.

## Kekurangan / penyesuaian harga

- Owner dapat menambahkan tagihan Kekurangan pada order.
- Mendukung alasan perubahan berat, rate, berat + rate, tax/biaya tambahan, atau alasan lain.
- Dapat menyimpan berat estimasi/aktual serta rate awal/akhir.
- Customer melihat Kekurangan dalam section terpisah tetapi tetap dapat membayarnya bersama invoice lain.
- Demo seeder memiliki contoh kenaikan dari 500 g ke 700 g dan perubahan rate.

## Export & backup

- Export workbook `.xlsx` per GO.
- Sheet per-GO: RINGKASAN, STATUS BARANG, TAGIHAN per negara, KEKURANGAN, PEMBAYARAN, ARSIP IMPORT.
- Backup lengkap `.xlsx` untuk data utama website.
- Backup lengkap mencakup metadata bukti pembayaran dan riwayat file import.
- Workbook sumber migrasi dapat didownload ulang dari Riwayat Import.

## Form safety & UX

- Semua mutation form mendapat loading toast di kanan atas dan tombol submit dinonaktifkan ketika sedang diproses.
- Guard double-submit diterapkan global.
- Logout menggunakan custom confirmation dialog.
- DELETE tetap dipaksa memakai custom confirmation walau view lupa memberi atribut konfirmasi.
- Navigasi internal saat form berubah menampilkan custom unsaved-changes dialog.
- Refresh/tutup tab memakai browser beforeunload safeguard karena browser tidak mengizinkan custom modal untuk event tersebut.
- Form lain yang belum disimpan juga diperingatkan ketika user menyubmit form berbeda pada halaman multi-form.

## Spreadsheet findings

- File legacy yang berbeda diperlakukan sebagai GO berbeda, bukan otomatis sebagai Event.
- FREEBIES/HANDCARRY dan sheet khusus lain tidak dipaksa masuk database sampai aturan bisnisnya didefinisikan.
