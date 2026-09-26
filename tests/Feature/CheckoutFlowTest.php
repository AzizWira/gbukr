<?php

namespace Tests\Feature;

use App\Models\{Country, Product, ProductVariant, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_page_renders_and_order_is_created_once(): void
    {
        Notification::fake();

        $customer = User::create([
            'name' => 'Customer Checkout',
            'email' => 'checkout@test.local',
            'password' => 'password1234',
            'role' => 'customer',
            'active' => true,
            'email_verified_at' => now(),
        ]);
        $customer->customerProfile()->create([]);

        $country = Country::create([
            'code' => 'JP',
            'name' => 'Jepang',
            'currency_code' => 'JPY',
            'rate' => 110,
            'admin_fee_idr' => 25000,
            'active' => true,
        ]);

        $product = Product::create([
            'country_id' => $country->id,
            'type' => 'ready',
            'name' => 'Ready Checkout Test',
            'slug' => 'ready-checkout-test',
            'active' => true,
            'payment_scheme' => ['type' => 'full', 'deadline_days' => 1],
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Blue',
            'price_idr' => 85000,
            'stock' => 3,
            'active' => true,
        ]);

        $session = ['acting_as' => 'customer', 'cart' => [$variant->id => 1]];

        $this->actingAs($customer)
            ->withSession($session)
            ->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('Periksa sebelum membuat order');

        $response = $this->actingAs($customer)
            ->withSession($session)
            ->post(route('checkout.store'), ['agree' => '1']);

        $response->assertRedirect(route('customer.orders.index'));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertSame(2, $variant->fresh()->stock);
    }
}
