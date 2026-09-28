<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\{OrderCleanupService, OrderStatusService};
use App\Support\Search;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request, OrderCleanupService $cleanup)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:180'],
            'status' => ['nullable', Rule::in(OrderStatusService::statuses())],
        ]);

        $query = Search::term($data['q'] ?? null);

        $orders = Order::with(['customer', 'items', 'batch', 'preorder.product', 'goGroup', 'invoices.payments'])
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($sub) use ($query) {
                    Search::code($sub, 'order_number', $query)
                        ->orWhereHas('customer', fn ($user) => $user
                            ->where('name', 'like', '%' . $query . '%')
                            ->orWhere('email', 'like', '%' . $query . '%'))
                        ->orWhereHas('items', fn ($item) => $item
                            ->where('item_name', 'like', '%' . $query . '%')
                            ->orWhere('details', 'like', '%' . $query . '%'))
                        ->orWhereHas('batch', fn ($batch) => Search::code($batch, 'code', $query)
                            ->orWhere('name', 'like', '%' . $query . '%')
                            ->orWhere('tracking_number', 'like', '%' . $query . '%'))
                        ->orWhereHas('goGroup', fn ($go) => $go->where('name', 'like', '%' . $query . '%'))
                        ->orWhereHas('preorder.product', fn ($product) => $product->where('name', 'like', '%' . $query . '%'));
                });
            })
            ->when(!empty($data['status']), fn ($q) => $q->where('status', $data['status']))
            ->latest()
            ->paginate(\App\Support\Listing::perPage($request, 20))
            ->withQueryString();

        return view('owner.orders.index', compact('orders'));
    }

    public function show(Order $order, OrderCleanupService $cleanup)
    {
        $order->load([
            'customer.customerProfile',
            'items',
            'batch.country',
            'batch.warehouse',
            'preorder.product',
            'goGroup',
            'invoices.payments',
            'adjustments.invoice',
            'statusHistories.changer',
        ]);

        return view('owner.orders.show', [
            'order' => $order,
            'deletePolicy' => $cleanup->policy($order),
            'latestDeletionRequest' => $order->deletionRequests()->latest()->first(),
        ]);
    }

    public function status(Request $request, Order $order, OrderStatusService $service)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(OrderStatusService::statuses())],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $service->update(
            $order,
            $data['status'],
            $request->user()->id,
            $data['notes'] ?? 'Override/update Owner'
        );

        return back()->with('success', 'Status order diperbarui.');
    }

    public function destroy(Request $request, Order $order, OrderCleanupService $cleanup)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $number = $order->order_number;
        $result = $cleanup->processDeletion(
            $order,
            $request->user(),
            $data['reason'] ?? null,
            'owner'
        );

        if ($result['status'] === 'pending_approval') {
            return back()->with('success', 'Permintaan penghapusan ' . $number . ' sudah dikirim ke customer. Order tetap tersimpan sampai customer menyetujui.');
        }

        return redirect()->route('owner.orders.index')->with('success', 'Order ' . $number . ' berhasil dihapus. Customer terkait sudah diberi notifikasi bila akunnya terhubung.');
    }
}
