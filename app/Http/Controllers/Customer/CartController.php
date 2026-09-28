<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart = $this->hydrate($request->session()->get('cart', []));
        return view('customer.cart', compact('cart'));
    }


    public function summary(Request $request)
    {
        $cart = $this->hydrate($request->session()->get('cart', []));
        $items = $cart->take(4)->map(function ($row) {
            $variant = $row['variant'];
            $product = $variant->product;
            $unit = $variant->price_idr !== null
                ? (int) $variant->price_idr
                : (int) round(((float) $variant->price_foreign * (float) $product->country->rate) + (int) $product->item_fee_idr);
            return [
                'name' => $product->name,
                'variant' => $variant->name,
                'qty' => (int) $row['qty'],
                'subtotal' => $unit * (int) $row['qty'],
            ];
        })->values();

        return response()->json([
            'count' => (int) $cart->sum('qty'),
            'total' => (int) $cart->sum(function ($row) {
                $variant = $row['variant'];
                $product = $variant->product;
                $unit = $variant->price_idr !== null
                    ? (int) $variant->price_idr
                    : (int) round(((float) $variant->price_foreign * (float) $product->country->rate) + (int) $product->item_fee_idr);
                return $unit * (int) $row['qty'];
            }),
            'items' => $items,
        ]);
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'qty' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $variant = ProductVariant::with(['product.preorder', 'product.country'])->findOrFail($data['variant_id']);
        $this->assertPurchasable($variant, (int) $data['qty']);

        $cart = $request->session()->get('cart', []);
        $newQty = min(99, ($cart[$variant->id] ?? 0) + (int) $data['qty']);
        $this->assertPurchasable($variant, $newQty);

        $cart[$variant->id] = $newQty;
        $request->session()->put('cart', $cart);

        return back()->with('success', 'Barang masuk ke keranjang.')->with('cart_added', true);
    }

    public function update(Request $request, ProductVariant $variant)
    {
        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $cart = $request->session()->get('cart', []);
        if (!isset($cart[$variant->id])) {
            return back()->withErrors(['cart' => 'Barang tersebut sudah tidak ada di keranjang.']);
        }

        $variant->load(['product.preorder', 'product.country']);
        $this->assertPurchasable($variant, (int) $data['qty']);

        $cart[$variant->id] = (int) $data['qty'];
        $request->session()->put('cart', $cart);

        return back()->with('success', 'Keranjang diperbarui.');
    }

    public function destroy(Request $request, ProductVariant $variant)
    {
        $cart = $request->session()->get('cart', []);
        unset($cart[$variant->id]);
        $request->session()->put('cart', $cart);

        return back()->with('success', 'Barang dihapus dari keranjang.');
    }

    private function hydrate(array $cart)
    {
        if ($cart === []) {
            return collect();
        }

        $variants = ProductVariant::with(['product.country', 'product.preorder'])
            ->whereIn('id', array_keys($cart))
            ->get();

        return $variants
            ->filter(fn ($variant) => isset($cart[$variant->id]))
            ->map(fn ($variant) => ['variant' => $variant, 'qty' => (int) $cart[$variant->id]])
            ->values();
    }

    private function assertPurchasable(ProductVariant $variant, int $qty): void
    {
        abort_unless($variant->active && $variant->product?->active, 422, 'Produk atau variasi sudah tidak tersedia.');

        if ($variant->price_idr === null && $variant->price_foreign === null) {
            abort(422, 'Harga variasi belum diatur. Hubungi Owner sebelum checkout.');
        }

        if ($variant->product->type === 'po') {
            abort_unless($variant->product->preorder?->isOpen(), 422, 'PO sudah ditutup.');
        }

        if ($variant->product->type === 'ready' && $variant->stock !== null && $variant->stock < $qty) {
            abort(422, 'Stok tidak mencukupi untuk jumlah yang dipilih.');
        }
    }
}
