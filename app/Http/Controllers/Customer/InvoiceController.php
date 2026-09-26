<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = $request->user()->invoices()
            ->with(['order.items', 'adjustment'])
            ->latest()
            ->paginate(20);

        foreach ($invoices as $invoice) {
            $invoice->recalculatePenalty();
        }

        return view('customer.invoices.index', compact('invoices'));
    }

    public function show(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->customer_id === $request->user()->id, 403);
        $invoice->recalculatePenalty();
        $invoice->load(['order.items', 'payments.proofs', 'adjustment']);

        return view('customer.invoices.show', compact('invoice'));
    }
}
