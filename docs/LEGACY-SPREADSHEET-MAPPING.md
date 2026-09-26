# Mapping Spreadsheet Legacy — GBUKR

Dokumen ini mencatat keputusan mapping berdasarkan workbook operasional lama yang diberikan Owner.

## Unit workbook

Untuk migrasi, **satu workbook diperlakukan sebagai satu sumber GO**. Nama file seperti CORTIS, SEVENTEEN, ENHYPEN, TWICE, atau KRJASTIP menunjukkan bahwa rekap lama memang sering dipisahkan berdasarkan GO.

`GO` tidak otomatis disamakan dengan `Event`. Sistem belum membuat entitas Event karena aturan bisnis Event belum didefinisikan. Jika di kemudian hari Owner membedakan GO dan Event, Event dapat ditambahkan tanpa mengubah makna data GO yang telah dimigrasikan.

## Sheet yang dipetakan

### STATUS BARANG

Pola utama yang ditemukan:

- Batch / PO
- Item Details
- Keterangan
- Info
- Qty
- penanda warehouse/negara
- Tracking Number
- Status

Importer mempertahankan detail tracking bersama dan mengelompokkannya berdasarkan sumber Batch/PO.

### TAGIHAN per negara

Nama yang didukung:

- TAGIHAN KR
- TAGIHAN CH
- TAGIHAN JP / JPN
- TAGIHAN THAI
- TAGIHAN PH
- TAGIHAN MY
- TAGIHAN SG
- TAGIHAN TW
- TAGIHAN USA
- TAGIHAN INA
- TAGIHAN PO

Ditemukan dua pola besar.

#### Pola pembayaran

Kolom seperti:

- Price / Price-Tax
- Fullpay
- DP
- Cicilan
- Kenaikan
- Pelunasan
- Status
- Ket

`Kenaikan` dipetakan ke tagihan **Kekurangan** terpisah agar harga/tagihan awal tidak ditimpa.

#### Pola shipping/tax

Beberapa workbook lama memakai kolom seperti:

- Air Cargo
- EMS
- DHL
- Tax
- Total
- Status

Importer mendeteksi format ini terpisah agar kolom shipping tidak salah dibaca sebagai Full Payment/DP/Cicilan.

## Sheet yang belum dipaksa masuk database

Beberapa workbook mempunyai sheet tambahan seperti:

- FREEBIES
- HANDCARRY 1, 2, ...
- WHEREHOUSE
- BATCH CHINA

Sheet tersebut tetap muncul pada Preview, tetapi belum dimigrasikan otomatis karena aturan bisnis dan hubungan datanya belum cukup jelas. Sistem **tidak menebak** maknanya untuk menghindari data rancu.

Workbook asli yang sudah dikirim untuk import tetap disimpan sebagai arsip dan dapat diunduh kembali oleh Owner dari menu Migrasi. Dengan begitu data pada sheet yang belum dipetakan tidak hilang.

## Customer legacy

Nama customer pada workbook lama tidak dianggap sebagai identitas global yang aman. Placeholder customer dibuat dengan scope per GO. Hal ini mencegah dua customer dengan display name sama dari GO berbeda tergabung tanpa sengaja.

Owner dapat melakukan rekonsiliasi/merge dengan akun customer yang sebenarnya setelah data diverifikasi.

## Import besar

Import dijalankan melalui database queue, bukan di request browser. Tujuannya:

- menghindari batas eksekusi HTTP 30 detik;
- menampilkan progress;
- mencegah browser menganggap proses gagal ketika data masih diproses;
- menghindari hash password berulang untuk customer placeholder, karena akun legacy memakai password `null` sampai akun direkonsiliasi.
