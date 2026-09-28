# GBUKPOP x KRJASTIP v1.0.27

## Perbaikan

- Memperbaiki rule hapus Customer yang terlalu ketat pada v1.0.26.
- Customer sekarang dapat dihapus permanen apabila tidak mempunyai Order/Tagihan aktif dan tidak mempunyai histori finansial, walaupun masih ada Order/Tagihan yang sebelumnya sudah soft-delete.
- Saat hard delete aman dilakukan, residu Order/Tagihan soft-delete ikut dipurge agar foreign key tidak lagi mengunci akun Customer.
- Payment aktif maupun soft-delete tetap dianggap histori finansial dan tetap melindungi Customer dari hard delete.
- Invoice dengan `paid_amount > 0` atau status `paid/partial` tetap dianggap histori finansial walaupun tidak mempunyai record Payment terpisah.
- Halaman detail Customer sekarang membedakan data aktif, histori finansial, dan residu soft-delete yang aman dibersihkan.
- Konfirmasi penghapusan menjelaskan jumlah Order/Tagihan lama yang akan ikut dibersihkan.
- Menambahkan regression test untuk cleanup Customer dengan Invoice/Order soft-delete tanpa pembayaran dan perlindungan Invoice yang pernah dibayar.

## Database

Tidak ada migration baru.
