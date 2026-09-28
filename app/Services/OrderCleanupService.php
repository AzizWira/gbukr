<?php

namespace App\Services;

use App\Models\{Order, StatusHistory};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderCleanupService
{
    public function blocker(Order $order): ?string
    {
        $order->loadMissing('invoices.payments');

        if ($order->invoices->contains(fn ($invoice) => (int) $invoice->paid_amount > 0 || $invoice->payments->isNotEmpty())) {
            return 'Order sudah memiliki riwayat pembayaran. Histori finansial tidak boleh dihapus permanen.';
        }

        if ($order->source_type !== 'batch' && $order->status !== 'ordered') {
            return 'Order non-Batch yang sudah bergerak dari status Ordered tidak boleh dihapus permanen. Histori operasional harus dipertahankan.';
        }

        return null;
    }

    public function canDelete(Order $order): bool
    {
        return $this->blocker($order) === null;
    }

    /**
     * Cleanup satu order dari konteks Batch.
     *
     * - Tanpa histori finansial: hard delete karena aman.
     * - Dengan histori finansial: hanya lepas relasi Batch. Order, invoice, dan payment tetap utuh.
     *
     * @return string deleted|detached
     */
    public function cleanupFromBatch(Order $order): string
    {
        if ($this->canDelete($order)) {
            $this->delete($order);
            return 'deleted';
        }

        $this->detachFromBatch($order);
        return 'detached';
    }

    public function detachFromBatch(Order $order): void
    {
        if (!$order->batch_id) {
            throw ValidationException::withMessages([
                'order' => 'Order ini sudah tidak terhubung ke Batch.',
            ]);
        }

        DB::transaction(function () use ($order) {
            $order->loadMissing('batch');
            $batchCode = $order->batch?->code ?: ('#' . $order->batch_id);
            $note = trim((string) $order->notes);
            $audit = 'Dikeluarkan dari Batch ' . $batchCode . ' oleh Owner. Histori tagihan/pembayaran tetap dipertahankan.';

            $order->update([
                'batch_id' => null,
                'notes' => $note !== '' ? $note . "\n" . $audit : $audit,
            ]);
        });
    }

    public function delete(Order $order): void
    {
        if ($reason = $this->blocker($order)) {
            throw ValidationException::withMessages(['order' => $reason]);
        }

        DB::transaction(function () use ($order) {
            $order->loadMissing(['adjustments', 'invoices', 'items.variant']);
            if ($order->source_type === 'ready') {
                foreach ($order->items as $item) {
                    if ($item->variant && $item->variant->stock !== null) {
                        $item->variant->increment('stock', (int) $item->qty);
                    }
                }
            }
            $order->adjustments()->delete();
            $order->invoices()->delete();
            StatusHistory::where('entity_type', 'order')->where('entity_id', $order->id)->delete();
            $order->delete();
        });
    }
}
