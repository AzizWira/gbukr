<?php

namespace App\Services;

use App\Models\{BankAccount, Batch, Country, GoGroup, ImportRun, Invoice, Order, OrderAdjustment, Payment, PaymentProof, Product, Shipment, StatusDefinition, User, Warehouse};
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DataExportService
{
    public function exportGo(GoGroup $group): array
    {
        $orders = Order::with([
            'customer.customerProfile',
            'items',
            'invoices.adjustment',
            'batch.country',
            'batch.warehouse',
            'batch.shipment',
            'preorder.product.country',
        ])->where(function ($query) use ($group) {
            $query->where('go_group_id', $group->id)
                ->orWhereHas('batch', fn ($q) => $q->where('go_group_id', $group->id));
        })->orderBy('created_at')->get();

        $batches = Batch::with(['country', 'warehouse', 'shipment', 'orders.items'])
            ->where('go_group_id', $group->id)
            ->orderBy('created_at')
            ->get();

        $invoiceIds = $orders->flatMap->invoices->pluck('id')->unique()->values();
        $payments = Payment::with(['customer', 'invoices.order'])
            ->whereHas('invoices', fn ($q) => $q->whereIn('invoices.id', $invoiceIds))
            ->orderBy('submitted_at')
            ->get();

        $book = $this->newWorkbook('Backup GO ' . $group->name);
        $this->removeDefaultSheet($book);

        $this->writeKeyValueSheet($book, 'RINGKASAN', [
            ['GO', $group->name],
            ['Status GO', $group->status],
            ['Diexport', now()->translatedFormat('d F Y, H.i')],
            ['Total order', $orders->count()],
            ['Total batch', $batches->count()],
            ['Total customer unik', $orders->pluck('customer_id')->unique()->count()],
            ['Total tagihan', $invoiceIds->count()],
            ['Total pembayaran terkait', $payments->count()],
        ]);

        $batchIds = $batches->pluck('id')->values();
        $batchById = $batches->keyBy('id');
        $goShipments = Shipment::with('country')
            ->where(function ($query) use ($batchIds, $group) {
                if ($batchIds->isNotEmpty()) {
                    $query->where(function ($batchQuery) use ($batchIds) {
                        $batchQuery->where('source_type', 'batch')->whereIn('source_id', $batchIds);
                    });
                }
                $query->orWhere(function ($poQuery) use ($group) {
                    $poQuery->where('source_type', 'po')->where('reference', 'like', 'PO-G' . $group->id . '-%');
                });
            })
            ->orderBy('reference')
            ->get();

        $statusRows = $goShipments->map(function (Shipment $shipment) use ($batchById) {
            $batch = $shipment->source_type === 'batch' ? $batchById->get($shipment->source_id) : null;
            return [
                $shipment->reference,
                $shipment->item_details,
                $shipment->description_type,
                $shipment->info,
                $shipment->qty,
                $shipment->country?->name,
                $batch?->warehouse?->code,
                $shipment->tracking_number,
                $shipment->status,
            ];
        })->all();

        $this->writeTableSheet($book, 'STATUS BARANG', [
            'BATCH / PO', 'ITEM DETAILS', 'KETERANGAN', 'INFO', 'QTY', 'NEGARA', 'WAREHOUSE', 'TRACKING NUMBER', 'STATUS',
        ], $statusRows);

        $invoices = $orders->flatMap->invoices->sortBy('created_at')->values();
        $byCountry = $invoices->groupBy(function (Invoice $invoice) {
            $order = $invoice->order;
            return $order?->batch?->country?->code
                ?: $order?->preorder?->product?->country?->code
                ?: $order?->currency_code
                ?: 'UMUM';
        });

        foreach ($byCountry as $countryCode => $countryInvoices) {
            $rows = [];
            foreach ($countryInvoices as $invoice) {
                $order = $invoice->order;
                $items = $order?->items ?? collect();
                $rows[] = [
                    $order?->batch?->code ?: strtoupper((string) ($order?->source_type ?: 'PO')),
                    $invoice->customer?->name,
                    $items->pluck('item_name')->join(', '),
                    $items->pluck('details')->filter()->join(', '),
                    $items->sum('qty'),
                    $items->sum(fn ($item) => (int) ($item->subtotal_idr ?? 0)),
                    strtoupper($invoice->type),
                    $invoice->amount,
                    $invoice->paid_amount,
                    $invoice->type === 'kekurangan' ? $invoice->amount : 0,
                    $invoice->outstanding(),
                    $invoice->deadline_at?->translatedFormat('d F Y, H.i'),
                    strtoupper($invoice->status),
                    $invoice->notes,
                ];
            }

            $sheetName = 'TAGIHAN ' . strtoupper((string) $countryCode);
            $this->writeTableSheet($book, $sheetName, [
                'BATCH / PO', 'NAMA', 'ITEMS', 'DETAILS', 'QTY', 'HARGA ORDER', 'JENIS TAGIHAN',
                'NOMINAL', 'DIBAYAR', 'KEKURANGAN', 'SISA', 'DEADLINE', 'STATUS', 'KET',
            ], $rows, [6, 8, 9, 10, 11]);
        }

        $adjustments = OrderAdjustment::with(['order.customer', 'order.items', 'invoice'])
            ->whereHas('order', function ($q) use ($group) {
                $q->where('go_group_id', $group->id)
                    ->orWhereHas('batch', fn ($batch) => $batch->where('go_group_id', $group->id));
            })
            ->orderBy('created_at')
            ->get();

        $this->writeTableSheet($book, 'TAGIHAN TAMBAHAN', [
            'ORDER', 'CUSTOMER', 'ITEM', 'ALASAN', 'NOMINAL', 'BERAT ESTIMASI (GR)', 'BERAT AKTUAL (GR)',
            'SHIPPING ESTIMASI IDR', 'SHIPPING AKTUAL IDR', 'RATE AWAL', 'RATE AKHIR', 'STATUS TAGIHAN', 'CATATAN', 'DIBUAT',
        ], $adjustments->map(fn ($a) => [
            $a->order?->order_number,
            $a->order?->customer?->name,
            $a->order?->items?->pluck('item_name')->join(', '),
            $a->reasonLabel(),
            $a->amount_idr,
            $a->estimated_weight_grams,
            $a->actual_weight_grams,
            $a->estimated_shipping_idr,
            $a->actual_shipping_idr,
            $a->original_rate,
            $a->final_rate,
            strtoupper((string) $a->invoice?->status),
            $a->notes,
            $a->created_at?->translatedFormat('d F Y, H.i'),
        ])->all(), [5, 8, 9]);

        $paymentRows = [];
        foreach ($payments as $payment) {
            foreach ($payment->invoices->whereIn('id', $invoiceIds) as $invoice) {
                $paymentRows[] = [
                    $payment->payment_number,
                    $payment->customer?->name,
                    $payment->bankAccount?->bank_name.' '.$payment->bankAccount?->account_number,
                    $invoice->invoice_number,
                    $invoice->order?->order_number,
                    $payment->amount,
                    $invoice->pivot?->allocated_amount,
                    strtoupper($payment->status),
                    $payment->submitted_at?->translatedFormat('d F Y, H.i'),
                    $payment->verified_at?->translatedFormat('d F Y, H.i'),
                ];
            }
        }
        $this->writeTableSheet($book, 'PEMBAYARAN', [
            'PEMBAYARAN', 'CUSTOMER', 'REKENING TUJUAN', 'INVOICE', 'ORDER', 'TOTAL TRANSFER', 'ALOKASI', 'STATUS', 'DIKIRIM', 'DIVERIFIKASI',
        ], $paymentRows, [6, 7]);

        $importRuns = ImportRun::where('go_group_id', $group->id)->orderBy('created_at')->get();
        $this->writeTableSheet($book, 'ARSIP IMPORT', [
            'FILE ASLI', 'STATUS', 'TOTAL BARIS', 'DIPROSES', 'MULAI', 'SELESAI', 'ERROR',
        ], $importRuns->map(fn ($run) => [
            $run->original_name,
            strtoupper($run->status),
            $run->total_rows,
            $run->processed_rows,
            $run->started_at?->translatedFormat('d F Y, H.i'),
            $run->finished_at?->translatedFormat('d F Y, H.i'),
            $run->error_message,
        ])->all());

        return $this->saveWorkbook($book, 'GBUKR-GO-' . Str::slug($group->name) . '-' . now()->format('Ymd-His') . '.xlsx');
    }

    public function exportFull(): array
    {
        $book = $this->newWorkbook('Backup Lengkap GBUKR');
        $this->removeDefaultSheet($book);

        $this->writeKeyValueSheet($book, 'RINGKASAN', [
            ['Backup', 'GBUKPOP x KRJASTIP'],
            ['Diexport', now()->translatedFormat('d F Y, H.i')],
            ['Customer', User::where('role', 'customer')->count()],
            ['GO', GoGroup::count()],
            ['Produk', Product::count()],
            ['Order', Order::count()],
            ['Tagihan', Invoice::count()],
            ['Pembayaran', Payment::count()],
            ['Batch', Batch::count()],
            ['Tracking', Shipment::count()],
        ]);

        $countries = Country::orderBy('name')->get();
        $this->writeTableSheet($book, 'MATA UANG', ['KODE NEGARA', 'NEGARA', 'KODE MATA UANG', 'SIMBOL', 'RATE', 'FEE ADMIN IDR', 'STATUS'], $countries->map(fn ($c) => [
            $c->code, $c->name, $c->currency_code, $c->moneySymbol(), $c->rate, $c->admin_fee_idr, $c->active ? 'Aktif' : 'Nonaktif',
        ])->all(), [6]);

        $groups = GoGroup::orderBy('name')->get();
        $this->writeTableSheet($book, 'GO', ['ID', 'NAMA', 'STATUS', 'CATATAN'], $groups->map(fn ($g) => [
            $g->id, $g->name, $g->status, $g->notes,
        ])->all());

        $banks = BankAccount::orderBy('bank_name')->get();
        $this->writeTableSheet($book, 'REKENING', ['BANK','NOMOR','NAMA PEMILIK','STATUS','INSTRUKSI'], $banks->map(fn($b)=>[$b->bank_name,$b->account_number,$b->account_name,$b->active?'Aktif':'Nonaktif',$b->instructions])->all());

        $statuses = StatusDefinition::orderBy('sort_order')->get();
        $this->writeTableSheet($book, 'STATUS SISTEM', ['KODE','LABEL','WARNA','URUTAN','AKTIF','SYSTEM'], $statuses->map(fn($st)=>[$st->code,$st->label,$st->color,$st->sort_order,$st->active?'Ya':'Tidak',$st->system?'Ya':'Tidak'])->all());

        $customers = User::with('customerProfile')->where('role', 'customer')->orderBy('name')->get();
        $this->writeTableSheet($book, 'CUSTOMER', [
            'ID', 'NAMA', 'EMAIL', 'USERNAME', 'WHATSAPP', 'LINE', 'CHANNEL', 'AKTIF', 'AKSES ADMIN',
        ], $customers->map(fn ($u) => [
            $u->id,
            $u->name,
            str_ends_with($u->email, '@placeholder.local') ? '' : $u->email,
            $u->customerProfile?->username,
            $u->customerProfile?->whatsapp,
            $u->customerProfile?->line_id,
            $u->customerProfile?->source_channel,
            $u->active ? 'Ya' : 'Tidak',
            $u->admin_enabled ? 'Ya' : 'Tidak',
        ])->all());

        $batches = Batch::with(['goGroup', 'country', 'warehouse'])->orderBy('created_at')->get();
        $this->writeTableSheet($book, 'BATCH', [
            'KODE', 'NAMA', 'GO', 'NEGARA', 'WAREHOUSE', 'TRACKING', 'STATUS', 'ARRIVED GBU/KRJASTIP', 'CATATAN',
        ], $batches->map(fn ($b) => [
            $b->code,
            $b->name,
            $b->goGroup?->name,
            $b->country?->name,
            $b->warehouse?->code,
            $b->tracking_number,
            OrderStatusService::label($b->status),
            $b->arrived_gbu_at?->translatedFormat('d F Y, H.i'),
            $b->description,
        ])->all());

        $orders = Order::with(['customer', 'goGroup', 'batch', 'items'])->orderBy('created_at')->get();
        $this->writeTableSheet($book, 'ORDER', [
            'ORDER', 'CUSTOMER', 'GO', 'SUMBER', 'BATCH', 'STATUS', 'RATE SNAPSHOT', 'MATA UANG', 'DIBUAT', 'CATATAN',
        ], $orders->map(fn ($o) => [
            $o->order_number,
            $o->customer?->name,
            $o->goGroup?->name ?: $o->batch?->goGroup?->name,
            strtoupper($o->source_type),
            $o->batch?->code,
            OrderStatusService::label($o->status),
            $o->rate_snapshot,
            $o->currency_code,
            $o->created_at?->translatedFormat('d F Y, H.i'),
            $o->notes,
        ])->all());

        $itemRows = [];
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $itemRows[] = [
                    $order->order_number,
                    $item->item_name,
                    $item->details,
                    $item->description_type,
                    $item->qty,
                    $item->unit_price_foreign,
                    $item->unit_price_idr,
                    $item->subtotal_idr,
                ];
            }
        }
        $this->writeTableSheet($book, 'ORDER ITEM', [
            'ORDER', 'ITEM', 'DETAILS', 'KETERANGAN', 'QTY', 'HARGA ASAL', 'HARGA IDR', 'SUBTOTAL IDR',
        ], $itemRows, [7, 8]);

        $invoices = Invoice::with(['customer', 'order'])->orderBy('created_at')->get();
        $this->writeTableSheet($book, 'TAGIHAN', [
            'INVOICE', 'CUSTOMER', 'ORDER', 'JENIS', 'NOMINAL', 'DIBAYAR', 'DENDA', 'SISA', 'DEADLINE', 'STATUS', 'CATATAN',
        ], $invoices->map(fn ($i) => [
            $i->invoice_number,
            $i->customer?->name,
            $i->order?->order_number,
            strtoupper($i->type),
            $i->amount,
            $i->paid_amount,
            $i->penalty_amount,
            $i->outstanding(),
            $i->deadline_at?->translatedFormat('d F Y, H.i'),
            strtoupper($i->status),
            $i->notes,
        ])->all(), [5, 6, 7, 8]);

        $adjustments = OrderAdjustment::with(['order.customer', 'invoice'])->orderBy('created_at')->get();
        $this->writeTableSheet($book, 'TAGIHAN TAMBAHAN', [
            'ORDER', 'CUSTOMER', 'ALASAN', 'NOMINAL', 'BERAT ESTIMASI (GR)', 'BERAT AKTUAL (GR)', 'SHIPPING ESTIMASI IDR', 'SHIPPING AKTUAL IDR', 'RATE AWAL', 'RATE AKHIR', 'INVOICE', 'CATATAN',
        ], $adjustments->map(fn ($a) => [
            $a->order?->order_number,
            $a->order?->customer?->name,
            $a->reasonLabel(),
            $a->amount_idr,
            $a->estimated_weight_grams,
            $a->actual_weight_grams,
            $a->estimated_shipping_idr,
            $a->actual_shipping_idr,
            $a->original_rate,
            $a->final_rate,
            $a->invoice?->invoice_number,
            $a->notes,
        ])->all(), [4]);

        $payments = Payment::with(['customer', 'invoices.order', 'bankAccount'])->orderBy('submitted_at')->get();
        $paymentRows = [];
        foreach ($payments as $payment) {
            if ($payment->invoices->isEmpty()) {
                $paymentRows[] = [
                    $payment->payment_number, $payment->customer?->name, $payment->bankAccount?->bank_name.' '.$payment->bankAccount?->account_number, '', '', $payment->amount, '', strtoupper($payment->status),
                    $payment->submitted_at?->translatedFormat('d F Y, H.i'), $payment->verified_at?->translatedFormat('d F Y, H.i'),
                ];
                continue;
            }
            foreach ($payment->invoices as $invoice) {
                $paymentRows[] = [
                    $payment->payment_number,
                    $payment->customer?->name,
                    $payment->bankAccount?->bank_name.' '.$payment->bankAccount?->account_number,
                    $invoice->invoice_number,
                    $invoice->order?->order_number,
                    $payment->amount,
                    $invoice->pivot?->allocated_amount,
                    strtoupper($payment->status),
                    $payment->submitted_at?->translatedFormat('d F Y, H.i'),
                    $payment->verified_at?->translatedFormat('d F Y, H.i'),
                ];
            }
        }
        $this->writeTableSheet($book, 'PEMBAYARAN', [
            'PEMBAYARAN', 'CUSTOMER', 'REKENING TUJUAN', 'INVOICE', 'ORDER', 'TOTAL TRANSFER', 'ALOKASI', 'STATUS', 'DIKIRIM', 'DIVERIFIKASI',
        ], $paymentRows, [6, 7]);

        $proofs = PaymentProof::with(['payment.customer'])->orderBy('created_at')->get();
        $this->writeTableSheet($book, 'BUKTI PEMBAYARAN', [
            'PEMBAYARAN', 'CUSTOMER', 'NAMA FILE', 'TIPE FILE', 'UKURAN BYTE', 'DIUPLOAD',
        ], $proofs->map(fn ($proof) => [
            $proof->payment?->payment_number,
            $proof->payment?->customer?->name,
            $proof->original_name,
            $proof->mime_type,
            $proof->size,
            $proof->created_at?->translatedFormat('d F Y, H.i'),
        ])->all());

        $importRuns = ImportRun::with('goGroup')->orderBy('created_at')->get();
        $this->writeTableSheet($book, 'ARSIP IMPORT', [
            'GO', 'FILE ASLI', 'STATUS', 'TOTAL BARIS', 'DIPROSES', 'MULAI', 'SELESAI', 'ERROR',
        ], $importRuns->map(fn ($run) => [
            $run->goGroup?->name,
            $run->original_name,
            strtoupper($run->status),
            $run->total_rows,
            $run->processed_rows,
            $run->started_at?->translatedFormat('d F Y, H.i'),
            $run->finished_at?->translatedFormat('d F Y, H.i'),
            $run->error_message,
        ])->all());

        $shipments = Shipment::with('country')->orderBy('created_at')->get();
        $this->writeTableSheet($book, 'TRACKING', [
            'SUMBER', 'REF', 'ITEM', 'KETERANGAN', 'INFO', 'QTY', 'NEGARA', 'TRACKING NUMBER', 'STATUS', 'PUBLIK',
        ], $shipments->map(fn ($s) => [
            strtoupper($s->source_type),
            $s->reference,
            $s->item_details,
            $s->description_type,
            $s->info,
            $s->qty,
            $s->country?->name,
            $s->tracking_number,
            $s->status,
            $s->visible_publicly ? 'Ya' : 'Tidak',
        ])->all());

        $products = Product::with(['country', 'variants', 'preorder'])->orderBy('name')->get();
        $productRows = [];
        foreach ($products as $product) {
            if ($product->variants->isEmpty()) {
                $productRows[] = [
                    $product->name, strtoupper($product->type), $product->country?->name, $product->country?->moneySymbol(), $product->country?->rate,
                    $product->item_fee_idr, $product->free_shipping ? 'Ya' : 'Tidak', $product->taxLabel(),
                    $product->apply_fansign ? 'Ya' : 'Tidak', $product->location_note, $product->event_date?->translatedFormat('d F Y'),
                    '', '', '', '', '', '', '', $product->active ? 'Aktif' : 'Nonaktif',
                ];
                continue;
            }
            foreach ($product->variants as $variant) {
                $productRows[] = [
                    $product->name,
                    strtoupper($product->type),
                    $product->country?->name,
                    $product->country?->moneySymbol(),
                    $product->country?->rate,
                    $product->item_fee_idr,
                    $product->free_shipping ? 'Ya' : 'Tidak',
                    $product->taxLabel(),
                    $product->apply_fansign ? 'Ya' : 'Tidak',
                    $product->location_note,
                    $product->event_date?->translatedFormat('d F Y'),
                    $variant->source_label,
                    $variant->name,
                    $variant->details,
                    $variant->estimated_weight_grams,
                    $variant->price_foreign,
                    $variant->price_idr,
                    $variant->dp_amount_idr,
                    $variant->active ? 'Aktif' : 'Nonaktif',
                ];
            }
        }
        $this->writeTableSheet($book, 'PRODUK', [
            'PRODUK', 'JENIS', 'NEGARA', 'SIMBOL', 'RATE AKTIF', 'FEE/BARANG IDR', 'FREE SHIPPING', 'STATUS TAX', 'APPLY FANSIGN',
            'LOKASI', 'TANGGAL', 'SUMBER WEBSITE', 'VARIASI', 'DETAIL', 'EST BERAT (GR)', 'HARGA ASAL', 'PRICELIST IDR', 'DP IDR', 'STATUS',
        ], $productRows, [6, 17, 18]);

        $warehouses = Warehouse::with('country')->orderBy('code')->get();
        $this->writeTableSheet($book, 'WAREHOUSE', ['KODE', 'NEGARA', 'NAMA', 'STATUS', 'CATATAN'], $warehouses->map(fn ($w) => [
            $w->code, $w->country?->name, $w->name, $w->active ? 'Aktif' : 'Nonaktif', $w->notes,
        ])->all());

        return $this->saveWorkbook($book, 'GBUKR-BACKUP-LENGKAP-' . now()->format('Ymd-His') . '.xlsx');
    }

    private function newWorkbook(string $title): Spreadsheet
    {
        $book = new Spreadsheet();
        $book->getProperties()
            ->setCreator('GBUKR')
            ->setTitle($title)
            ->setSubject('Backup data GBUKPOP x KRJASTIP');
        return $book;
    }

    private function removeDefaultSheet(Spreadsheet $book): void
    {
        if ($book->getSheetCount() === 1 && $book->getActiveSheet()->getHighestDataRow() === 1) {
            $book->removeSheetByIndex(0);
        }
    }

    private function writeKeyValueSheet(Spreadsheet $book, string $name, array $rows): void
    {
        $sheet = $book->createSheet();
        $sheet->setTitle($this->uniqueSheetTitle($book, $name, $sheet));
        $sheet->setCellValue('A1', 'GBUKPOP x KRJASTIP');
        $sheet->setCellValue('A2', 'GBUKR Data Export');
        $sheet->mergeCells('A1:B1');
        $sheet->mergeCells('A2:B2');
        $sheet->getStyle('A1:B2')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $sheet->getStyle('A1:B2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2D63D7');
        $rowIndex = 4;
        foreach ($rows as $row) {
            $sheet->fromArray($row, null, 'A' . $rowIndex);
            $rowIndex++;
        }
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(42);
        $sheet->getStyle('A4:A' . max(4, $rowIndex - 1))->getFont()->setBold(true);
    }

    private function writeTableSheet(Spreadsheet $book, string $name, array $headers, array $rows, array $moneyColumns = []): void
    {
        $sheet = $book->createSheet();
        $sheet->setTitle($this->uniqueSheetTitle($book, $name, $sheet));
        $sheet->fromArray($headers, null, 'A1');
        if ($rows !== []) {
            $sheet->fromArray($rows, null, 'A2');
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $lastRow = max(1, count($rows) + 1);
        $headerRange = 'A1:' . $lastColumn . '1';
        $dataRange = 'A1:' . $lastColumn . $lastRow;

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2D63D7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFDDE3F1']]],
        ]);
        $sheet->getStyle($dataRange)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($headerRange);

        for ($column = 1; $column <= count($headers); $column++) {
            $letter = Coordinate::stringFromColumnIndex($column);
            $header = mb_strtoupper((string) $headers[$column - 1]);
            $width = 18;
            if (str_contains($header, 'ITEM') || str_contains($header, 'DETAIL') || str_contains($header, 'CATATAN') || str_contains($header, 'INFO')) {
                $width = 32;
            } elseif (str_contains($header, 'NAMA') || str_contains($header, 'CUSTOMER') || str_contains($header, 'TRACKING')) {
                $width = 24;
            } elseif (str_contains($header, 'STATUS') || str_contains($header, 'QTY')) {
                $width = 14;
            }
            $sheet->getColumnDimension($letter)->setWidth($width);
        }

        foreach ($moneyColumns as $columnNumber) {
            $letter = Coordinate::stringFromColumnIndex($columnNumber);
            $sheet->getStyle($letter . '2:' . $letter . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
        }
    }

    private function uniqueSheetTitle(Spreadsheet $book, string $requested, Worksheet $current): string
    {
        $base = Str::limit(preg_replace('~[\\\\/?*\[\]:]~', '-', $requested), 31, '');
        $candidate = $base;
        $index = 2;
        while (($existing = $book->getSheetByName($candidate)) && $existing !== $current) {
            $suffix = '-' . $index++;
            $candidate = Str::limit($base, 31 - strlen($suffix), '') . $suffix;
        }
        return $candidate;
    }

    private function saveWorkbook(Spreadsheet $book, string $filename): array
    {
        $disk = Storage::disk('local');
        if (!$disk->exists('exports')) {
            $disk->makeDirectory('exports');
        }

        $storedPath = 'exports/' . Str::uuid() . '.xlsx';
        $absolutePath = $disk->path($storedPath);
        (new Xlsx($book))->save($absolutePath);
        $book->disconnectWorksheets();

        return [
            'path' => $absolutePath,
            'filename' => $filename,
        ];
    }
}
