<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\{Invoice, Order, User};
use App\Notifications\InvoiceCreatedNotification;
use App\Support\Search;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Search::term($request->query('q'));

        $invoices = Invoice::with(['customer', 'order.items', 'order.batch', 'payments'])
            ->when($query !== '', fn ($builder) => $builder->where(function ($sub) use ($query) {
                Search::code($sub, 'invoice_number', $query)
                    ->orWhereHas('customer', fn ($user) => $user
                        ->where('name', 'like', '%' . $query . '%')
                        ->orWhere('email', 'like', '%' . $query . '%'))
                    ->orWhereHas('order', fn ($order) => $order
                        ->where('order_number', 'like', '%' . $query . '%')
                        ->orWhereHas('items', fn ($item) => $item->where('item_name', 'like', '%' . $query . '%'))
                        ->orWhereHas('batch', fn ($batch) => Search::code($batch, 'code', $query)));
            }))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $customers = User::where('role', 'customer')->where('active', true)->orderBy('name')->get();
        return view('owner.invoices.index', compact('invoices', 'customers'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $invoice = Invoice::create($data + [
            'invoice_number' => $this->generateInvoiceNumber(),
            'status' => 'unpaid',
            'paid_amount' => 0,
            'penalty_amount' => 0,
        ]);
        $invoice->customer?->notify(new InvoiceCreatedNotification($invoice));
        return back()->with('success', 'Tagihan baru dibuat.');
    }

    public function update(Request $request, Invoice $invoice)
    {
        if ($invoice->payments()->exists() || !in_array($invoice->status, ['unpaid', 'overdue'], true)) {
            throw ValidationException::withMessages(['invoice' => 'Tagihan yang sudah memiliki proses pembayaran tidak dapat diedit.']);
        }

        $data = $this->validated($request, $invoice);
        $invoice->update([
            'type' => $data['type'],
            'amount' => $data['amount'],
            'deadline_at' => $data['deadline_at'] ?? null,
            'notes' => $data['notes'] ?? null,
            'penalty_amount' => 0,
            'status' => 'unpaid',
        ]);
        $invoice->recalculatePenalty();
        return back()->with('success', 'Tagihan diperbarui.');
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->payments()->exists() || !in_array($invoice->status, ['unpaid', 'overdue'], true)) {
            throw ValidationException::withMessages(['invoice' => 'Tagihan yang sudah memiliki proses pembayaran tidak dapat dibatalkan.']);
        }
        $invoice->update(['status' => 'cancelled']);
        return back()->with('success', 'Tagihan dibatalkan. Histori tetap disimpan.');
    }

    private function validated(Request $request, ?Invoice $invoice = null): array
    {
        $rules = [
            'customer_id' => [$invoice ? 'nullable' : 'required', Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'customer')->where('active', true))],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'type' => ['required', 'in:full,dp,cicilan,pelunasan,kenaikan,penyesuaian,kekurangan'],
            'amount' => ['required', 'integer', 'min:1'],
            'deadline_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
        $data = $request->validate($rules);

        if ($invoice) {
            $data['customer_id'] = $invoice->customer_id;
            $data['order_id'] = $invoice->order_id;
        }

        if (!empty($data['order_id'])) {
            abort_unless(Order::where('id', $data['order_id'])->where('customer_id', $data['customer_id'])->exists(), 422, 'Order tidak sesuai dengan customer.');
        }
        return $data;
    }

    private function generateInvoiceNumber(): string
    {
        do { $number = 'INV-' . now()->format('ymd') . '-' . strtoupper(Str::random(7)); }
        while (Invoice::where('invoice_number', $number)->exists());
        return $number;
    }
}
