# GBUKPOP x KRJASTIP v1.0.15

## Calculator Batch
- `Fee per barang` sekarang dihitung dari `((Fee Shipping × Rate) + Fee Admin) ÷ Jumlah Barengan`.
- Estimasi total menjadi `(Harga Barang × Rate) + Fee per Barang`.
- Detail hasil disederhanakan menjadi Rate hari ini + Fee per barang.
- Penegasan bahwa shipping masih estimasi dan harga barang merupakan harga bersih negara asal yang belum termasuk tax/pajak.
- Free Shipping tetap didukung.

## Status Tax Produk
- Menambah status tax eksplisit pada produk: `Belum termasuk tax` atau `Sudah termasuk estimasi tax`.
- Field lama `EMS Tax` dipertahankan secara internal untuk kompatibilitas, tetapi UI Owner menggunakan Status Tax yang lebih jelas.
- Detail produk customer menampilkan status tax dan konsekuensi penyesuaian biaya aktual.
- Export backup produk ikut membawa Status Tax.

## Bulk Tagihan Tambahan dari Batch
- Owner dapat membuat Tagihan Tambahan langsung dari detail Batch tanpa mengetik customer ulang.
- Jenis: Tax/Pajak, Shipping Aktual, Berat, Rate, Berat+Rate, dan Lainnya.
- Mode perhitungan: nominal per barang × Qty, nominal per customer, atau nominal berbeda tiap customer.
- Owner dapat memilih customer/order Batch yang menerima tagihan.
- Tagihan awal tidak diubah; sistem membuat invoice `kekurangan` terpisah agar histori tetap jelas.
- Backend menolak order yang bukan bagian dari Batch walaupun request dimanipulasi.
- Customer melihat invoice tersebut pada section `Tagihan Tambahan` dan tetap bisa membayarnya bersama invoice lain.

## Demo & QA
- Seeder menambahkan contoh Batch dengan dua customer dan Tax tambahan terpisah setelah tagihan awal lunas.
- Menambah test kalkulator formula baru, status tax produk, dan bulk Tagihan Tambahan Batch.
