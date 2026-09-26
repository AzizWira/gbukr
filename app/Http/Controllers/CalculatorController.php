<?php

namespace App\Http\Controllers;

use App\Models\{Country, ShippingOption};
use App\Services\CalculatorService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CalculatorController extends Controller
{
    public function index()
    {
        $countries = Country::with(['shippingOptions' => fn ($q) => $q->where('active', true)->orderBy('amount_foreign')])
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('calculator', compact('countries'));
    }

    public function calculate(Request $request, CalculatorService $service)
    {
        $data = $request->validate([
            'country_id' => [
                'required',
                Rule::exists('countries', 'id')->where(fn ($q) => $q->where('active', true)),
            ],
            'shipping_option_id' => [
                'required',
                Rule::exists('shipping_options', 'id')->where(fn ($q) => $q->where('active', true)),
            ],
            'item_price' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'together' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        $country = Country::where('active', true)->findOrFail($data['country_id']);
        $shipping = ShippingOption::where('active', true)
            ->where('country_id', $country->id)
            ->findOrFail($data['shipping_option_id']);

        return response()->json(
            $service->calculate($country, $shipping, (float) $data['item_price'], (int) $data['together'])
        );
    }
}
