<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\{Invoice, Order, User};
use App\Notifications\InvoiceCreatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        $invoices = Invoice::with(['customer', 'order.items'])
            ->when($query !== '', fn ($q) => $q->where(function ($sub) use ($query) {
                $sub->where('invoice_number', 'like', '%' . $query . '%')
                    ->orWhereHas('customer', fn ($user) => $user->where('name', 'like', '%' . $query . '%'));
            }))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $customers = User::where('role', 'customer')->where('active', true)->orderBy('name')->get();

        return view('owner.invoices.index', compact('invoices', 'customers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'customer')->where('active', true)),
            ],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'type' => ['required', 'in:full,dp,cicilan,pelunasan,kenaikan,penyesuaian'],
            'amount' => ['required', 'integer', 'min:1'],
            'deadline_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (!empty($data['order_id'])) {
            abort_unless(
                Order::where('id', $data['order_id'])->where('customer_id', $data['customer_id'])->exists(),
                422,
                'Order tidak sesuai dengan customer.'
            );
        }

        $invoice = Invoice::create($data + [
            'invoice_number' => $this->generateInvoiceNumber(),
            'status' => 'unpaid',
            'paid_amount' => 0,
            'penalty_amount' => 0,
        ]);

        $invoice->customer?->notify(new InvoiceCreatedNotification($invoice));

        return back()->with('success', 'Tagihan baru dibuat.');
    }

    private function generateInvoiceNumber(): string
    {
        do {
            $number = 'INV-' . now()->format('ymd') . '-' . strtoupper(Str::random(7));
        } while (Invoice::where('invoice_number', $number)->exists());

        return $number;
    }
}
