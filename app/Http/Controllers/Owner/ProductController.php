<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\{Country, OrderItem, Product, ProductVariant};
use App\Services\ImageStorageService;
use App\Support\Search;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type');
        if ($type !== null && $type !== '' && !in_array($type, ['po', 'ready'], true)) {
            abort(422, 'Filter jenis produk tidak valid.');
        }

        $query = Search::term($request->query('q'));

        $products = Product::with(['country', 'variants', 'preorder'])
            ->when($query !== '', fn ($builder) => $builder->where(function ($sub) use ($query) {
                $sub->where('name', 'like', '%' . $query . '%')
                    ->orWhere('description', 'like', '%' . $query . '%')
                    ->orWhereHas('country', fn ($country) => $country->where('name', 'like', '%' . $query . '%')->orWhere('code', 'like', '%' . $query . '%'))
                    ->orWhereHas('variants', fn ($variant) => $variant
                        ->where('name', 'like', '%' . $query . '%')
                        ->orWhere('sku', 'like', '%' . $query . '%')
                        ->orWhere('source_label', 'like', '%' . $query . '%'));
            }))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('owner.products.index', compact('products'));
    }

    public function create()
    {
        return view('owner.products.form', [
            'product' => new Product,
            'countries' => Country::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, ImageStorageService $images)
    {
        $data = $this->validated($request);
        $storedImage = null;

        if ($request->hasFile('image')) {
            $storedImage = $images->storeOptimized($request->file('image'), 'products', 'public')['path'];
        }

        try {
            $product = DB::transaction(function () use ($data, $storedImage) {
                $product = Product::create([
                    'country_id' => $data['country_id'],
                    'type' => $data['type'],
                    'name' => trim($data['name']),
                    'slug' => Str::slug($data['name']) . '-' . strtolower(Str::random(5)),
                    'description' => $data['description'] ?? null,
                    'item_fee_idr' => $data['item_fee_idr'],
                    'free_shipping' => $data['free_shipping'],
                    'ems_tax' => $data['ems_tax'],
                    'tax_status' => $data['tax_status'],
                    'apply_fansign' => $data['apply_fansign'],
                    'location_note' => $data['location_note'] ?? null,
                    'event_date' => $data['event_date'] ?? null,
                    'image_path' => $storedImage,
                    'active' => $data['active'],
                    'payment_scheme' => $data['payment_scheme'],
                ]);

                $this->syncPo($product, $data);

                return $product;
            });
        } catch (\Throwable $e) {
            if ($storedImage) {
                Storage::disk('public')->delete($storedImage);
            }
            throw $e;
        }

        return redirect()
            ->route('owner.products.edit', $product)
            ->with('success', 'Produk berhasil dibuat. Tambahkan variasi produk.');
    }

    public function edit(Product $product)
    {
        $product->load(['variants', 'preorder']);
        $productUsageCount = OrderItem::where('product_id', $product->id)
            ->orWhereIn('product_variant_id', $product->variants->pluck('id'))
            ->count();
        $variantUsage = $product->variants->mapWithKeys(fn (ProductVariant $variant) => [
            $variant->id => OrderItem::where('product_variant_id', $variant->id)->count(),
        ]);

        return view('owner.products.form', [
            'product' => $product,
            'countries' => Country::where('active', true)->orderBy('name')->get(),
            'productUsageCount' => $productUsageCount,
            'variantUsage' => $variantUsage,
        ]);
    }

    public function update(Request $request, Product $product, ImageStorageService $images)
    {
        $data = $this->validated($request, $product);
        $oldImage = $product->image_path;
        $newImage = null;

        if ($request->hasFile('image')) {
            $newImage = $images->storeOptimized($request->file('image'), 'products', 'public')['path'];
        }

        try {
            DB::transaction(function () use ($product, $data, $newImage) {
                $product->update([
                    'country_id' => $data['country_id'],
                    'type' => $data['type'],
                    'name' => trim($data['name']),
                    'description' => $data['description'] ?? null,
                    'item_fee_idr' => $data['item_fee_idr'],
                    'free_shipping' => $data['free_shipping'],
                    'ems_tax' => $data['ems_tax'],
                    'tax_status' => $data['tax_status'],
                    'apply_fansign' => $data['apply_fansign'],
                    'location_note' => $data['location_note'] ?? null,
                    'event_date' => $data['event_date'] ?? null,
                    'image_path' => $newImage ?: $product->image_path,
                    'active' => $data['active'],
                    'payment_scheme' => $data['payment_scheme'],
                ]);

                $this->syncPo($product, $data);
            });
        } catch (\Throwable $e) {
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }
            throw $e;
        }

        if ($newImage && $oldImage && $oldImage !== $newImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return back()->with('success', 'Produk berhasil diperbarui.');
    }

    public function toggle(Product $product)
    {
        $product->update(['active' => !$product->active]);
        return back()->with('success', $product->active ? 'Produk diaktifkan.' : 'Produk dinonaktifkan.');
    }

    public function destroy(Product $product)
    {
        $variantIds = $product->variants()->pluck('id');
        $usage = OrderItem::where('product_id', $product->id)
            ->orWhereIn('product_variant_id', $variantIds)
            ->count();

        if ($usage > 0) {
            throw ValidationException::withMessages([
                'delete' => 'Produk tidak dapat dihapus permanen karena sudah dipakai oleh ' . $usage . ' item order. Nonaktifkan produk agar tidak dapat dipesan lagi tanpa merusak histori.',
            ]);
        }

        $image = $product->image_path;
        DB::transaction(fn () => $product->delete());
        if ($image) Storage::disk('public')->delete($image);

        return redirect()->route('owner.products.index')->with('success', 'Produk yang belum pernah dipakai berhasil dihapus permanen.');
    }

    public function variant(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'sku' => ['nullable', 'string', 'max:80', 'unique:product_variants,sku'],
            'source_label' => ['nullable', 'string', 'max:120'],
            'details' => ['nullable', 'string', 'max:1000'],
            'estimated_weight_grams' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'price_foreign' => ['nullable', 'numeric', 'gt:0'],
            'price_idr' => ['nullable', 'integer', 'min:1'],
            'dp_amount_idr' => ['nullable', 'integer', 'min:1'],
            'stock' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);

        $hasForeign = filled($data['price_foreign'] ?? null);
        $hasIdr = filled($data['price_idr'] ?? null);

        if ($hasForeign === $hasIdr) {
            throw ValidationException::withMessages([
                'price_foreign' => 'Isi tepat satu harga: mata uang asal atau Rupiah.',
            ]);
        }

        $estimatedPrice = $hasIdr
            ? (int) $data['price_idr']
            : (int) round(((float) $data['price_foreign']) * (float) $product->country->rate) + (int) $product->item_fee_idr;
        if (!empty($data['dp_amount_idr']) && (int) $data['dp_amount_idr'] > $estimatedPrice) {
            throw ValidationException::withMessages([
                'dp_amount_idr' => 'DP variasi tidak boleh lebih besar dari pricelist estimasi.',
            ]);
        }

        if ($product->type === 'ready' && !array_key_exists('stock', $data)) {
            throw ValidationException::withMessages([
                'stock' => 'Stok wajib diisi untuk produk Ready Stock.',
            ]);
        }

        $product->variants()->create($data + ['active' => true]);

        return back()->with('success', 'Variasi ditambahkan.');
    }

    public function toggleVariant(Product $product, ProductVariant $variant)
    {
        abort_unless($variant->product_id === $product->id, 404);
        $variant->update(['active' => !$variant->active]);

        return back()->with('success', $variant->active ? 'Variasi diaktifkan.' : 'Variasi dinonaktifkan.');
    }

    public function destroyVariant(Product $product, ProductVariant $variant)
    {
        abort_unless($variant->product_id === $product->id, 404);
        $usage = OrderItem::where('product_variant_id', $variant->id)->count();
        if ($usage > 0) {
            throw ValidationException::withMessages([
                'delete' => 'Variasi tidak dapat dihapus permanen karena sudah dipakai oleh ' . $usage . ' item order. Nonaktifkan variasi agar histori tetap aman.',
            ]);
        }
        $variant->delete();
        return back()->with('success', 'Variasi yang belum pernah dipakai berhasil dihapus permanen.');
    }

    public function updateVariant(Request $request, Product $product, ProductVariant $variant)
    {
        abort_unless($variant->product_id === $product->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'sku' => ['nullable', 'string', 'max:80', \Illuminate\Validation\Rule::unique('product_variants', 'sku')->ignore($variant->id)],
            'source_label' => ['nullable', 'string', 'max:120'],
            'details' => ['nullable', 'string', 'max:1000'],
            'estimated_weight_grams' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'price_foreign' => ['nullable', 'numeric', 'gt:0'],
            'price_idr' => ['nullable', 'integer', 'min:1'],
            'dp_amount_idr' => ['nullable', 'integer', 'min:1'],
            'stock' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);

        $hasForeign = filled($data['price_foreign'] ?? null);
        $hasIdr = filled($data['price_idr'] ?? null);
        if ($hasForeign === $hasIdr) {
            throw ValidationException::withMessages([
                'price_foreign' => 'Isi tepat satu harga: mata uang asal atau Rupiah.',
            ]);
        }

        $estimatedPrice = $hasIdr
            ? (int) $data['price_idr']
            : (int) round(((float) $data['price_foreign']) * (float) $product->country->rate) + (int) $product->item_fee_idr;
        if (!empty($data['dp_amount_idr']) && (int) $data['dp_amount_idr'] > $estimatedPrice) {
            throw ValidationException::withMessages([
                'dp_amount_idr' => 'DP variasi tidak boleh lebih besar dari pricelist estimasi.',
            ]);
        }

        if ($product->type === 'ready' && !array_key_exists('stock', $data)) {
            throw ValidationException::withMessages(['stock' => 'Stok wajib diisi untuk produk Ready Stock.']);
        }

        $variant->update($data);
        return back()->with('success', 'Variasi berhasil diperbarui.');
    }

    public function close(Product $product)
    {
        abort_unless($product->type === 'po' && $product->preorder, 404);

        if ($product->preorder->status === 'closed') {
            return back()->with('success', 'PO sudah dalam keadaan tertutup.');
        }

        $product->preorder->update([
            'status' => 'closed',
            'closed_manually_at' => now(),
        ]);

        return back()->with('success', 'PO ditutup lebih awal.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        if (!$request->filled('tax_status')) {
            $request->merge(['tax_status' => 'excluded']);
        }

        $data = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'type' => ['required', 'in:po,ready'],
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'item_fee_idr' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'free_shipping' => ['nullable', 'boolean'],
            'tax_status' => ['required', 'in:included_estimate,excluded'],
            'apply_fansign' => ['nullable', 'boolean'],
            'location_note' => ['nullable', 'string', 'max:160'],
            'event_date' => ['nullable', 'date'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'active' => ['nullable', 'boolean'],
            'payment_type' => ['required', 'in:full,dp,cicilan,pelunasan'],
            'payment_amount' => ['nullable', 'integer', 'min:1'],
            'payment_percent' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'deadline_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'open_at' => ['nullable', 'date'],
            'close_at' => ['nullable', 'date'],
        ]);

        $country = Country::where('active', true)->find($data['country_id']);
        if (!$country) {
            throw ValidationException::withMessages(['country_id' => 'Negara harus aktif.']);
        }

        if ($data['type'] === 'po') {
            if (empty($data['close_at'])) {
                throw ValidationException::withMessages(['close_at' => 'Close PO wajib diisi untuk produk PO.']);
            }

            $openAt = !empty($data['open_at']) ? \Illuminate\Support\Carbon::parse($data['open_at']) : now();
            $closeAt = \Illuminate\Support\Carbon::parse($data['close_at']);

            if ($closeAt->lte($openAt)) {
                throw ValidationException::withMessages(['close_at' => 'Close PO harus setelah waktu Open PO.']);
            }
        }


        $data['active'] = $request->boolean('active');
        $data['item_fee_idr'] = (int) ($data['item_fee_idr'] ?? 0);
        $data['free_shipping'] = $request->boolean('free_shipping');
        $data['ems_tax'] = ($data['tax_status'] ?? 'excluded') === 'included_estimate';
        $data['apply_fansign'] = $request->boolean('apply_fansign');
        $data['payment_scheme'] = [
            'type' => $data['payment_type'],
            'amount' => $data['payment_amount'] ?? null,
            'percent' => $data['payment_percent'] ?? null,
            'deadline_days' => $data['deadline_days'] ?? null,
        ];

        return $data;
    }

    private function syncPo(Product $product, array $data): void
    {
        if ($product->type === 'po') {
            $openAt = !empty($data['open_at']) ? $data['open_at'] : now();
            $existingStatus = $product->preorder?->status;

            $product->preorder()->updateOrCreate([], [
                'open_at' => $openAt,
                'close_at' => $data['close_at'],
                'status' => $existingStatus ?: 'open',
            ]);

            return;
        }

        if ($product->preorder) {
            $product->preorder->delete();
        }
    }
}
