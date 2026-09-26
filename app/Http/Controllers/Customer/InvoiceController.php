<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Support\Search;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Search::term($request->query('q'));
        $invoices = $request->user()->invoices()
            ->with(['order.items', 'order.batch', 'adjustment'])
            ->where('status', '!=', 'cancelled')
            ->when($query !== '', fn ($builder) => $builder->where(function ($sub) use ($query) {
                Search::code($sub, 'invoice_number', $query)
                    ->orWhereHas('order', fn ($order) => $order
                        ->where('order_number', 'like', '%' . $query . '%')
                        ->orWhereHas('items', fn ($item) => $item->where('item_name', 'like', '%' . $query . '%'))
                        ->orWhereHas('batch', fn ($batch) => Search::code($batch, 'code', $query)));
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        foreach ($invoices as $invoice) $invoice->recalculatePenalty();
        return view('customer.invoices.index', compact('invoices'));
    }

    public function show(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->customer_id === $request->user()->id, 403);
        abort_if($invoice->status === 'cancelled', 404);
        $invoice->recalculatePenalty();
        $invoice->load(['order.items', 'payments.proofs', 'adjustment']);
        return view('customer.invoices.show', compact('invoice'));
    }
}
