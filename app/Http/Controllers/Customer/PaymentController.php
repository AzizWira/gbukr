<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\{BankAccount, Invoice, Payment, PaymentProof, User};
use App\Notifications\{PaymentSubmittedCustomerNotification, PaymentSubmittedNotification};
use App\Services\ImageStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function create(Request $request)
    {
        $ids = collect((array) $request->query('invoice_ids', []))
            ->filter(fn ($id) => filter_var($id, FILTER_VALIDATE_INT) !== false)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return redirect()->route('customer.invoices.index')->withErrors(['invoice_ids' => 'Pilih minimal satu tagihan.']);
        }

        $invoices = $request->user()->invoices()
            ->whereIn('id', $ids)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->get();

        foreach ($invoices as $invoice) {
            $invoice->recalculatePenalty();
        }

        if ($invoices->count() !== $ids->count()) {
            return redirect()->route('customer.invoices.index')->withErrors([
                'invoice_ids' => 'Ada tagihan yang tidak tersedia, bukan milik akunmu, atau sedang diproses.',
            ]);
        }

        $total = $invoices->sum(fn ($invoice) => $invoice->outstanding());
        $banks = BankAccount::where('active', true)->orderBy('bank_name')->get();

        return view('customer.payments.create', compact('invoices', 'total', 'banks'));
    }

    public function store(Request $request, ImageStorageService $images)
    {
        $data = $request->validate([
            'invoice_ids' => ['required', 'array', 'min:1', 'max:50'],
            'invoice_ids.*' => ['required', 'integer', 'distinct'],
            'bank_account_id' => ['required', 'integer', \Illuminate\Validation\Rule::exists('bank_accounts','id')->where(fn($q)=>$q->where('active',true))],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $invoiceIds = collect($data['invoice_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $file = $request->file('proof');
        $storedPath = null;

        try {
            $payment = DB::transaction(function () use ($request, $invoiceIds, $file, $data, $images, &$storedPath) {
                $invoices = $request->user()->invoices()
                    ->whereIn('id', $invoiceIds)
                    ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                    ->lockForUpdate()
                    ->get();

                if ($invoices->count() !== $invoiceIds->count()) {
                    throw ValidationException::withMessages([
                        'invoice_ids' => 'Ada tagihan yang sudah berubah status atau sedang diproses. Muat ulang halaman lalu coba lagi.',
                    ]);
                }

                foreach ($invoices as $invoice) {
                    $invoice->recalculatePenalty();
                }

                $expected = (int) $invoices->sum(fn ($invoice) => $invoice->outstanding());
                if ($expected <= 0) {
                    throw ValidationException::withMessages(['invoice_ids' => 'Tagihan yang dipilih sudah tidak memiliki sisa pembayaran.']);
                }

                $payment = Payment::create([
                    'customer_id' => $request->user()->id,
                    'bank_account_id' => $data['bank_account_id'],
                    'payment_number' => $this->generatePaymentNumber(),
                    'amount' => $expected,
                    'status' => 'pending',
                    'submitted_at' => now(),
                ]);

                foreach ($invoices as $invoice) {
                    $allocation = $invoice->outstanding();
                    $payment->invoices()->attach($invoice->id, ['allocated_amount' => $allocation]);
                    $invoice->update(['status' => 'pending']);
                }

                if (str_starts_with((string) $file->getMimeType(), 'image/')) {
                    $stored = $images->storeOptimized($file, 'payment-proofs', 'local', 1800, 82);
                    $storedPath = $stored['path'];
                    $storedMime = $stored['mime_type'];
                    $storedSize = $stored['size'];
                } else {
                    $storedPath = $file->store('payment-proofs');
                    $storedMime = $file->getMimeType();
                    $storedSize = \Illuminate\Support\Facades\Storage::size($storedPath);
                }

                PaymentProof::create([
                    'payment_id' => $payment->id,
                    'path' => $storedPath,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $storedMime,
                    'size' => $storedSize,
                ]);

                return $payment;
            });
        } catch (\Throwable $e) {
            if ($storedPath) {
                \Illuminate\Support\Facades\Storage::delete($storedPath);
            }
            throw $e;
        }

        try {
            $request->user()->notify(new PaymentSubmittedCustomerNotification($payment));
            User::where('role', 'owner')->where('active', true)->get()
                ->each->notify(new PaymentSubmittedNotification($payment));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route('customer.invoices.index')
            ->with('success', 'Bukti pembayaran sudah dikirim dan menunggu verifikasi.');
    }

    private function generatePaymentNumber(): string
    {
        do {
            $number = 'PAY-' . now()->format('ymd') . '-' . strtoupper(Str::random(7));
        } while (Payment::where('payment_number', $number)->exists());

        return $number;
    }
}
