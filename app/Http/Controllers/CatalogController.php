<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Search;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:180'],
            'type' => ['nullable', Rule::in(['po', 'ready'])],
        ]);

        $query = Search::term($data['q'] ?? null);

        $products = Product::with(['country', 'variants', 'preorder'])
            ->where('active', true)
            ->when(!empty($data['type']), fn ($q) => $q->where('type', $data['type']))
            ->when($query !== '', fn ($builder) => $builder->where(function ($sub) use ($query) {
                $sub->where('name', 'like', '%' . $query . '%')
                    ->orWhere('description', 'like', '%' . $query . '%')
                    ->orWhereHas('country', fn ($country) => $country->where('name', 'like', '%' . $query . '%'))
                    ->orWhereHas('variants', fn ($variant) => $variant
                        ->where('name', 'like', '%' . $query . '%')
                        ->orWhere('sku', 'like', '%' . $query . '%')
                        ->orWhere('source_label', 'like', '%' . $query . '%'));
            }))
            ->latest()
            ->paginate(\App\Support\Listing::perPage($request, 20))
            ->withQueryString();

        return view('catalog.index', compact('products'));
    }

    public function show(Product $product)
    {
        abort_unless($product->active, 404);
        $product->load(['country', 'variants' => fn ($q) => $q->where('active', true), 'preorder']);

        return view('catalog.show', compact('product'));
    }
}
