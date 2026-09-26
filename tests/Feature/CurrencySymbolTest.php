<?php

namespace Tests\Feature;

use App\Models\{Country, ShippingOption, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencySymbolTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculator_uses_configurable_currency_symbol(): void
    {
        $country = Country::create([
            'code' => 'KR',
            'name' => 'Korea Selatan',
            'currency_code' => 'KRW',
            'currency_symbol' => '₩',
            'rate' => 12,
            'admin_fee_idr' => 25000,
            'active' => true,
        ]);

        ShippingOption::create([
            'country_id' => $country->id,
            'label' => 'Standard',
            'amount_foreign' => 5000,
            'active' => true,
        ]);

        $this->get(route('calculator'))
            ->assertOk()
            ->assertSee('data-currency-symbol="₩"', false)
            ->assertSee('₩5.000');
    }

    public function test_owner_can_update_currency_symbol(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $country = Country::create([
            'code' => 'KR',
            'name' => 'Korea Selatan',
            'currency_code' => 'KRW',
            'currency_symbol' => '₩',
            'rate' => 12,
            'admin_fee_idr' => 25000,
            'active' => true,
        ]);

        $this->actingAs($owner)->post(route('owner.master.country', $country), [
            'code' => 'KR',
            'name' => 'Korea Selatan',
            'currency_code' => 'KRW',
            'currency_symbol' => 'W',
            'rate' => 12,
            'admin_fee_idr' => 25000,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('countries', [
            'id' => $country->id,
            'currency_symbol' => 'W',
        ]);
    }
}
