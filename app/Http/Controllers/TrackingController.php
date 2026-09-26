<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:180'],
        ]);

        $query = trim((string) ($data['q'] ?? ''));

        $shipments = Shipment::with('country')
            ->where('visible_publicly', true)
            ->when($query !== '', fn ($q) => $q->where(function ($sub) use ($query) {
                $sub->where('reference', 'like', '%' . $query . '%')
                    ->orWhere('item_details', 'like', '%' . $query . '%')
                    ->orWhere('description_type', 'like', '%' . $query . '%')
                    ->orWhere('tracking_number', 'like', '%' . $query . '%');
            }))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('tracking', compact('shipments'));
    }
}
