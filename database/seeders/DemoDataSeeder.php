<?php

namespace Database\Seeders;

use App\Models\{
    BankAccount,
    Batch,
    Country,
    CustomerProfile,
    GoGroup,
    Invoice,
    Order,
    OrderAdjustment,
    OrderItem,
    Payment,
    PaymentProof,
    Product,
    ProductVariant,
    Shipment,
    StatusDefinition,
    StatusHistory,
    User,
    Warehouse
};
use App\Services\OrderStatusService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('DEMO_PASSWORD', 'DemoGBUKR2026!');
        if (strlen($password) < 12) {
            throw new \RuntimeException('DEMO_PASSWORD minimal 12 karakter jika SEED_DEMO_DATA=true.');
        }

        $admin = $this->user('admin.demo@example.com', 'Admin Demo GBUKR', 'customer', $password, 'admin.demo', '081234560010', null, 'whatsapp', true);
        $naya = $this->user('demo.naya@example.com', 'Naya Demo', 'customer', $password, 'naya.demo', '081234560001', null, 'whatsapp');
        $raka = $this->user('demo.raka@example.com', 'Raka Demo', 'customer', $password, 'raka.demo', null, 'raka_demo_line', 'line');
        $sari = $this->user('demo.sari@example.com', 'Sari Demo', 'customer', $password, 'sari.demo', '081234560003', 'sari_demo_line', 'whatsapp');

        $kr = Country::where('code', 'KR')->firstOrFail();
        $jp = Country::where('code', 'JP')->firstOrFail();
        $ch = Country::where('code', 'CH')->firstOrFail();
        $ph = Country::where('code', 'PH')->firstOrFail();

        $gbu = GoGroup::where('name', 'GBUKPOP')->firstOrFail();
        $krjastip = GoGroup::where('name', 'KRJASTIP')->firstOrFail();


        $demoBankA = BankAccount::updateOrCreate(
            ['account_number' => '1234567890'],
            ['bank_name' => 'BCA Demo', 'account_name' => 'GBUKR Demo', 'instructions' => 'Rekening demo aktif untuk alur pembayaran.', 'active' => true]
        );
        $demoBankB = BankAccount::updateOrCreate(
            ['account_number' => '9876543210'],
            ['bank_name' => 'Mandiri Demo', 'account_name' => 'GBUKR Demo', 'instructions' => 'Alternatif rekening demo.', 'active' => true]
        );
        BankAccount::updateOrCreate(
            ['account_number' => '111122223333'],
            ['bank_name' => 'BNI Demo Nonaktif', 'account_name' => 'GBUKR Demo', 'instructions' => 'Contoh rekening nonaktif.', 'active' => false]
        );

        StatusDefinition::updateOrCreate(
            ['code' => 'quality_check'],
            ['label' => 'Quality Check', 'color' => '#ec4899', 'sort_order' => 55, 'active' => true, 'system' => false]
        );

        $krWh1 = Warehouse::where('code', 'KR-WH01')->firstOrFail();
        $chWh1 = Warehouse::where('code', 'CH-WH01')->firstOrFail();
        $phWh1 = Warehouse::where('code', 'PH-WH01')->firstOrFail();

        $cortis = Product::updateOrCreate(
            ['slug' => 'demo-cortis-photocard-po'],
            [
                'country_id' => $kr->id,
                'type' => 'po',
                'name' => 'CORTIS 2nd EP Album [GREENGREEN]',
                'description' => 'Contoh PO lengkap dengan beberapa sumber website, pricelist, DP per variasi, dan estimasi berat.',
                'active' => true,
                'item_fee_idr' => 12500,
                'free_shipping' => false,
                'ems_tax' => true,
                'tax_status' => 'included_estimate',
                'apply_fansign' => false,
                'location_note' => 'Sidoarjo, Jawa Timur',
                'event_date' => now()->addDays(12)->toDateString(),
                'payment_scheme' => ['type' => 'dp', 'amount' => null, 'percent' => 60, 'deadline_days' => 3],
            ]
        );
        ProductVariant::where('product_id', $cortis->id)->whereNull('source_label')->delete();

        $cortis->preorder()->updateOrCreate([], [
            'open_at' => now()->subDays(2),
            'close_at' => now()->addDays(12),
            'status' => 'open',
            'closed_manually_at' => null,
        ]);

        $cortisVariants = [
            ['Ktown4u Website', 'Standard Version (Random)', 'Studio Ver / Street Ver / Bridge Ver', 300, 241000, 150000],
            ['Ktown4u Website', 'Standard Version (Set)', 'Studio Ver + Street Ver + Bridge Ver (Get 3pcs Album)', 1000, 710000, 500000],
            ['Ktown4u Website', 'Weverse Album (Random)', 'Panorama A Ver / Panorama B Ver / Portrait A Ver / Portrait B Ver', 50, 125000, 85000],
            ['Ktown4u Website', 'Weverse Album (Set)', 'Panorama A Ver + Panorama B Ver + Portrait A Ver + Portrait B Ver', 200, 465000, 350000],
            ['Ktown4u Website', 'Vinyl Ver', null, 400, 410000, 300000],
            ['Ktown4u Website', 'Dice Ver', null, 300, 315000, 250000],
            ['Weverse Website', 'Standard Version (Random)', 'Studio Ver / Street Ver / Bridge Ver', 300, 315000, 200000],
            ['Weverse Website', 'Standard Version (Set)', 'Studio Ver + Street Ver + Bridge Ver (Get 3pcs Album)', 1000, 920000, 700000],
            ['Weverse Website', 'Weverse Album (Random)', 'Panorama A Ver / Panorama B Ver / Portrait A Ver / Portrait B Ver', 50, 165000, 85000],
            ['Weverse Website', 'Weverse Album (Set)', 'Panorama A Ver + Panorama B Ver + Portrait A Ver + Portrait B Ver', 200, 645000, 550000],
            ['Weverse Website', 'Vinyl Ver', null, 400, 555000, 450000],
            ['Weverse Website', 'Dice Ver', null, 300, 420000, 350000],
        ];

        $cortisSet = null;
        foreach ($cortisVariants as [$source, $name, $details, $weight, $price, $dp]) {
            $variant = ProductVariant::updateOrCreate(
                ['product_id' => $cortis->id, 'source_label' => $source, 'name' => $name],
                [
                    'details' => $details,
                    'estimated_weight_grams' => $weight,
                    'price_foreign' => null,
                    'price_idr' => $price,
                    'dp_amount_idr' => $dp,
                    'stock' => null,
                    'active' => true,
                ]
            );
            if ($source === 'Ktown4u Website' && $name === 'Standard Version (Random)') {
                $cortisSet = $variant;
            }
        }

        $enhypen = Product::updateOrCreate(
            ['slug' => 'demo-enhypen-the-sin-bliss'],
            [
                'country_id' => $kr->id,
                'type' => 'po',
                'name' => 'ENHYPEN The Sin : Bliss',
                'description' => 'Contoh PO dengan dua website sumber dan DP berbeda di setiap variasi.',
                'active' => true,
                'item_fee_idr' => 12500,
                'free_shipping' => false,
                'ems_tax' => true,
                'tax_status' => 'included_estimate',
                'apply_fansign' => true,
                'location_note' => 'Sidoarjo, Jawa Timur',
                'event_date' => now()->addDays(7)->toDateString(),
                'payment_scheme' => ['type' => 'dp', 'amount' => null, 'percent' => 60, 'deadline_days' => 3],
            ]
        );
        $enhypen->preorder()->updateOrCreate([], [
            'open_at' => now()->subDay(),
            'close_at' => now()->addDays(7),
            'status' => 'open',
            'closed_manually_at' => null,
        ]);

        foreach ([
            ['Korean Web', 'Photobook Ver', 300, 330000, 250000],
            ['Korean Web', 'Engene Ver', 100, 215000, 150000],
            ['Korean Web', 'Weverse Ver', 80, 165000, 100000],
            ['Korean Web', 'Vinyl Ver', 500, 675000, 580000],
            ['Korean Web', 'ID Card Keyring Ver', 200, 205000, 150000],
            ['Korean Web', 'ID Card Ver', 200, 205000, 150000],
            ['Ktown4u Web', 'Photobook Ver', 300, 255000, 180000],
            ['Ktown4u Web', 'Engene Ver', 100, 170000, 100000],
            ['Ktown4u Web', 'Weverse Ver', 80, 135000, 85000],
            ['Ktown4u Web', 'Vinyl Ver', 500, 530000, 445000],
            ['Ktown4u Web', 'ID Card Keyring Ver', 200, 210000, 165000],
            ['Ktown4u Web', 'ID Card Ver', 200, 165000, 100000],
        ] as [$source, $name, $weight, $price, $dp]) {
            ProductVariant::updateOrCreate(
                ['product_id' => $enhypen->id, 'source_label' => $source, 'name' => $name],
                [
                    'estimated_weight_grams' => $weight,
                    'price_foreign' => null,
                    'price_idr' => $price,
                    'dp_amount_idr' => $dp,
                    'stock' => null,
                    'active' => true,
                ]
            );
        }

        $sg = Product::updateOrCreate(
            ['slug' => 'demo-season-greetings-2026'],
            [
                'country_id' => $kr->id,
                'type' => 'po',
                'name' => 'Season Greetings 2026',
                'description' => 'Contoh PO dengan Full Payment.',
                'tax_status' => 'excluded',
                'free_shipping' => true,
                'active' => true,
                'payment_scheme' => ['type' => 'full', 'amount' => null, 'percent' => null, 'deadline_days' => 2],
            ]
        );
        $sg->preorder()->updateOrCreate([], [
            'open_at' => now()->subDay(),
            'close_at' => now()->addDays(7),
            'status' => 'open',
            'closed_manually_at' => null,
        ]);
        ProductVariant::updateOrCreate(
            ['product_id' => $sg->id, 'name' => 'Box Set'],
            ['price_foreign' => 42000, 'price_idr' => null, 'stock' => null, 'active' => true]
        );

        $jpReady = Product::updateOrCreate(
            ['slug' => 'demo-japan-character-keyring'],
            [
                'country_id' => $jp->id,
                'type' => 'ready',
                'name' => 'Character Keyring',
                'description' => 'Contoh Ready Stock Jepang dengan stok per variasi.',
                'tax_status' => 'excluded',
                'active' => true,
                'payment_scheme' => ['type' => 'full', 'amount' => null, 'percent' => null, 'deadline_days' => 1],
            ]
        );
        $jpBlue = ProductVariant::updateOrCreate(
            ['product_id' => $jpReady->id, 'name' => 'Blue'],
            ['price_foreign' => null, 'price_idr' => 85000, 'stock' => 7, 'active' => true]
        );
        ProductVariant::updateOrCreate(
            ['product_id' => $jpReady->id, 'name' => 'Pink'],
            ['price_foreign' => null, 'price_idr' => 85000, 'stock' => 5, 'active' => true]
        );

        $chReady = Product::updateOrCreate(
            ['slug' => 'demo-china-acrylic-stand'],
            [
                'country_id' => $ch->id,
                'type' => 'ready',
                'name' => 'Acrylic Stand',
                'description' => 'Contoh Ready Stock China.',
                'tax_status' => 'excluded',
                'active' => true,
                'payment_scheme' => ['type' => 'full', 'amount' => null, 'percent' => null, 'deadline_days' => 1],
            ]
        );
        ProductVariant::updateOrCreate(
            ['product_id' => $chReady->id, 'name' => 'Version A'],
            ['price_foreign' => 35, 'price_idr' => null, 'stock' => 10, 'active' => true]
        );

        $poOrder = Order::updateOrCreate(
            ['order_number' => 'ORD-DEMO-PO-001'],
            [
                'customer_id' => $naya->id,
                'preorder_id' => $cortis->preorder->id,
                'batch_id' => null,
                'go_group_id' => $gbu->id,
                'source_type' => 'po',
                'status' => 'ordered',
                'rate_snapshot' => $kr->rate,
                'currency_code' => 'KRW',
                'notes' => 'Data demo PO yang dibuat otomatis dari checkout.',
            ]
        );
        OrderItem::updateOrCreate(
            ['order_id' => $poOrder->id, 'item_name' => 'CORTIS 2nd EP Album [GREENGREEN]'],
            [
                'product_id' => $cortis->id,
                'product_variant_id' => $cortisSet->id,
                'details' => 'Ktown4u Website · Standard Version (Random)',
                'description_type' => 'Album',
                'qty' => 1,
                'unit_price_foreign' => null,
                'unit_price_idr' => 241000,
                'subtotal_idr' => 241000,
            ]
        );
        Invoice::updateOrCreate(
            ['invoice_number' => 'INV-DEMO-PO-001'],
            [
                'customer_id' => $naya->id,
                'order_id' => $poOrder->id,
                'type' => 'dp',
                'amount' => 150000,
                'paid_amount' => 0,
                'penalty_amount' => 0,
                'deadline_at' => now()->addDays(2),
                'status' => 'unpaid',
                'notes' => 'DP awal contoh PO.',
            ]
        );

        $demoAdjustmentInvoice = Invoice::updateOrCreate(
            ['invoice_number' => 'INV-DEMO-KRG-001'],
            [
                'customer_id' => $naya->id,
                'order_id' => $poOrder->id,
                'type' => 'kekurangan',
                'amount' => 50000,
                'paid_amount' => 0,
                'penalty_amount' => 0,
                'deadline_at' => now()->addDays(5),
                'status' => 'unpaid',
                'notes' => 'Contoh kekurangan karena berat aktual dan rate berubah setelah barang tiba.',
            ]
        );
        OrderAdjustment::updateOrCreate(
            ['legacy_key' => 'DEMO-KRG-001'],
            [
                'order_id' => $poOrder->id,
                'invoice_id' => $demoAdjustmentInvoice->id,
                'type' => 'kekurangan',
                'reason' => 'weight_rate',
                'amount_idr' => 50000,
                'estimated_weight_grams' => 500,
                'actual_weight_grams' => 700,
                'original_rate' => 12,
                'final_rate' => 12.5,
                'notes' => 'Berat aktual lebih besar dari estimasi dan rate berubah saat barang tiba.',
            ]
        );

        $readyOrder = Order::updateOrCreate(
            ['order_number' => 'ORD-DEMO-RS-001'],
            [
                'customer_id' => $sari->id,
                'source_type' => 'ready',
                'status' => 'completed',
                'rate_snapshot' => $jp->rate,
                'currency_code' => 'JPY',
                'completed_at' => now()->subDays(2),
                'notes' => 'Data demo Ready Stock yang sudah selesai.',
            ]
        );
        OrderItem::updateOrCreate(
            ['order_id' => $readyOrder->id, 'item_name' => 'Character Keyring'],
            [
                'product_id' => $jpReady->id,
                'product_variant_id' => $jpBlue->id,
                'details' => 'Blue',
                'description_type' => 'Keyring',
                'qty' => 1,
                'unit_price_idr' => 85000,
                'subtotal_idr' => 85000,
            ]
        );
        $readyInvoice = Invoice::updateOrCreate(
            ['invoice_number' => 'INV-DEMO-RS-001'],
            [
                'customer_id' => $sari->id,
                'order_id' => $readyOrder->id,
                'type' => 'full',
                'amount' => 85000,
                'paid_amount' => 85000,
                'penalty_amount' => 0,
                'deadline_at' => now()->subDays(5),
                'status' => 'paid',
                'notes' => 'Contoh tagihan yang sudah lunas.',
            ]
        );

        $batchKr = Batch::updateOrCreate(
            ['code' => 'KR-260915-001'],
            [
                'go_group_id' => $gbu->id,
                'country_id' => $kr->id,
                'warehouse_id' => $krWh1->id,
                'name' => 'CORTIS Seller Batch',
                'description' => 'Contoh Batch dari seller Korea.',
                'status' => 'arrived_wh',
                'tracking_number' => 'DEMO-KR-260915-001',
            ]
        );

        $batchKrOrder = Order::updateOrCreate(
            ['order_number' => 'ORD-DEMO-B-001'],
            [
                'customer_id' => $raka->id,
                'go_group_id' => $gbu->id,
                'batch_id' => $batchKr->id,
                'source_type' => 'batch',
                'status' => 'arrived_wh',
                'currency_code' => 'KRW',
                'notes' => 'Contoh order manual di Batch Korea.',
            ]
        );
        OrderItem::updateOrCreate(
            ['order_id' => $batchKrOrder->id, 'item_name' => 'CORTIS Photocard'],
            [
                'details' => 'Seonghyeon Set',
                'description_type' => 'Photocard',
                'qty' => 2,
            ]
        );
        $pendingInvoice = Invoice::updateOrCreate(
            ['invoice_number' => 'INV-DEMO-B-001'],
            [
                'customer_id' => $raka->id,
                'order_id' => $batchKrOrder->id,
                'type' => 'pelunasan',
                'amount' => 120000,
                'paid_amount' => 0,
                'penalty_amount' => 0,
                'deadline_at' => now()->addDays(5),
                'status' => 'pending',
                'notes' => 'Contoh pembayaran yang menunggu verifikasi.',
            ]
        );

        $batchCh = Batch::updateOrCreate(
            ['code' => 'CH-260918-001'],
            [
                'go_group_id' => $krjastip->id,
                'country_id' => $ch->id,
                'warehouse_id' => $chWh1->id,
                'name' => 'Merchandise China Batch',
                'description' => 'Contoh Batch China yang sedang menuju Indonesia.',
                'status' => 'otw_indo',
                'tracking_number' => 'DEMO-CH-260918-001',
            ]
        );
        $batchChOrder = Order::updateOrCreate(
            ['order_number' => 'ORD-DEMO-B-002'],
            [
                'customer_id' => $naya->id,
                'go_group_id' => $krjastip->id,
                'batch_id' => $batchCh->id,
                'source_type' => 'batch',
                'status' => 'otw_indo',
                'currency_code' => 'CNY',
                'notes' => 'Contoh Batch China.',
            ]
        );
        OrderItem::updateOrCreate(
            ['order_id' => $batchChOrder->id, 'item_name' => 'Acrylic Stand'],
            ['details' => 'Version A', 'description_type' => 'Merchandise', 'qty' => 1]
        );
        Invoice::updateOrCreate(
            ['invoice_number' => 'INV-DEMO-B-002'],
            [
                'customer_id' => $naya->id,
                'order_id' => $batchChOrder->id,
                'type' => 'dp',
                'amount' => 60000,
                'paid_amount' => 0,
                'penalty_amount' => 4000,
                'deadline_at' => now()->subDays(2),
                'status' => 'overdue',
                'notes' => 'Contoh DP terlambat 2 hari, denda Rp2.000/hari.',
            ]
        );

        $batchPh = Batch::updateOrCreate(
            ['code' => 'PH-260920-001'],
            [
                'go_group_id' => $krjastip->id,
                'country_id' => $ph->id,
                'warehouse_id' => $phWh1->id,
                'name' => 'Magazine Philippines Batch',
                'description' => 'Contoh Batch Filipina yang sudah tiba di GBU/KRJASTIP.',
                'status' => 'arrived_gbu',
                'tracking_number' => 'DEMO-PH-260920-001',
                'arrived_gbu_at' => now()->subDays(8),
            ]
        );
        $batchPhOrder = Order::updateOrCreate(
            ['order_number' => 'ORD-DEMO-B-003'],
            [
                'customer_id' => $sari->id,
                'go_group_id' => $krjastip->id,
                'batch_id' => $batchPh->id,
                'source_type' => 'batch',
                'status' => 'arrived_gbu',
                'currency_code' => 'PHP',
                'arrived_gbu_at' => now()->subDays(8),
                'notes' => 'Contoh barang yang sudah sampai GBU/KRJASTIP.',
            ]
        );
        OrderItem::updateOrCreate(
            ['order_id' => $batchPhOrder->id, 'item_name' => 'K-pop Magazine'],
            ['details' => 'September Edition', 'description_type' => 'Magazine', 'qty' => 1]
        );
        $paidBatchInvoice = Invoice::updateOrCreate(
            ['invoice_number' => 'INV-DEMO-B-003'],
            [
                'customer_id' => $sari->id,
                'order_id' => $batchPhOrder->id,
                'type' => 'pelunasan',
                'amount' => 95000,
                'paid_amount' => 95000,
                'penalty_amount' => 0,
                'deadline_at' => now()->subDays(10),
                'status' => 'paid',
                'notes' => 'Contoh pelunasan Batch yang sudah lunas.',
            ]
        );

        // Demo bulk Tagihan Tambahan: tagihan awal tetap utuh, tax muncul sebagai invoice terpisah.
        $batchPhOrder2 = Order::updateOrCreate(
            ['order_number' => 'ORD-DEMO-B-004'],
            [
                'customer_id' => $naya->id,
                'go_group_id' => $krjastip->id,
                'batch_id' => $batchPh->id,
                'source_type' => 'batch',
                'status' => 'arrived_gbu',
                'currency_code' => 'PHP',
                'arrived_gbu_at' => now()->subDays(8),
                'notes' => 'Contoh customer kedua pada Batch yang sama untuk demo tagihan tambahan massal.',
            ]
        );
        OrderItem::updateOrCreate(
            ['order_id' => $batchPhOrder2->id, 'item_name' => 'K-pop Magazine Special'],
            ['details' => 'Collector Edition', 'description_type' => 'Magazine', 'qty' => 2]
        );
        Invoice::updateOrCreate(
            ['invoice_number' => 'INV-DEMO-B-004'],
            [
                'customer_id' => $naya->id,
                'order_id' => $batchPhOrder2->id,
                'type' => 'pelunasan',
                'amount' => 150000,
                'paid_amount' => 150000,
                'penalty_amount' => 0,
                'deadline_at' => now()->subDays(10),
                'status' => 'paid',
                'notes' => 'Tagihan awal sudah lunas; tax dibuat terpisah setelah barang tiba.',
            ]
        );

        foreach ([
            [$batchPhOrder, $sari, 'INV-DEMO-TMB-001', 'DEMO-TMB-001', 25000],
            [$batchPhOrder2, $naya, 'INV-DEMO-TMB-002', 'DEMO-TMB-002', 40000],
        ] as [$taxOrder, $taxCustomer, $invoiceNumber, $legacyKey, $amount]) {
            $taxInvoice = Invoice::updateOrCreate(
                ['invoice_number' => $invoiceNumber],
                [
                    'customer_id' => $taxCustomer->id,
                    'order_id' => $taxOrder->id,
                    'type' => 'kekurangan',
                    'amount' => $amount,
                    'paid_amount' => 0,
                    'penalty_amount' => 0,
                    'deadline_at' => now()->addDays(5),
                    'status' => 'unpaid',
                    'notes' => 'Tagihan tambahan tax aktual setelah barang Batch tiba di Indonesia.',
                ]
            );
            OrderAdjustment::updateOrCreate(
                ['legacy_key' => $legacyKey],
                [
                    'order_id' => $taxOrder->id,
                    'invoice_id' => $taxInvoice->id,
                    'type' => 'kekurangan',
                    'reason' => 'tax',
                    'amount_idr' => $amount,
                    'notes' => 'Demo tagihan tambahan dari Batch PH-260920-001.',
                ]
            );
        }


        // SCENARIO: satu order mempunyai lebih dari satu Tagihan Tambahan (Tax + perubahan Rate).
        $rateInvoice = Invoice::updateOrCreate(
            ['invoice_number' => 'INV-DEMO-TMB-RATE-001'],
            ['customer_id'=>$sari->id,'order_id'=>$batchPhOrder->id,'type'=>'kekurangan','amount'=>15000,'paid_amount'=>0,'penalty_amount'=>0,'deadline_at'=>now()->addDays(6),'status'=>'unpaid','notes'=>'Tambahan kedua pada order yang sama: perubahan rate.']
        );
        OrderAdjustment::updateOrCreate(['legacy_key'=>'DEMO-TMB-RATE-001'],[
            'order_id'=>$batchPhOrder->id,'invoice_id'=>$rateInvoice->id,'type'=>'kekurangan','reason'=>'rate','amount_idr'=>15000,'original_rate'=>12,'final_rate'=>12.5,'notes'=>'Contoh Tax dan Rate pada order yang sama.'
        ]);

        // SCENARIO: penyesuaian berat.
        $weightInvoice=Invoice::updateOrCreate(['invoice_number'=>'INV-DEMO-TMB-WEIGHT-001'],[
            'customer_id'=>$naya->id,'order_id'=>$batchChOrder->id,'type'=>'kekurangan','amount'=>22000,'paid_amount'=>0,'penalty_amount'=>0,'deadline_at'=>now()->addDays(4),'status'=>'unpaid','notes'=>'Berat aktual melebihi estimasi.'
        ]);
        OrderAdjustment::updateOrCreate(['legacy_key'=>'DEMO-TMB-WEIGHT-001'],[
            'order_id'=>$batchChOrder->id,'invoice_id'=>$weightInvoice->id,'type'=>'kekurangan','reason'=>'weight','amount_idr'=>22000,'estimated_weight_grams'=>300,'actual_weight_grams'=>480,'notes'=>'Demo penyesuaian berat.'
        ]);

        // SCENARIO: shipping aktual berubah.
        $shippingInvoice=Invoice::updateOrCreate(['invoice_number'=>'INV-DEMO-TMB-SHIP-001'],[
            'customer_id'=>$raka->id,'order_id'=>$batchKrOrder->id,'type'=>'kekurangan','amount'=>18000,'paid_amount'=>0,'penalty_amount'=>0,'deadline_at'=>now()->addDays(3),'status'=>'unpaid','notes'=>'Shipping aktual lebih tinggi dari estimasi.'
        ]);
        OrderAdjustment::updateOrCreate(['legacy_key'=>'DEMO-TMB-SHIP-001'],[
            'order_id'=>$batchKrOrder->id,'invoice_id'=>$shippingInvoice->id,'type'=>'kekurangan','reason'=>'shipping_actual','amount_idr'=>18000,'estimated_shipping_idr'=>30000,'actual_shipping_idr'=>48000,'notes'=>'Demo shipping aktual.'
        ]);

        // SCENARIO: tambahan lain dengan catatan khusus.
        $otherInvoice=Invoice::updateOrCreate(['invoice_number'=>'INV-DEMO-TMB-OTHER-001'],[
            'customer_id'=>$naya->id,'order_id'=>$poOrder->id,'type'=>'kekurangan','amount'=>10000,'paid_amount'=>0,'penalty_amount'=>0,'deadline_at'=>now()->addDays(7),'status'=>'unpaid','notes'=>'Biaya packing khusus dari seller.'
        ]);
        OrderAdjustment::updateOrCreate(['legacy_key'=>'DEMO-TMB-OTHER-001'],[
            'order_id'=>$poOrder->id,'invoice_id'=>$otherInvoice->id,'type'=>'kekurangan','reason'=>'other','amount_idr'=>10000,'notes'=>'Biaya packing khusus seller.'
        ]);

        // SCENARIO: invoice partial.
        Invoice::updateOrCreate(['invoice_number'=>'INV-DEMO-PARTIAL-001'],[
            'customer_id'=>$raka->id,'order_id'=>$batchKrOrder->id,'type'=>'cicilan','amount'=>100000,'paid_amount'=>40000,'penalty_amount'=>0,'deadline_at'=>now()->addDays(8),'status'=>'partial','notes'=>'Contoh cicilan sebagian dibayar.'
        ]);

        // SCENARIO: order Unclaimed.
        $unclaimedOrder=Order::updateOrCreate(['order_number'=>'ORD-DEMO-UNCLAIMED-001'],[
            'customer_id'=>$naya->id,'go_group_id'=>$gbu->id,'batch_id'=>$batchPh->id,'source_type'=>'batch','status'=>'unclaimed','currency_code'=>'PHP','arrived_gbu_at'=>now()->subMonths(4),'notes'=>'Contoh barang lewat 3 bulan dan belum di-claim.'
        ]);
        OrderItem::updateOrCreate(['order_id'=>$unclaimedOrder->id,'item_name'=>'Demo Unclaimed Album'],['details'=>'Unclaimed case','description_type'=>'Album','qty'=>1]);

        // SCENARIO: pembayaran ditolak.
        $rejectedInvoice=Invoice::updateOrCreate(['invoice_number'=>'INV-DEMO-REJECTED-001'],[
            'customer_id'=>$naya->id,'order_id'=>$batchChOrder->id,'type'=>'pelunasan','amount'=>30000,'paid_amount'=>0,'penalty_amount'=>0,'deadline_at'=>now()->addDays(2),'status'=>'unpaid','notes'=>'Tagihan demo untuk bukti pembayaran yang ditolak.'
        ]);
        $rejectedPayment=Payment::updateOrCreate(['payment_number'=>'PAY-DEMO-REJECTED-001'],[
            'customer_id'=>$naya->id,'bank_account_id'=>$demoBankA->id,'amount'=>30000,'status'=>'rejected','submitted_at'=>now()->subHours(6),'verified_at'=>now()->subHours(5),'verified_by'=>$admin->id,'rejection_reason'=>'Nominal pada bukti tidak sesuai.'
        ]);
        $rejectedPayment->invoices()->syncWithoutDetaching([$rejectedInvoice->id=>['allocated_amount'=>30000]]);
        $rejectedPath='demo/payment-proof-pay-demo-rejected-001.pdf';
        if(!Storage::exists($rejectedPath)){Storage::put($rejectedPath, "%PDF-1.4\n% rejected demo proof\n%%EOF");}
        PaymentProof::updateOrCreate(['payment_id'=>$rejectedPayment->id,'path'=>$rejectedPath],['original_name'=>'bukti-transfer-ditolak-demo.pdf','mime_type'=>'application/pdf','size'=>Storage::size($rejectedPath)]);

        $this->shipment($batchKr, 'CORTIS Photocard', 'Photocard');
        $this->shipment($batchCh, 'Acrylic Stand', 'Merchandise');
        $this->shipment($batchPh, 'K-pop Magazine', 'Magazine');

        $this->statusHistory($batchKrOrder, null, 'ordered', now()->subDays(8), $admin->id);
        $this->statusHistory($batchKrOrder, 'ordered', 'arrived_wh', now()->subDays(4), $admin->id);
        $this->statusHistory($batchChOrder, 'arrived_wh', 'otw_indo', now()->subDays(3), $admin->id);
        $this->statusHistory($batchPhOrder, 'arrived_indo', 'arrived_gbu', now()->subDays(8), $admin->id);

        $this->paymentWithProof(
            'PAY-DEMO-PENDING-001',
            $raka,
            $pendingInvoice,
            120000,
            'pending',
            null,
            $demoBankA
        );

        $this->paymentWithProof(
            'PAY-DEMO-APPROVED-001',
            $sari,
            $paidBatchInvoice,
            95000,
            'approved',
            $admin->id,
            $demoBankB
        );

        $this->paymentWithProof(
            'PAY-DEMO-APPROVED-002',
            $sari,
            $readyInvoice,
            85000,
            'approved',
            $admin->id,
            $demoBankA
        );
    }

    private function user(
        string $email,
        string $name,
        string $role,
        string $password,
        ?string $username = null,
        ?string $whatsapp = null,
        ?string $line = null,
        ?string $source = null,
        bool $adminEnabled = false
    ): User {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'role' => $role,
                'admin_enabled' => $adminEnabled,
                'active' => true,
                'email_verified_at' => now(),
            ]
        );

        if ($role === 'customer') {
            CustomerProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'username' => $username,
                    'whatsapp' => $whatsapp,
                    'line_id' => $line,
                    'source_channel' => $source,
                    'notes' => 'Akun demo untuk presentasi dan QA.',
                ]
            );
        }

        return $user;
    }

    private function shipment(Batch $batch, string $item, string $type): void
    {
        Shipment::updateOrCreate(
            ['source_type' => 'batch', 'source_id' => $batch->id],
            [
                'reference' => $batch->code,
                'item_details' => $item,
                'description_type' => $type,
                'info' => $batch->description,
                'country_id' => $batch->country_id,
                'tracking_number' => $batch->tracking_number,
                'status' => $batch->status,
                'visible_publicly' => true,
            ]
        );
    }

    private function statusHistory(Order $order, ?string $from, string $to, $changedAt, int $actor): void
    {
        StatusHistory::updateOrCreate(
            [
                'entity_type' => 'order',
                'entity_id' => $order->id,
                'to_status' => $to,
            ],
            [
                'from_status' => $from,
                'changed_by' => $actor,
                'changed_at' => $changedAt,
                'notes' => 'Riwayat status data demo.',
            ]
        );
    }

    private function paymentWithProof(
        string $number,
        User $customer,
        Invoice $invoice,
        int $amount,
        string $status,
        ?int $verifiedBy,
        ?BankAccount $bank = null
    ): void {
        $payment = Payment::updateOrCreate(
            ['payment_number' => $number],
            [
                'customer_id' => $customer->id,
                'bank_account_id' => $bank?->id ?: BankAccount::where('active', true)->value('id'),
                'amount' => $amount,
                'status' => $status,
                'submitted_at' => now()->subDay(),
                'verified_at' => $status === 'approved' ? now()->subHours(18) : null,
                'verified_by' => $verifiedBy,
                'rejection_reason' => null,
            ]
        );

        $payment->invoices()->syncWithoutDetaching([
            $invoice->id => ['allocated_amount' => $amount],
        ]);

        $path = 'demo/payment-proof-' . strtolower($number) . '.pdf';
        if (!Storage::exists($path)) {
            Storage::put($path, "%PDF-1.4\n% GBUKR demo payment proof\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF");
        }

        PaymentProof::updateOrCreate(
            ['payment_id' => $payment->id, 'path' => $path],
            [
                'original_name' => 'bukti-transfer-demo.pdf',
                'mime_type' => 'application/pdf',
                'size' => Storage::size($path),
            ]
        );
    }
}
