<?php

namespace App\Http\Controllers;

use App\Models\Product;
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

        $query = trim((string) ($data['q'] ?? ''));

        $products = Product::with(['country', 'variants', 'preorder'])
            ->where('active', true)
            ->when(!empty($data['type']), fn ($q) => $q->where('type', $data['type']))
            ->when($query !== '', fn ($q) => $q->where('name', 'like', '%' . $query . '%'))
            ->latest()
            ->paginate(12)
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
