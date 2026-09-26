<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\{Batch, Country, Shipment};
use App\Services\OrderStatusService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TrackingController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:180'],
        ]);

        $query = trim((string) ($data['q'] ?? ''));

        $shipments = Shipment::with('country')
            ->when($query !== '', fn ($q) => $q->where(function ($sub) use ($query) {
                $sub->where('reference', 'like', '%' . $query . '%')
                    ->orWhere('item_details', 'like', '%' . $query . '%')
                    ->orWhere('description_type', 'like', '%' . $query . '%')
                    ->orWhere('tracking_number', 'like', '%' . $query . '%');
            }))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $countries = Country::where('active', true)->orderBy('name')->get();

        return view('owner.tracking.index', compact('shipments', 'countries'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'source_type' => ['required', 'in:po,batch'],
            'reference' => ['required', 'string', 'max:100'],
            'item_details' => ['required', 'string', 'max:180'],
            'description_type' => ['required', 'string', 'max:100'],
            'info' => ['nullable', 'string', 'max:1000'],
            'country_id' => [
                'required',
                Rule::exists('countries', 'id')->where(fn ($q) => $q->where('active', true)),
            ],
            'tracking_number' => ['nullable', 'string', 'max:180'],
            'status' => ['required', Rule::in(OrderStatusService::statuses())],
            'visible_publicly' => ['nullable', 'boolean'],
        ]);

        if ($data['source_type'] === 'batch' && Batch::where('code', $data['reference'])->exists()) {
            throw ValidationException::withMessages([
                'reference' => 'Tracking untuk Batch tersebut dibuat otomatis. Ubah status atau tracking dari halaman Batch agar data tetap sinkron.',
            ]);
        }

        Shipment::create($data + ['visible_publicly' => $request->boolean('visible_publicly', true)]);

        return back()->with('success', 'Tracking ditambahkan.');
    }

    public function update(Request $request, Shipment $shipment)
    {
        if ($shipment->source_type === 'batch' && $shipment->source_id) {
            throw ValidationException::withMessages([
                'status' => 'Tracking Batch dikelola dari halaman Batch agar status order terkait tetap sinkron.',
            ]);
        }

        $data = $request->validate([
            'tracking_number' => ['nullable', 'string', 'max:180'],
            'status' => ['required', Rule::in(OrderStatusService::statuses())],
            'visible_publicly' => ['nullable', 'boolean'],
        ]);

        $shipment->update($data + ['visible_publicly' => $request->boolean('visible_publicly')]);

        return back()->with('success', 'Tracking diperbarui.');
    }
}
