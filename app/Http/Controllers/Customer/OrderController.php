<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Search;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Search::term($request->query('q'));
        $orders = $request->user()->orders()
            ->with(['items', 'batch', 'preorder.product', 'goGroup'])
            ->when($query !== '', fn ($builder) => $builder->where(function ($sub) use ($query) {
                Search::code($sub, 'order_number', $query)
                    ->orWhereHas('items', fn ($item) => $item->where('item_name', 'like', '%' . $query . '%')->orWhere('details', 'like', '%' . $query . '%'))
                    ->orWhereHas('batch', fn ($batch) => Search::code($batch, 'code', $query)->orWhere('name', 'like', '%' . $query . '%'))
                    ->orWhereHas('goGroup', fn ($go) => $go->where('name', 'like', '%' . $query . '%'));
            }))
            ->latest()
            ->paginate(\App\Support\Listing::perPage($request, 20))
            ->withQueryString();
        return view('customer.orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order)
    {
        abort_unless($order->customer_id === $request->user()->id, 403);
        $order->load(['items', 'batch.country', 'preorder.product', 'goGroup', 'invoices.payments']);
        foreach ($order->invoices as $invoice) $invoice->recalculatePenalty();
        return view('customer.orders.show', compact('order'));
    }
}
