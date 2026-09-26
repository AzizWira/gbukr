<?php

namespace App\Services;

use App\Models\{Country, ShippingOption};

class CalculatorService
{
    public function calculate(Country $country, ShippingOption $shipping, float $price, int $together): array
    {
        $together = max(1, $together);
        $rate = (float) $country->rate;
        $shippingForeign = (float) $shipping->amount_foreign;
        $shippingIdr = (int) round($shippingForeign * $rate);
        $sharedFeeIdr = $shippingIdr + (int) $country->admin_fee_idr;
        $feePerItemIdr = (int) round($sharedFeeIdr / $together);
        $itemPriceIdr = (int) round($price * $rate);
        $total = $itemPriceIdr + $feePerItemIdr;

        return [
            'item_price' => $price,
            'item_price_idr' => $itemPriceIdr,
            'shipping_foreign' => $shippingForeign,
            'shipping_idr' => $shippingIdr,
            'admin_fee_idr' => (int) $country->admin_fee_idr,
            'shared_fee_total_idr' => $sharedFeeIdr,
            'fee_per_item_idr' => $feePerItemIdr,
            // Backward-compatible keys for old clients/tests that may still inspect the response.
            'shipping_share' => $shippingForeign / $together,
            'admin_fee_share_idr' => (int) round($country->admin_fee_idr / $together),
            'foreign_subtotal' => $price + ($shippingForeign / $together),
            'rate' => $rate,
            'currency' => $country->currency_code,
            'currency_symbol' => $country->moneySymbol(),
            'total_idr' => $total,
        ];
    }
}
