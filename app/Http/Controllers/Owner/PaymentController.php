<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\{Payment, PaymentProof};
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
        ]);

        $payments = Payment::with(['customer', 'invoices', 'bankAccount'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('owner.payments.index', compact('payments'));
    }

    public function show(Payment $payment)
    {
        $payment->load(['customer', 'invoices.order.items', 'proofs', 'verifier']);
        return view('owner.payments.show', compact('payment'));
    }

    public function approve(Request $request, Payment $payment, PaymentService $service)
    {
        abort_unless($payment->status === 'pending', 422, 'Pembayaran sudah diproses.');
        $service->approve($payment, $request->user()->id);

        return back()->with('success', 'Pembayaran diverifikasi.');
    }

    public function reject(Request $request, Payment $payment, PaymentService $service)
    {
        abort_unless($payment->status === 'pending', 422, 'Pembayaran sudah diproses.');

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $service->reject($payment, $request->user()->id, $data['reason']);

        return back()->with('success', 'Bukti pembayaran ditolak.');
    }

    public function proofPreview(PaymentProof $proof)
    {
        abort_unless(Storage::exists($proof->path), 404);

        $safeName = preg_replace('/[^A-Za-z0-9._ -]/', '_', basename($proof->original_name)) ?: 'bukti-transfer';

        return Storage::response($proof->path, $safeName, [
            'Content-Type' => $proof->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . $safeName . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function proof(PaymentProof $proof)
    {
        abort_unless(Storage::exists($proof->path), 404);
        return Storage::download($proof->path, $proof->original_name);
    }
}
