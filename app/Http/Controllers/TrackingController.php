<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Support\Search;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:180'],
        ]);

        $query = Search::term($data['q'] ?? null);

        $shipments = Shipment::with('country')
            ->where('visible_publicly', true)
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($sub) use ($query) {
                    Search::code($sub, 'reference', $query)
                        ->orWhere('item_details', 'like', '%' . $query . '%')
                        ->orWhere('description_type', 'like', '%' . $query . '%')
                        ->orWhere('info', 'like', '%' . $query . '%')
                        ->orWhere('tracking_number', 'like', '%' . $query . '%')
                        ->orWhereHas('country', fn ($country) => $country->where('name', 'like', '%' . $query . '%')->orWhere('code', 'like', '%' . $query . '%'));
                });
            })
            ->latest()
            ->paginate(\App\Support\Listing::perPage($request, 20))
            ->withQueryString();

        return view('tracking', compact('shipments'));
    }
}
