<?php

namespace Tests\Feature;

use App\Models\{Batch, Country, GoGroup, Order, Shipment};
use App\Services\LegacyImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class LegacyImportFallbackBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_row_without_batch_reference_gets_deterministic_fallback_batch(): void
    {
        Country::create([
            'code' => 'KR',
            'name' => 'Korea Selatan',
            'currency_code' => 'KRW',
            'currency_symbol' => '₩',
            'rate' => 12,
            'admin_fee_idr' => 25000,
            'active' => true,
        ]);
        $group = GoGroup::create(['name' => 'GO Tanpa Batch', 'status' => 'active']);

        $tmp = tempnam(sys_get_temp_dir(), 'gbukr-no-batch-') . '.xlsx';
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('TAGIHAN KR');
        $sheet->fromArray([
            ['Batch', 'Nama', 'Items', 'Details', 'Qty', 'Price', 'Payment', '', '', '', 'Pelunasan', 'Status', 'Ket'],
            ['', '', '', '', '', '', 'Fullpay', 'DP', 'Cicilan', 'Kenaikan', '', '', ''],
            ['', 'Customer Tanpa Batch', 'Album Demo', 'Version A', 1, 150000, 150000, 0, 0, 0, 0, 'Lunas', 'Tidak ada kode batch di workbook'],
        ], null, 'A1');

        $status = $book->createSheet();
        $status->setTitle('STATUS BARANG');
        $status->setCellValue('A1', 'Batch');
        $status->setCellValue('B1', 'Detail Barang');
        $status->setCellValue('C1', 'Keterangan');
        $status->setCellValue('D1', 'Info');
        $status->setCellValue('E1', 'Qty');
        $status->setCellValue('H1', 'Korea');
        $status->setCellValue('P1', 'Tracking Number');
        $status->setCellValue('Q1', 'Status');
        $status->setCellValue('B3', 'Album Demo');
        $status->setCellValue('C3', 'Album');
        $status->setCellValue('D3', 'Tanpa referensi Batch');
        $status->setCellValue('E3', 1);
        $status->setCellValue('H3', 'KR');
        $status->setCellValue('P3', 'DEMO-NO-BATCH');
        $status->setCellValue('Q3', 'Arrived WH');

        (new Xlsx($book))->save($tmp);

        try {
            app(LegacyImportService::class)->import($tmp, $group->id, 'Workbook Tanpa Batch.xlsx');
        } finally {
            @unlink($tmp);
        }

        $this->assertDatabaseCount('batches', 1);
        $batch = Batch::firstOrFail();
        $this->assertSame($group->id, $batch->go_group_id);
        $this->assertStringStartsWith('KR-LEG-G' . $group->id . '-UNASSIGNED-', $batch->code);
        $this->assertStringContainsString('Legacy Tanpa Batch', $batch->name);

        $order = Order::firstOrFail();
        $this->assertSame($batch->id, $order->batch_id);
        $this->assertSame('batch', $order->source_type);
        $this->assertDatabaseHas('shipments', [
            'source_type' => 'batch',
            'source_id' => $batch->id,
        ]);
        $this->assertSame('DEMO-NO-BATCH', Shipment::where('source_type', 'batch')->firstOrFail()->tracking_number);
    }
}
