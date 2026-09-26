<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\{Invoice, Order, OrderItem, ProductVariant};
use App\Notifications\InvoiceCreatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function show(Request $request)
    {
        $cart = $this->cart($request);

        if ($cart->isEmpty()) {
            return redirect()->route('catalog.index')->withErrors(['cart' => 'Keranjang masih kosong.']);
        }

        return view('customer.checkout', compact('cart'));
    }

    public function store(Request $request)
    {
        $request->validate(['agree' => ['accepted']]);
        $cart = $this->cart($request);

        if ($cart->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Keranjang kosong.']);
        }

        $createdOrderIds = [];
        $createdInvoiceIds = [];

        DB::transaction(function () use ($request, $cart, &$createdOrderIds, &$createdInvoiceIds) {
            foreach ($cart as $row) {
                $variant = ProductVariant::with(['product.country', 'product.preorder'])
                    ->lockForUpdate()
                    ->findOrFail($row['variant']->id);

                $product = $variant->product;
                $qty = (int) $row['qty'];

                abort_unless($product->active && $variant->active, 422, 'Salah satu produk atau variasi sudah tidak tersedia.');
                abort_if($variant->price_idr === null && $variant->price_foreign === null, 422, 'Harga salah satu variasi belum diatur.');

                if ($product->type === 'po') {
                    abort_unless($product->preorder?->isOpen(), 422, 'Salah satu PO sudah ditutup.');
                }

                if ($product->type === 'ready') {
                    abort_if($variant->stock === null, 422, 'Stok Ready Stock belum diatur.');
                    abort_if($variant->stock < $qty, 422, 'Stok ' . $product->name . ' tidak mencukupi.');
                    $variant->decrement('stock', $qty);
                }

                $rate = (float) $product->country->rate;
                abort_if($rate <= 0, 422, 'Rate negara untuk salah satu produk belum valid.');

                $unit = $variant->price_idr !== null
                    ? (int) $variant->price_idr
                    : (int) round(((float) $variant->price_foreign) * $rate) + (int) $product->item_fee_idr;

                abort_if($unit <= 0, 422, 'Harga Rupiah salah satu variasi tidak valid.');

                $order = Order::create([
                    'customer_id' => $request->user()->id,
                    'preorder_id' => $product->preorder?->id,
                    'source_type' => $product->type === 'po' ? 'po' : 'ready',
                    'order_number' => $this->generateOrderNumber(),
                    'status' => 'ordered',
                    'rate_snapshot' => $rate,
                    'currency_code' => $product->country->currency_code,
                ]);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'item_name' => $product->name,
                    'details' => collect([$variant->source_label, $variant->name, $variant->details])->filter()->join(' · '),
                    'qty' => $qty,
                    'unit_price_foreign' => $variant->price_foreign,
                    'unit_price_idr' => $unit,
                    'subtotal_idr' => $unit * $qty,
                ]);

                $scheme = $product->payment_scheme ?: ['type' => 'full'];
                $type = $scheme['type'] ?? 'full';
                $total = $unit * $qty;
                $amount = $total;

                if (in_array($type, ['dp', 'cicilan'], true)) {
                    if (!empty($variant->dp_amount_idr)) {
                        $amount = (int) $variant->dp_amount_idr * $qty;
                    } elseif (!empty($scheme['amount'])) {
                        $amount = (int) $scheme['amount'];
                    } else {
                        $percent = (float) ($scheme['percent'] ?? 0);
                        abort_if($percent <= 0 || $percent > 100, 422, 'Konfigurasi DP/Cicilan produk belum valid.');
                        $amount = (int) round($total * ($percent / 100));
                    }

                    abort_if($amount <= 0 || $amount > $total, 422, 'Nominal pembayaran awal produk tidak valid.');
                }

                $deadlineDays = $scheme['deadline_days'] ?? null;
                $deadline = $deadlineDays !== null ? now()->addDays((int) $deadlineDays) : null;

                $invoice = Invoice::create([
                    'customer_id' => $request->user()->id,
                    'order_id' => $order->id,
                    'invoice_number' => $this->generateInvoiceNumber(),
                    'type' => $type,
                    'amount' => $amount,
                    'deadline_at' => $deadline,
                    'status' => 'unpaid',
                    'notes' => $type === 'dp' ? 'Tagihan DP awal' : 'Tagihan awal',
                ]);

                $createdOrderIds[] = $order->id;
                $createdInvoiceIds[] = $invoice->id;
            }
        });

        $request->session()->forget('cart');

        try {
            Invoice::with('customer')->whereIn('id', $createdInvoiceIds)->get()->each(function ($invoice) {
                $invoice->customer?->notify(new InvoiceCreatedNotification($invoice));
            });
        } catch (\Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route('customer.orders.index')
            ->with('success', count($createdOrderIds) . ' order berhasil dibuat.');
    }

    private function cart(Request $request)
    {
        $raw = $request->session()->get('cart', []);

        if ($raw === []) {
            return collect();
        }

        $variants = ProductVariant::with(['product.country', 'product.preorder'])
            ->whereIn('id', array_keys($raw))
            ->get();

        return $variants
            ->filter(fn ($variant) => isset($raw[$variant->id]))
            ->map(fn ($variant) => ['variant' => $variant, 'qty' => (int) $raw[$variant->id]])
            ->values();
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-' . now()->format('ymd') . '-' . strtoupper(Str::random(7));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }

    private function generateInvoiceNumber(): string
    {
        do {
            $number = 'INV-' . now()->format('ymd') . '-' . strtoupper(Str::random(7));
        } while (Invoice::where('invoice_number', $number)->exists());

        return $number;
    }
}
