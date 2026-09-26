<?php

namespace App\Services;

use App\Models\{Batch, Country, CustomerProfile, GoGroup, Invoice, Order, OrderAdjustment, OrderItem, Shipment, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LegacyImportService
{
    private const BILLING_SHEETS = [
        'TAGIHAN KR' => ['country' => 'KR', 'source' => 'mixed'],
        'TAGIHAN CH' => ['country' => 'CH', 'source' => 'mixed'],
        'TAGIHAN JP' => ['country' => 'JP', 'source' => 'mixed'],
        'TAGIHAN JPN' => ['country' => 'JP', 'source' => 'mixed'],
        'TAGIHAN THAI' => ['country' => 'TH', 'source' => 'mixed'],
        'TAGIHAN PH' => ['country' => 'PH', 'source' => 'mixed'],
        'TAGIHAN MY' => ['country' => 'MY', 'source' => 'mixed'],
        'TAGIHAN SG' => ['country' => 'SG', 'source' => 'mixed'],
        'TAGIHAN TW' => ['country' => 'TW', 'source' => 'mixed'],
        'TAGIHAN USA' => ['country' => 'US', 'source' => 'mixed'],
        'TAGIHAN INA' => ['country' => 'ID', 'source' => 'mixed'],
        'TAGIHAN PO' => ['country' => null, 'source' => 'po'],
    ];

    private array $customerCache = [];
    private array $countryCache = [];
    private array $batchCache = [];
    private array $touchedBatchIds = [];

    public function __construct(private readonly BatchTrackingService $batchTracking)
    {
    }

    public function preview(string $path): array
    {
        $book = $this->loadWorkbook($path);
        $sheets = [];
        $recognizedCount = 0;
        $estimatedRows = 0;

        foreach ($book->getWorksheetIterator() as $sheet) {
            $title = trim($sheet->getTitle());
            $normalized = $this->normalizeSheetTitle($title);
            $isStatus = $normalized === 'STATUS BARANG';
            $billing = self::BILLING_SHEETS[$normalized] ?? null;
            $recognized = $isStatus || $billing !== null;

            if ($recognized) {
                $recognizedCount++;
                $estimatedRows += max(0, $sheet->getHighestDataRow() - 2);
            }

            $sheets[] = [
                'sheet' => $title,
                'rows' => $sheet->getHighestDataRow(),
                'columns' => $sheet->getHighestDataColumn(),
                'recognized' => $recognized,
                'mode' => $isStatus ? 'Tracking / status' : ($billing ? 'Tagihan' : 'Tidak diimport'),
            ];
        }

        return [
            'sheets' => $sheets,
            'recognized' => $recognizedCount > 0,
            'recognized_count' => $recognizedCount,
            'estimated_rows' => $estimatedRows,
        ];
    }

    public function import(
        string $path,
        int $goGroupId,
        string $sourceName,
        ?callable $progress = null
    ): array {
        $group = GoGroup::findOrFail($goGroupId);
        $book = $this->loadWorkbook($path, true);
        $this->customerCache = [];
        $this->countryCache = [];
        $this->batchCache = [];
        $this->touchedBatchIds = [];

        $summary = [
            'customers' => 0,
            'orders' => 0,
            'invoices' => 0,
            'adjustments' => 0,
            'shipments' => 0,
            'skipped' => 0,
        ];

        $totalRows = 0;
        foreach ($book->getWorksheetIterator() as $sheet) {
            $normalized = $this->normalizeSheetTitle($sheet->getTitle());
            if ($normalized === 'STATUS BARANG' || isset(self::BILLING_SHEETS[$normalized])) {
                $totalRows += max(0, $sheet->getHighestDataRow() - 2);
            }
        }

        $processedRows = 0;
        $tick = function (int $count = 1) use (&$processedRows, $totalRows, $progress): void {
            $processedRows += $count;
            if ($progress && ($processedRows % 20 === 0 || $processedRows >= $totalRows)) {
                $progress(min($processedRows, $totalRows), $totalRows);
            }
        };

        foreach ($book->getWorksheetIterator() as $sheet) {
            $normalized = $this->normalizeSheetTitle($sheet->getTitle());
            $definition = self::BILLING_SHEETS[$normalized] ?? null;

            if (!$definition) {
                continue;
            }

            $this->importBillingSheet(
                $sheet,
                $definition,
                $group,
                $sourceName,
                $summary,
                $tick
            );
        }

        $this->importStatusSheet($book, $group, $summary, $tick);

        if ($progress) {
            $progress($totalRows, $totalRows);
        }

        if ($this->touchedBatchIds !== []) {
            Batch::with(['country', 'orders.items'])
                ->whereIn('id', array_values(array_unique($this->touchedBatchIds)))
                ->chunkById(100, function ($batches) {
                    foreach ($batches as $batch) {
                        $this->batchTracking->sync($batch);
                    }
                });
        }

        return $summary;
    }

    private function importBillingSheet(
        Worksheet $sheet,
        array $definition,
        GoGroup $group,
        string $sourceName,
        array &$summary,
        callable $tick
    ): void {
        $country = $definition['country'] ? $this->country($definition['country']) : null;
        if ($definition['country'] && !$country) {
            for ($row = 3; $row <= $sheet->getHighestDataRow(); $row++) {
                $summary['skipped']++;
                $tick();
            }
            return;
        }

        $schema = $this->detectBillingSchema($sheet);
        $lastBatch = null;
        $sheetName = $this->normalizeSheetTitle($sheet->getTitle());

        for ($row = 3; $row <= $sheet->getHighestDataRow(); $row++) {
            $batchRef = $this->textCell($sheet, "A{$row}");
            $name = $this->textCell($sheet, "B{$row}");
            $item = $this->textCell($sheet, "C{$row}");

            if ($batchRef !== '') {
                $lastBatch = $batchRef;
            }

            if ($name === '' || $item === '') {
                $summary['skipped']++;
                $tick();
                continue;
            }

            $details = $this->textCell($sheet, "D{$row}");
            $qty = max(1, (int) round($this->numericCell($sheet, "E{$row}") ?: 1));

            DB::transaction(function () use (
                &$summary,
                $sheet,
                $row,
                $sheetName,
                $schema,
                $group,
                $sourceName,
                $country,
                $definition,
                $lastBatch,
                $name,
                $item,
                $details,
                $qty
            ) {
                $user = $this->legacyCustomer($name, $group, $summary);
                $forcedPo = $definition['source'] === 'po';
                $isPo = $forcedPo || $this->isPoReference((string) $lastBatch);
                $batch = (!$isPo && $country)
                    ? $this->legacyBatch($country, (string) ($lastBatch ?: 'Tanpa Batch'), $group)
                    : null;

                $countryCode = $country?->code ?: 'PO';
                $fingerprint = substr(sha1(implode('|', [
                    $group->id,
                    mb_strtolower($sourceName),
                    $sheetName,
                    $row,
                    mb_strtolower($name),
                    mb_strtolower($item),
                    (string) $lastBatch,
                ])), 0, 12);

                // Compatibility v1.0.7: import lama dapat terhenti di tengah request HTTP tetapi
                // sebagian row sudah tersimpan. Adopsi order lama berdasarkan fingerprint lama
                // agar rerun v1.0.8 tidak menggandakan data parsial tersebut.
                $oldFingerprint = $country
                    ? substr(sha1($sheetName . '|' . $row . '|' . mb_strtolower($name) . '|' . $item . '|' . (string) $lastBatch), 0, 8)
                    : null;
                $oldOrderNumber = ($country && $oldFingerprint)
                    ? 'LEG-' . $country->code . '-' . str_pad((string) $row, 4, '0', STR_PAD_LEFT) . '-' . strtoupper($oldFingerprint)
                    : null;
                $newOrderNumber = 'LEG-' . $countryCode . '-' . strtoupper($fingerprint);
                $orderAttributes = [
                    'customer_id' => $user->id,
                    'go_group_id' => $group->id,
                    'batch_id' => $batch?->id,
                    'source_type' => $isPo ? 'po' : 'batch',
                    'status' => $batch?->status ?: 'ordered',
                    'currency_code' => $country?->currency_code,
                    'notes' => 'Migrasi ' . $sourceName . ' | ' . $sheetName . ' | Ref: ' . ($lastBatch ?: '-'),
                ];

                $order = Order::where('order_number', $newOrderNumber)->first();
                $orderCreated = false;
                if (!$order && $oldOrderNumber) {
                    $order = Order::where('order_number', $oldOrderNumber)->first();
                    if ($order) {
                        $order->update($orderAttributes + ['order_number' => $newOrderNumber]);
                    }
                }
                if (!$order) {
                    $order = Order::create($orderAttributes + ['order_number' => $newOrderNumber]);
                    $orderCreated = true;
                } else {
                    $order->update($orderAttributes);
                }

                OrderItem::updateOrCreate(
                    [
                        'order_id' => $order->id,
                        'item_name' => $item,
                    ],
                    [
                        'details' => $details ?: null,
                        'description_type' => $this->inferDescriptionType($item, $details),
                        'qty' => $qty,
                    ]
                );

                if ($schema === 'shipping_tax') {
                    $this->importShippingTaxInvoice($sheet, $row, $order, $user, $fingerprint, $oldFingerprint, $summary);
                } else {
                    $this->importPaymentInvoice($sheet, $row, $order, $user, $fingerprint, $oldFingerprint, $qty, $summary);
                }

                if ($orderCreated) {
                    $summary['orders']++;
                }
            });

            $tick();
        }
    }

    private function importPaymentInvoice(
        Worksheet $sheet,
        int $row,
        Order $order,
        User $user,
        string $fingerprint,
        ?string $oldFingerprint,
        int $qty,
        array &$summary
    ): void {
        $price = max(0, (int) round($this->numericCell($sheet, "F{$row}")));
        $full = max(0, (int) round($this->numericCell($sheet, "G{$row}")));
        $dp = max(0, (int) round($this->numericCell($sheet, "H{$row}")));
        $cicilan = max(0, (int) round($this->numericCell($sheet, "I{$row}")));
        $increase = max(0, (int) round($this->numericCell($sheet, "J{$row}")));
        $remainingSheet = max(0, (int) round($this->numericCell($sheet, "K{$row}")));
        $statusText = $this->textCell($sheet, "L{$row}");
        $note = $this->textCell($sheet, "M{$row}");

        $paidTotal = max(0, $full + $dp + $cicilan);
        $baseAmount = max($price * $qty, max(0, $paidTotal + $remainingSheet - $increase));
        $doneByText = str_contains(mb_strtoupper($statusText), 'DONE');

        if ($baseAmount > 0) {
            $paidBase = $doneByText ? $baseAmount : min($paidTotal, $baseAmount);
            $baseStatus = $paidBase >= $baseAmount ? 'paid' : ($paidBase > 0 ? 'partial' : 'unpaid');
            [$invoice, $created] = $this->upsertLegacyInvoice(
                'LEG-INV-' . strtoupper($fingerprint),
                $oldFingerprint ? 'LEG-INV-' . strtoupper($oldFingerprint) : null,
                [
                    'customer_id' => $user->id,
                    'order_id' => $order->id,
                    'type' => 'pelunasan',
                    'amount' => $baseAmount,
                    'paid_amount' => $paidBase,
                    'penalty_amount' => 0,
                    'status' => $baseStatus,
                    'notes' => trim('Migrasi spreadsheet. Pembayaran lama — Full: Rp' . number_format($full, 0, ',', '.') . ', DP: Rp' . number_format($dp, 0, ',', '.') . ', Cicilan: Rp' . number_format($cicilan, 0, ',', '.') . '. Sisa pada sheet: Rp' . number_format($remainingSheet, 0, ',', '.') . '. ' . $statusText . ' ' . $note),
                ]
            );

            if ($created) {
                $summary['invoices']++;
            }
        }

        if ($increase > 0) {
            $adjustmentPaid = $doneByText ? $increase : max(0, min($increase, $paidTotal - $baseAmount));
            $adjustmentStatus = $adjustmentPaid >= $increase ? 'paid' : ($adjustmentPaid > 0 ? 'partial' : 'unpaid');

            $adjustmentInvoice = Invoice::updateOrCreate(
                ['invoice_number' => 'LEG-KRG-' . strtoupper($fingerprint)],
                [
                    'customer_id' => $user->id,
                    'order_id' => $order->id,
                    'type' => 'kekurangan',
                    'amount' => $increase,
                    'paid_amount' => $adjustmentPaid,
                    'penalty_amount' => 0,
                    'status' => $adjustmentStatus,
                    'notes' => trim('Kekurangan/kenaikan dari spreadsheet lama. ' . $note),
                ]
            );

            $adjustment = OrderAdjustment::updateOrCreate(
                ['legacy_key' => 'legacy-' . $fingerprint],
                [
                    'order_id' => $order->id,
                    'invoice_id' => $adjustmentInvoice->id,
                    'type' => 'kekurangan',
                    'reason' => 'other',
                    'amount_idr' => $increase,
                    'notes' => $note ?: 'Kenaikan harga dari spreadsheet lama.',
                ]
            );

            if ($adjustmentInvoice->wasRecentlyCreated) {
                $summary['invoices']++;
            }
            if ($adjustment->wasRecentlyCreated) {
                $summary['adjustments']++;
            }
        }
    }

    private function importShippingTaxInvoice(
        Worksheet $sheet,
        int $row,
        Order $order,
        User $user,
        string $fingerprint,
        ?string $oldFingerprint,
        array &$summary
    ): void {
        $shipping = 0;
        foreach (['F', 'G', 'H'] as $column) {
            $shipping += max(0, (int) round($this->numericCell($sheet, $column . $row)));
        }
        $tax = max(0, (int) round($this->numericCell($sheet, "I{$row}")));
        $total = max(0, (int) round($this->numericCell($sheet, "J{$row}")));
        $amount = max($total, $shipping + $tax);
        $statusText = $this->textCell($sheet, "K{$row}");
        $note = $this->textCell($sheet, "L{$row}");
        $done = str_contains(mb_strtoupper($statusText), 'DONE');

        if ($amount <= 0) {
            return;
        }

        [$invoice, $created] = $this->upsertLegacyInvoice(
            'LEG-INV-' . strtoupper($fingerprint),
            $oldFingerprint ? 'LEG-INV-' . strtoupper($oldFingerprint) : null,
            [
                'customer_id' => $user->id,
                'order_id' => $order->id,
                'type' => 'pelunasan',
                'amount' => $amount,
                'paid_amount' => $done ? $amount : 0,
                'penalty_amount' => 0,
                'status' => $done ? 'paid' : 'unpaid',
                'notes' => trim('Migrasi shipping/tax lama. Shipping: Rp' . number_format($shipping, 0, ',', '.') . ', Tax: Rp' . number_format($tax, 0, ',', '.') . '. ' . $statusText . ' ' . $note),
            ]
        );

        if ($created) {
            $summary['invoices']++;
        }
    }

    private function upsertLegacyInvoice(string $newNumber, ?string $oldNumber, array $attributes): array
    {
        $invoice = Invoice::where('invoice_number', $newNumber)->first();
        $created = false;

        if (!$invoice && $oldNumber) {
            $invoice = Invoice::where('invoice_number', $oldNumber)->first();
            if ($invoice) {
                $invoice->update($attributes + ['invoice_number' => $newNumber]);
            }
        }

        if (!$invoice) {
            $invoice = Invoice::create($attributes + ['invoice_number' => $newNumber]);
            $created = true;
        } else {
            $invoice->update($attributes);
        }

        return [$invoice, $created];
    }

    private function importStatusSheet(Spreadsheet $book, GoGroup $group, array &$summary, callable $tick): void
    {
        $sheet = null;
        foreach ($book->getWorksheetIterator() as $candidate) {
            if ($this->normalizeSheetTitle($candidate->getTitle()) === 'STATUS BARANG') {
                $sheet = $candidate;
                break;
            }
        }
        if (!$sheet) {
            return;
        }

        $map = [
            'F' => 'CH',
            'G' => 'JP',
            'H' => 'KR',
            'I' => 'PH',
            'J' => 'TH',
            'K' => 'MY',
            'L' => 'TW',
            'M' => 'SG',
            'N' => 'ID',
            'O' => 'US',
        ];

        $trackingColumn = $this->findHeaderColumn($sheet, ['TRACKING NUMBER', 'TRACKING'], 20) ?: 'P';
        $statusColumn = $this->findHeaderColumn($sheet, ['STATUS'], 20) ?: 'Q';
        $lastRef = null;
        $groups = [];

        for ($row = 3; $row <= $sheet->getHighestDataRow(); $row++) {
            $ref = $this->textCell($sheet, "A{$row}");
            if ($ref !== '' && !$this->looksLikeStatusLegend($ref)) {
                $lastRef = $ref;
            }

            $item = $this->textCell($sheet, "B{$row}");
            if ($item === '') {
                $tick();
                continue;
            }

            $description = $this->textCell($sheet, "C{$row}") ?: 'Barang';
            $info = $this->textCell($sheet, "D{$row}");
            $qty = max(1, (int) round($this->numericCell($sheet, "E{$row}") ?: 1));
            $tracking = $this->textCell($sheet, $trackingColumn . $row);
            $statusText = $this->textCell($sheet, $statusColumn . $row) ?: 'Ordered';

            $country = null;
            foreach ($map as $column => $code) {
                if ($this->textCell($sheet, $column . $row) !== '') {
                    $country = $this->country($code);
                    if ($country) {
                        break;
                    }
                }
            }

            if (!$country) {
                $summary['skipped']++;
                $tick();
                continue;
            }

            $isPo = $this->isPoReference((string) $lastRef);
            $batch = $isPo ? null : $this->legacyBatch($country, (string) ($lastRef ?: 'Tanpa Batch'), $group);
            $reference = $batch?->code ?: 'PO-G' . $group->id . '-' . $country->code;
            $key = ($isPo ? 'po' : 'batch') . '|' . ($batch?->id ?: 0) . '|' . $country->id . '|' . $reference;

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'source_type' => $isPo ? 'po' : 'batch',
                    'source_id' => $batch?->id,
                    'reference' => $reference,
                    'country_id' => $country->id,
                    'items' => [],
                    'types' => [],
                    'infos' => [],
                    'qty' => 0,
                    'tracking_number' => null,
                    'status' => 'Ordered',
                    'batch' => $batch,
                ];
            }

            $groups[$key]['items'][] = $item;
            $groups[$key]['types'][] = $description;
            if ($info !== '') {
                $groups[$key]['infos'][] = $info;
            }
            $groups[$key]['qty'] += $qty;
            if ($tracking !== '') {
                $groups[$key]['tracking_number'] = $tracking;
            }
            if ($statusText !== '') {
                $groups[$key]['status'] = $statusText;
            }

            $tick();
        }

        foreach ($groups as $row) {
            if ($row['source_type'] === 'po') {
                $existingNew = Shipment::where('source_type', 'po')
                    ->whereNull('source_id')
                    ->where('reference', $row['reference'])
                    ->first();
                if (!$existingNew) {
                    $oldPoReference = 'PO-' . optional(Country::find($row['country_id']))->code;
                    $oldPoShipment = Shipment::where('source_type', 'po')
                        ->whereNull('source_id')
                        ->where('reference', $oldPoReference)
                        ->first();
                    if ($oldPoShipment) {
                        $oldPoShipment->update(['reference' => $row['reference']]);
                    }
                }
            }

            $shipment = Shipment::updateOrCreate(
                [
                    'source_type' => $row['source_type'],
                    'source_id' => $row['source_id'],
                    'reference' => $row['reference'],
                ],
                [
                    'item_details' => Str::limit(implode(', ', array_values(array_unique($row['items']))), 180, ''),
                    'description_type' => Str::limit(implode(', ', array_values(array_unique($row['types']))), 100, ''),
                    'info' => ($infos = array_values(array_unique($row['infos']))) ? Str::limit(implode(' · ', $infos), 500, '') : null,
                    'qty' => $row['qty'] ?: null,
                    'country_id' => $row['country_id'],
                    'tracking_number' => $row['tracking_number'],
                    'status' => $row['status'],
                    'visible_publicly' => true,
                ]
            );

            if ($shipment->wasRecentlyCreated) {
                $summary['shipments']++;
            }

            if ($row['batch']) {
                $normalized = $this->normalizeStatus($row['status']);
                $row['batch']->update([
                    'tracking_number' => $row['tracking_number'] ?: $row['batch']->tracking_number,
                    'status' => $normalized ?: $row['batch']->status,
                    'arrived_gbu_at' => $normalized === 'arrived_gbu' && !$row['batch']->arrived_gbu_at
                        ? now()
                        : $row['batch']->arrived_gbu_at,
                ]);
            }
        }
    }

    private function loadWorkbook(string $path, bool $recognizedOnly = false): Spreadsheet
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException('File workbook tidak ditemukan atau tidak dapat dibaca.');
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        if (method_exists($reader, 'setReadEmptyCells')) {
            $reader->setReadEmptyCells(false);
        }

        if ($recognizedOnly && method_exists($reader, 'listWorksheetNames') && method_exists($reader, 'setLoadSheetsOnly')) {
            $recognizedSheets = array_values(array_filter(
                $reader->listWorksheetNames($path),
                function (string $title): bool {
                    $normalized = $this->normalizeSheetTitle($title);
                    return $normalized === 'STATUS BARANG' || isset(self::BILLING_SHEETS[$normalized]);
                }
            ));

            if ($recognizedSheets !== []) {
                $reader->setLoadSheetsOnly($recognizedSheets);
            }
        }

        return $reader->load($path);
    }

    private function legacyCustomer(string $name, GoGroup $group, array &$summary): User
    {
        $normalized = mb_strtolower(trim(preg_replace('/\s+/', ' ', $name)));
        $cacheKey = $group->id . '|' . $normalized;
        if (isset($this->customerCache[$cacheKey])) {
            return $this->customerCache[$cacheKey];
        }

        // Nama dari spreadsheet bukan identitas global yang aman. Scope per GO mencegah dua orang
        // dengan nama tampilan sama dari GO berbeda tergabung otomatis. Owner masih dapat merge nanti.
        $email = 'legacy+' . substr(sha1($cacheKey), 0, 24) . '@placeholder.local';
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => trim($name),
                'role' => 'customer',
                'active' => true,
                'password' => null,
            ]
        );

        if ($user->wasRecentlyCreated) {
            CustomerProfile::create([
                'user_id' => $user->id,
                'legacy_name' => trim($name),
                'source_channel' => 'other',
                'notes' => 'Dibuat melalui migrasi spreadsheet GO ' . $group->name,
            ]);
            $summary['customers']++;
        }

        return $this->customerCache[$cacheKey] = $user;
    }

    private function legacyBatch(Country $country, string $reference, GoGroup $group): Batch
    {
        $reference = trim($reference) ?: 'Tanpa Batch';
        $cacheKey = $group->id . '|' . $country->id . '|' . mb_strtolower($reference);
        if (isset($this->batchCache[$cacheKey])) {
            return $this->batchCache[$cacheKey];
        }

        $clean = strtoupper(preg_replace('/[^A-Z0-9]+/i', '-', $reference));
        $clean = trim($clean, '-') ?: 'UNSPECIFIED';
        $code = Str::limit($country->code . '-LEG-G' . $group->id . '-' . $clean, 80, '');

        $batch = Batch::where('code', $code)->first();

        if (!$batch) {
            // Compatibility: data yang pernah diimport versi lama belum memiliki GO pada kode Batch.
            $legacyCode = Str::limit($country->code . '-LEG-' . $clean, 80, '');
            $batch = Batch::where('code', $legacyCode)->whereNull('go_group_id')->first();
            if ($batch) {
                $batch->update(['go_group_id' => $group->id]);
            }
        }

        if (!$batch) {
            $batch = Batch::create([
                'go_group_id' => $group->id,
                'country_id' => $country->id,
                'code' => $code,
                'name' => 'Legacy Batch ' . $reference,
                'description' => 'Dibuat otomatis dari migrasi spreadsheet GO ' . $group->name . '.',
                'status' => 'ordered',
            ]);
        }

        $this->touchedBatchIds[] = $batch->id;

        return $this->batchCache[$cacheKey] = $batch;
    }

    private function country(string $code): ?Country
    {
        $code = strtoupper($code);
        if (!array_key_exists($code, $this->countryCache)) {
            $this->countryCache[$code] = Country::where('code', $code)->first();
        }

        return $this->countryCache[$code];
    }

    private function detectBillingSchema(Worksheet $sheet): string
    {
        $first = mb_strtoupper($this->textCell($sheet, 'F1') . ' ' . $this->textCell($sheet, 'G1'));
        $second = mb_strtoupper(implode(' ', [
            $this->textCell($sheet, 'F2'),
            $this->textCell($sheet, 'G2'),
            $this->textCell($sheet, 'H2'),
            $this->textCell($sheet, 'I2'),
        ]));

        if (str_contains($first, 'SHIPPING') || str_contains($second, 'AIR CARGO') || str_contains($second, 'EMS') || str_contains($second, 'DHL')) {
            return 'shipping_tax';
        }

        return 'payment';
    }

    private function textCell(Worksheet $sheet, string $coordinate): string
    {
        return trim((string) $sheet->getCell($coordinate)->getFormattedValue());
    }

    private function numericCell(Worksheet $sheet, string $coordinate): float
    {
        $cell = $sheet->getCell($coordinate);
        $value = $cell->getCalculatedValue();
        if (is_numeric($value)) {
            return (float) $value;
        }

        $text = trim((string) $cell->getFormattedValue());
        if ($text === '') {
            return 0.0;
        }

        $negative = str_contains($text, '(') && str_contains($text, ')');
        $clean = preg_replace('/[^0-9,.-]/', '', $text);
        if ($clean === '' || $clean === '-' || $clean === '.') {
            return 0.0;
        }

        if (str_contains($clean, ',') && str_contains($clean, '.')) {
            if (strrpos($clean, ',') > strrpos($clean, '.')) {
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            } else {
                $clean = str_replace(',', '', $clean);
            }
        } elseif (substr_count($clean, ',') === 1 && strlen(substr($clean, strrpos($clean, ',') + 1)) <= 2) {
            $clean = str_replace(',', '.', $clean);
        } else {
            $clean = str_replace([',', '.'], '', $clean);
        }

        $number = is_numeric($clean) ? (float) $clean : 0.0;
        return $negative ? -$number : $number;
    }

    private function normalizeSheetTitle(string $title): string
    {
        return mb_strtoupper(trim(preg_replace('/\s+/', ' ', $title)));
    }

    private function findHeaderColumn(Worksheet $sheet, array $needles, int $maxRows): ?string
    {
        $maxColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        for ($row = 1; $row <= min($maxRows, $sheet->getHighestDataRow()); $row++) {
            for ($column = 1; $column <= $maxColumn; $column++) {
                $coordinate = Coordinate::stringFromColumnIndex($column) . $row;
                $value = mb_strtoupper(trim((string) $sheet->getCell($coordinate)->getFormattedValue()));
                foreach ($needles as $needle) {
                    if ($value === $needle || str_contains($value, $needle)) {
                        return Coordinate::stringFromColumnIndex($column);
                    }
                }
            }
        }

        return null;
    }

    private function isPoReference(string $reference): bool
    {
        $normalized = mb_strtoupper(trim($reference));
        return $normalized === 'PO'
            || $normalized === 'PRE ORDER'
            || str_contains($normalized, 'PRE ORDER (PO)');
    }

    private function looksLikeStatusLegend(string $value): bool
    {
        $normalized = mb_strtolower(trim($value));
        return in_array($normalized, [
            'batch',
            'pre order (po)',
            'ordered',
            'otw/arrived wh (ch/jp/kr/thai)',
            'otw ina (on board)',
            'tax proccess (ba cukai)',
            'otw/arrived wh ina',
            'arrived gbu (ready)',
            'ship to you',
            'done',
        ], true) || str_starts_with($normalized, 'last update:');
    }

    private function inferDescriptionType(string $item, string $details): string
    {
        $text = mb_strtolower($item . ' ' . $details);

        foreach ([
            'photocard' => 'Photocard',
            'pc ' => 'Photocard',
            'album' => 'Album',
            'magazine' => 'Magazine',
            'keyring' => 'Keyring',
            'acrylic' => 'Merchandise',
            'plush' => 'Merchandise',
        ] as $needle => $label) {
            if (str_contains($text, $needle)) {
                return $label;
            }
        }

        return 'Merchandise';
    }

    private function normalizeStatus(string $status): ?string
    {
        $status = mb_strtolower(trim($status));

        return match (true) {
            $status === '' => null,
            str_contains($status, 'arrived gbu'), str_contains($status, 'arrived krjastip') => 'arrived_gbu',
            str_contains($status, 'arrived indo'), str_contains($status, 'arrived wh ina') => 'arrived_indo',
            str_contains($status, 'otw indo'), str_contains($status, 'otw ina'), str_contains($status, 'on board') => 'otw_indo',
            str_contains($status, 'arrived wh'), str_contains($status, 'otw/arrived wh') => 'arrived_wh',
            str_contains($status, 'send to customer'), str_contains($status, 'sent to customer'), str_contains($status, 'ship to you') => 'send_to_customer',
            str_contains($status, 'selesai'), str_contains($status, 'done') => 'completed',
            str_contains($status, 'ordered') => 'ordered',
            default => null,
        };
    }
}
