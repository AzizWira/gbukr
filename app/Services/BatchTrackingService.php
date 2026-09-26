<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Shipment;
use Illuminate\Support\Str;

class BatchTrackingService
{
    public function sync(Batch $batch): Shipment
    {
        $batch->loadMissing(['country', 'orders.items']);
        $existing = Shipment::where('source_type', 'batch')
            ->where('source_id', $batch->id)
            ->first();

        $items = $batch->orders
            ->flatMap(fn ($order) => $order->items)
            ->pluck('item_name')
            ->filter()
            ->unique()
            ->values();

        $types = $batch->orders
            ->flatMap(fn ($order) => $order->items)
            ->pluck('description_type')
            ->filter()
            ->unique()
            ->values();

        // Batch legacy dapat mempunyai STATUS BARANG yang lebih lengkap daripada baris TAGIHAN.
        // Pertahankan hasil agregasi dari sheet status agar sinkronisasi order tidak membuang detail lama.
        $isLegacy = str_contains($batch->code, '-LEG-') || str_starts_with($batch->name, 'Legacy Batch');

        $itemDetails = ($isLegacy && $existing?->item_details)
            ? $existing->item_details
            : ($items->isEmpty()
                ? ($existing?->item_details ?: $batch->name)
                : $this->summarize($items->all(), 180));

        $descriptionType = ($isLegacy && $existing?->description_type)
            ? $existing->description_type
            : ($types->isEmpty()
                ? ($existing?->description_type ?: 'Batch GO')
                : $this->summarize($types->all(), 100));

        $orderQty = $batch->orders->flatMap(fn ($order) => $order->items)->sum('qty');
        $qty = ($isLegacy && $existing?->qty)
            ? $existing->qty
            : ($orderQty > 0 ? $orderQty : $existing?->qty);

        $shipment = Shipment::updateOrCreate(
            [
                'source_type' => 'batch',
                'source_id' => $batch->id,
            ],
            [
                'reference' => $batch->code,
                'item_details' => $itemDetails,
                'description_type' => $descriptionType,
                'info' => $existing?->info ?: $batch->description,
                'qty' => $qty,
                'country_id' => $batch->country_id,
                'tracking_number' => $batch->tracking_number ?: $existing?->tracking_number,
                'status' => $batch->status,
                'visible_publicly' => $existing?->visible_publicly ?? true,
            ]
        );

        Shipment::where('source_type', 'batch')
            ->where('reference', $batch->code)
            ->whereKeyNot($shipment->id)
            ->delete();

        return $shipment;
    }

    private function summarize(array $values, int $maxLength): string
    {
        $values = array_values(array_filter(array_map(
            fn ($value) => trim((string) $value),
            $values
        )));

        if (count($values) <= 2) {
            return Str::limit(implode(', ', $values), $maxLength, '');
        }

        return Str::limit(
            $values[0] . ', ' . $values[1] . ' +' . (count($values) - 2) . ' lainnya',
            $maxLength,
            ''
        );
    }
}
