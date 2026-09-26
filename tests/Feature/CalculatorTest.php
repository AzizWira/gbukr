<?php

namespace Tests\Feature;

use App\Models\{Country, ShippingOption};
use App\Services\CalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_formula_matches_business_rule_and_fee_per_item_includes_shipping_and_admin(): void
    {
        $country = Country::create([
            'code' => 'KR',
            'name' => 'Korea Selatan',
            'currency_code' => 'KRW',
            'rate' => 12,
            'admin_fee_idr' => 25000,
            'active' => true,
        ]);
        $shipping = ShippingOption::create([
            'country_id' => $country->id,
            'label' => '10k KRW',
            'amount_foreign' => 10000,
            'active' => true,
        ]);

        $result = app(CalculatorService::class)->calculate($country, $shipping, 20000, 5);

        $this->assertSame(120000, $result['shipping_idr']);
        $this->assertSame(145000, $result['shared_fee_total_idr']);
        $this->assertSame(29000, $result['fee_per_item_idr']);
        $this->assertSame(269000, $result['total_idr']);
    }

    public function test_customer_example_returns_42500_fee_per_item(): void
    {
        $country = Country::create([
            'code' => 'KR', 'name' => 'Korea Selatan', 'currency_code' => 'KRW',
            'rate' => 12, 'admin_fee_idr' => 25000, 'active' => true,
        ]);
        $shipping = ShippingOption::create([
            'country_id' => $country->id, 'label' => '₩5.000', 'amount_foreign' => 5000, 'active' => true,
        ]);

        $result = app(CalculatorService::class)->calculate($country, $shipping, 20000, 2);
        $this->assertSame(42500, $result['fee_per_item_idr']);
        $this->assertSame(282500, $result['total_idr']);
    }
}
