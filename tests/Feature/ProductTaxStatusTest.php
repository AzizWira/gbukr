<?php

namespace Tests\Feature;

use App\Models\{Country, Product, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTaxStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_explicitly_mark_product_tax_status(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $country = Country::create([
            'code' => 'KR', 'name' => 'Korea Selatan', 'currency_code' => 'KRW',
            'rate' => 12, 'admin_fee_idr' => 25000, 'active' => true,
        ]);

        $response = $this->actingAs($owner)->post(route('owner.products.store'), [
            'name' => 'PO Tax Included',
            'country_id' => $country->id,
            'type' => 'po',
            'tax_status' => 'included_estimate',
            'payment_type' => 'full',
            'close_at' => now()->addDay()->format('Y-m-d\\TH:i'),
            'active' => 1,
        ]);

        $product = Product::where('name', 'PO Tax Included')->firstOrFail();
        $response->assertRedirect(route('owner.products.edit', $product));
        $this->assertSame('included_estimate', $product->tax_status);
        $this->assertTrue((bool) $product->ems_tax);

        $this->get(route('catalog.show', $product))
            ->assertOk()
            ->assertSee('Sudah termasuk estimasi tax');
    }
}
