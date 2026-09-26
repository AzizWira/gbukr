<?php

namespace App\Services;

use App\Models\Payment;
use App\Notifications\PaymentStatusNotification;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function approve(Payment $payment, int $actor): void
    {
        DB::transaction(function () use ($payment, $actor) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            abort_unless($payment->status === 'pending', 422, 'Pembayaran sudah diproses.');

            $payment->load('invoices');

            foreach ($payment->invoices as $invoice) {
                $allocation = (int) $invoice->pivot->allocated_amount;
                $totalDue = $invoice->amount + $invoice->penalty_amount;
                $invoice->paid_amount = min($totalDue, $invoice->paid_amount + $allocation);
                $invoice->status = ($totalDue - $invoice->paid_amount) <= 0 ? 'paid' : 'partial';
                $invoice->save();
            }

            $payment->update([
                'status' => 'approved',
                'verified_at' => now(),
                'verified_by' => $actor,
                'rejection_reason' => null,
            ]);
        });

        $fresh = $payment->fresh(['customer']);
        try {
            $fresh?->customer?->notify(new PaymentStatusNotification($fresh));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function reject(Payment $payment, int $actor, string $reason): void
    {
        DB::transaction(function () use ($payment, $actor, $reason) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            abort_unless($payment->status === 'pending', 422, 'Pembayaran sudah diproses.');

            $payment->load('invoices');
            $payment->update([
                'status' => 'rejected',
                'verified_at' => now(),
                'verified_by' => $actor,
                'rejection_reason' => $reason,
            ]);

            foreach ($payment->invoices as $invoice) {
                if ($invoice->status === 'pending') {
                    $invoice->update(['status' => $invoice->paid_amount > 0 ? 'partial' : 'unpaid']);
                }
            }
        });

        $fresh = $payment->fresh(['customer']);
        try {
            $fresh?->customer?->notify(new PaymentStatusNotification($fresh));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
