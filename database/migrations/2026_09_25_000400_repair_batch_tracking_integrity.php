<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $mismatched = DB::table('batches')
            ->join('warehouses', 'batches.warehouse_id', '=', 'warehouses.id')
            ->whereColumn('batches.country_id', '!=', 'warehouses.country_id')
            ->pluck('batches.id');

        if ($mismatched->isNotEmpty()) {
            DB::table('batches')->whereIn('id', $mismatched)->update(['warehouse_id' => null]);
        }

        $labels = [
            'ordered' => 'Ordered',
            'arrived_wh' => 'Arrived WH',
            'otw_indo' => 'OTW Indo',
            'arrived_indo' => 'Arrived Indo',
            'arrived_gbu' => 'Arrived GBU/krjastip',
            'send_to_customer' => 'Send to Customer',
            'completed' => 'Selesai',
            'unclaimed' => 'Unclaimed',
        ];

        DB::table('batches')->orderBy('id')->chunkById(100, function ($batches) use ($labels) {
            foreach ($batches as $batch) {
                $orderIds = DB::table('orders')->where('batch_id', $batch->id)->pluck('id');
                $items = $orderIds->isEmpty()
                    ? collect()
                    : DB::table('order_items')->whereIn('order_id', $orderIds)->get(['item_name', 'description_type']);

                $itemNames = $items->pluck('item_name')->filter()->unique()->values()->all();
                $types = $items->pluck('description_type')->filter()->unique()->values()->all();

                $itemDetails = $this->summarize($itemNames, 180) ?: $batch->name;
                $descriptionType = $this->summarize($types, 100) ?: 'Batch GO';
                $status = $labels[$batch->status] ?? Str::headline((string) $batch->status);

                $existing = DB::table('shipments')
                    ->where('source_type', 'batch')
                    ->where('reference', $batch->code)
                    ->orderBy('id')
                    ->get();

                $payload = [
                    'source_type' => 'batch',
                    'source_id' => $batch->id,
                    'reference' => $batch->code,
                    'item_details' => $itemDetails,
                    'description_type' => $descriptionType,
                    'info' => $batch->description,
                    'country_id' => $batch->country_id,
                    'tracking_number' => $batch->tracking_number,
                    'status' => $status,
                    'visible_publicly' => true,
                    'updated_at' => now(),
                ];

                if ($existing->isEmpty()) {
                    DB::table('shipments')->insert($payload + ['created_at' => now()]);
                } else {
                    DB::table('shipments')->where('id', $existing->first()->id)->update($payload);
                    $extraIds = $existing->skip(1)->pluck('id');
                    if ($extraIds->isNotEmpty()) {
                        DB::table('shipments')->whereIn('id', $extraIds)->delete();
                    }
                }
            }
        }, 'id');

        Schema::table('shipments', function (Blueprint $table) {
            $table->index(['source_type', 'source_id'], 'shipments_source_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex('shipments_source_lookup_index');
        });
    }

    private function summarize(array $values, int $maxLength): string
    {
        $values = array_values(array_filter(array_map(fn ($value) => trim((string) $value), $values)));

        if ($values === []) {
            return '';
        }

        if (count($values) <= 2) {
            return Str::limit(implode(', ', $values), $maxLength, '');
        }

        return Str::limit($values[0] . ', ' . $values[1] . ' +' . (count($values) - 2) . ' lainnya', $maxLength, '');
    }
};
