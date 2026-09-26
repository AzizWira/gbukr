<?php

namespace Tests\Feature;

use App\Models\{Batch, Country, Invoice, Order, User};
use App\Services\LegacyImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class LegacyImportCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_v107_partial_row_is_adopted_instead_of_duplicated(): void
    {
        $country = Country::create([
            'code' => 'KR',
            'name' => 'Korea Selatan',
            'currency_code' => 'KRW',
            'rate' => 13.8,
            'admin_fee_idr' => 25000,
            'active' => true,
        ]);
        $group = \App\Models\GoGroup::create(['name' => 'CORTIS', 'status' => 'active']);

        $name = 'Customer Lama';
        $item = 'Album A';
        $batchRef = 'BATCH 01';
        $sheetName = 'TAGIHAN KR';
        $row = 3;
        $oldFingerprint = substr(sha1($sheetName . '|' . $row . '|' . mb_strtolower($name) . '|' . $item . '|' . $batchRef), 0, 8);
        $oldOrderNumber = 'LEG-KR-0003-' . strtoupper($oldFingerprint);

        $oldUser = User::create([
            'name' => $name,
            'email' => 'legacy+' . substr(sha1(mb_strtolower($name)), 0, 24) . '@placeholder.local',
            'password' => null,
            'role' => 'customer',
            'active' => true,
        ]);
        $oldBatch = Batch::create([
            'country_id' => $country->id,
            'code' => 'KR-LEG-BATCH-01',
            'name' => 'Legacy Batch BATCH 01',
            'status' => 'ordered',
        ]);
        $oldOrder = Order::create([
            'customer_id' => $oldUser->id,
            'batch_id' => $oldBatch->id,
            'source_type' => 'batch',
            'order_number' => $oldOrderNumber,
            'status' => 'ordered',
            'currency_code' => 'KRW',
        ]);
        $oldInvoice = Invoice::create([
            'customer_id' => $oldUser->id,
            'order_id' => $oldOrder->id,
            'invoice_number' => 'LEG-INV-' . strtoupper($oldFingerprint),
            'type' => 'pelunasan',
            'amount' => 200000,
            'paid_amount' => 100000,
            'penalty_amount' => 0,
            'status' => 'partial',
        ]);

        $tmp = tempnam(sys_get_temp_dir(), 'legacy-compat-') . '.xlsx';
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('TAGIHAN KR');
        $sheet->fromArray([
            ['Batch', 'Nama', 'Items', 'Details', 'Qty', 'Price', 'Payment', '', '', '', 'Pelunasan', 'Status', 'Ket'],
            ['', '', '', '', '', '', 'Fullpay', 'DP', 'Cicilan', 'Kenaikan', '', '', ''],
            [$batchRef, $name, $item, 'Version A', 1, 150000, 0, 100000, 0, 50000, 100000, '', 'Berat/rate berubah'],
        ], null, 'A1');
        (new Xlsx($book))->save($tmp);

        try {
            app(LegacyImportService::class)->import($tmp, $group->id, 'GBU CORTIS.xlsx');
        } finally {
            @unlink($tmp);
        }

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame($oldOrder->id, Order::firstOrFail()->id);
        $this->assertSame($group->id, Order::firstOrFail()->go_group_id);
        $this->assertSame($group->id, $oldBatch->fresh()->go_group_id);

        $this->assertSame($oldInvoice->id, Invoice::where('type', 'pelunasan')->firstOrFail()->id);
        $this->assertDatabaseHas('invoices', ['type' => 'pelunasan', 'amount' => 150000]);
        $this->assertDatabaseHas('invoices', ['type' => 'kekurangan', 'amount' => 50000]);
        $this->assertDatabaseHas('order_adjustments', ['amount_idr' => 50000]);
    }
}
