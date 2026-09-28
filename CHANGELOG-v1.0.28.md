# GBUKPOP x KRJASTIP — v1.0.28

## Perbaikan Cleanup Import
- Menghapus batas validasi 500 `review_order_ids`.
- Pilihan review dikirim melalui payload JSON sehingga tidak bergantung pada `max_input_vars` PHP.
- Review dan pemrosesan cleanup menggunakan chunk 100 order agar penggunaan memory lebih stabil.
- `Pilih semua` dapat memproses seluruh data review yang dimuat modal, bukan hanya 500 item.

## Perbaikan Penghapusan Order, Payment, dan Customer
- Approval penghapusan dari customer sekarang benar-benar menghapus data operasional Order, Invoice, dan Payment yang hanya terkait order tersebut.
- Payment gabungan yang masih dipakai invoice lain tidak ikut dihapus.
- Metadata pembayaran dan bukti pembayaran disimpan pada snapshot audit `order_deletion_requests` sebelum purge.
- Bukti pembayaran fisik ikut dibersihkan ketika Payment benar-benar dipurge.
- Customer dapat dihapus setelah seluruh transaksi terkait sudah dihapus dengan approval yang sah.
- Data v1.0.24–v1.0.27 yang sudah terlanjur soft-delete tetap kompatibel: histori finansial dengan approval selesai dikenali sebagai data yang boleh dipurge saat Customer dihapus.
- Histori pembayaran yang tidak mempunyai approval tetap melindungi Customer dari hard delete.
- Merge Customer sekarang juga memindahkan Order/Invoice/Payment soft-delete dan deletion request agar tidak meninggalkan foreign key tersembunyi.

## Deployment / Cache Asset
- CSS dan JavaScript memakai query version berdasarkan `filemtime`, sehingga browser/CDN mengambil asset baru setelah deployment dan tidak terus menggunakan CSS/JS versi lama.

## Database
- Tidak ada migration baru.
