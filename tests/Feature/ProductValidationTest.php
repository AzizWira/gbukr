<?php

namespace Tests\Feature;

use App\Models\{Country, Product, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_po_does_not_leave_partial_product(): void
    {
        $owner = $this->owner();
        $country = $this->country();

        $response = $this->actingAs($owner)->from(route('owner.products.create'))->post(route('owner.products.store'), [
            'name' => 'PO Tanpa Close',
            'country_id' => $country->id,
            'type' => 'po',
            'payment_type' => 'full',
            'active' => 1,
        ]);

        $response->assertRedirect(route('owner.products.create'));
        $response->assertSessionHasErrors('close_at');
        $this->assertDatabaseMissing('products', ['name' => 'PO Tanpa Close']);
    }

    public function test_variant_requires_exactly_one_price_and_ready_stock_requires_stock(): void
    {
        $owner = $this->owner();
        $country = $this->country();
        $product = Product::create([
            'country_id' => $country->id,
            'type' => 'ready',
            'name' => 'Ready Demo',
            'slug' => 'ready-demo',
            'active' => true,
            'payment_scheme' => ['type' => 'full'],
        ]);

        $response = $this->actingAs($owner)->from(route('owner.products.edit', $product))->post(route('owner.products.variant', $product), [
            'name' => 'Version A',
        ]);

        $response->assertSessionHasErrors('price_foreign');
        $this->assertDatabaseCount('product_variants', 0);

        $response = $this->actingAs($owner)->from(route('owner.products.edit', $product))->post(route('owner.products.variant', $product), [
            'name' => 'Version A',
            'price_idr' => 100000,
        ]);

        $response->assertSessionHasErrors('stock');
        $this->assertDatabaseCount('product_variants', 0);
    }

    private function owner(): User
    {
        return User::create([
            'name' => 'Owner Test',
            'email' => uniqid('owner') . '@test.local',
            'password' => 'password1234',
            'role' => 'owner',
            'active' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function country(): Country
    {
        return Country::create([
            'code' => 'KR',
            'name' => 'Korea Selatan',
            'currency_code' => 'KRW',
            'rate' => 12,
            'admin_fee_idr' => 25000,
            'active' => true,
        ]);
    }
}
