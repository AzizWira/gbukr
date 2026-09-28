<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Schema;

class OrderImportSnapshotService
{
    public function snapshot(Order $order): array
    {
        $order->loadMissing(['customer.customerProfile','items','invoices.payments']);
        return [
            'customer' => [
                'name' => (string) $order->customer?->name,
                'email' => (string) $order->customer?->email,
                'legacy_name' => (string) ($order->customer?->customerProfile?->legacy_name ?? ''),
                'username' => (string) ($order->customer?->customerProfile?->username ?? ''),
            ],
            'order' => [
                'status' => (string) $order->status,
                'batch_id' => $order->batch_id,
                'go_group_id' => $order->go_group_id,
                'notes' => (string) $order->notes,
            ],
            'items' => $order->items->sortBy('id')->map(fn($item) => [
                'item_name'=>(string)$item->item_name,
                'details'=>(string)$item->details,
                'description_type'=>(string)$item->description_type,
                'qty'=>(int)$item->qty,
            ])->values()->all(),
            'invoices' => $order->invoices->sortBy('invoice_number')->map(fn($invoice) => [
                'invoice_number'=>(string)$invoice->invoice_number,
                'type'=>(string)$invoice->type,
                'amount'=>(int)$invoice->amount,
                'paid_amount'=>(int)$invoice->paid_amount,
                'status'=>(string)$invoice->status,
                'payments'=>$invoice->payments->sortBy('payment_number')->map(fn($payment)=>[
                    'payment_number'=>(string)$payment->payment_number,
                    'status'=>(string)$payment->status,
                    'amount'=>(int)$payment->amount,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    public function storeBaseline(Order $order): void
    {
        if (!Schema::hasColumn('orders','import_snapshot')) return;
        $order->forceFill([
            'import_snapshot'=>$this->snapshot($order),
            'imported_at'=>now(),
        ])->saveQuietly();
    }

    public function changes(Order $order): array
    {
        $baseline = $order->import_snapshot;
        if (!$baseline) return ['Baseline import lama belum tersedia; data perlu ditinjau manual sebelum cleanup.'];
        $current = $this->snapshot($order);
        $labels=[];
        if (($baseline['customer'] ?? []) !== ($current['customer'] ?? [])) $labels[]='Data customer berubah setelah import.';
        if (($baseline['order'] ?? []) !== ($current['order'] ?? [])) $labels[]='Status/relasi/catatan order berubah setelah import.';
        if (($baseline['items'] ?? []) !== ($current['items'] ?? [])) $labels[]='Barang atau qty order berubah setelah import.';
        if (($baseline['invoices'] ?? []) !== ($current['invoices'] ?? [])) $labels[]='Tagihan atau pembayaran berubah setelah import.';
        return $labels;
    }

    public function hasNewActivity(Order $order): bool
    {
        if (!$order->imported_at) return true;
        $at=$order->imported_at;
        if ($order->statusHistories()->where('changed_at','>',$at)->exists()) return true;
        if ($order->adjustments()->where('created_at','>',$at)->exists()) return true;
        if ($order->invoices()->where('created_at','>',$at)->exists()) return true;
        return $order->invoices()->whereHas('payments', fn($q)=>$q->where('payments.created_at','>',$at))->exists();
    }
}
