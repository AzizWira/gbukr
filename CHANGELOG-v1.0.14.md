# Changelog v1.0.14

- Memperbaiki layout detail produk agar gambar/placeholder tidak over di desktop maupun mobile.
- Menambahkan indikator `*` otomatis pada field wajib.
- Mengganti validasi browser default dengan validasi styled GBUKR + inline error + toast.
- Menambahkan `currency_symbol` pada master negara/mata uang dan membuatnya dapat diubah Owner.
- Seeder mata uang dilengkapi simbol default: ₩, ¥, $, ₱, ฿, Rp, RM, NT$, S$.
- Kalkulator menampilkan simbol mata uang langsung pada harga barang dan opsi shipping.
- Kalkulator menampilkan `Rate` + `Fee per barang` serta catatan estimasi shipping/tax sesuai requirement.
- Free Shipping tetap didukung sebagai nominal shipping 0.
- Tampilan harga mata uang asal pada katalog/detail/Owner menggunakan simbol yang dikonfigurasi.
- Backup lengkap menambahkan sheet `MATA UANG` dan menyimpan simbol mata uang.
- Form produk menyelaraskan required state PO/Ready Stock secara dinamis.
