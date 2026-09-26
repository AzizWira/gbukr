<?php

namespace Tests\Feature;

use App\Models\{Country, Product, ProductVariant, ShippingOption, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProductDetailMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_detail_shows_source_weight_rate_fee_and_variant_dp(): void
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

        $product = Product::create([
            'country_id' => $country->id,
            'type' => 'po',
            'name' => 'Enhypen Demo',
            'slug' => 'enhypen-demo',
            'active' => true,
            'item_fee_idr' => 12500,
            'ems_tax' => true,
            'apply_fansign' => true,
            'location_note' => 'Sidoarjo, Jawa Timur',
            'payment_scheme' => ['type' => 'dp', 'amount' => null, 'percent' => null, 'deadline_days' => 3],
        ]);
        $product->preorder()->create([
            'open_at' => now()->subHour(),
            'close_at' => now()->addDay(),
            'status' => 'open',
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'source_label' => 'Korean Web',
            'name' => 'Photobook Ver',
            'estimated_weight_grams' => 300,
            'price_idr' => 330000,
            'dp_amount_idr' => 250000,
            'active' => true,
        ]);

        $this->get(route('catalog.show', $product))
            ->assertOk()
            ->assertSee('Korean Web')
            ->assertSee('300gr')
            ->assertSee('Rate aktif:')
            ->assertSee('12.500')
            ->assertSee('DP Rp250.000')
            ->assertSee('₩', false);
    }

    public function test_checkout_uses_variant_dp_override_and_zero_shipping_option_is_allowed(): void
    {
        Notification::fake();
        $owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $customer = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $customer->customerProfile()->create([]);
        $country = Country::create([
            'code' => 'KR', 'name' => 'Korea Selatan', 'currency_code' => 'KRW',
            'rate' => 12, 'admin_fee_idr' => 25000, 'active' => true,
        ]);

        $this->actingAs($owner)->post(route('owner.master.shipping'), [
            'country_id' => $country->id,
            'label' => 'Free Shipping',
            'amount_foreign' => 0,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('shipping_options', ['country_id' => $country->id, 'amount_foreign' => 0]);

        $product = Product::create([
            'country_id' => $country->id,
            'type' => 'po',
            'name' => 'PO DP Variant',
            'slug' => 'po-dp-variant',
            'active' => true,
            'item_fee_idr' => 12500,
            'payment_scheme' => ['type' => 'dp', 'amount' => null, 'percent' => null, 'deadline_days' => 3],
        ]);
        $product->preorder()->create(['open_at' => now()->subHour(), 'close_at' => now()->addDay(), 'status' => 'open']);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Photobook Ver',
            'price_idr' => 330000,
            'dp_amount_idr' => 250000,
            'active' => true,
        ]);

        $this->actingAs($customer)
            ->withSession(['acting_as' => 'customer', 'cart' => [$variant->id => 1]])
            ->post(route('checkout.store'), ['agree' => '1'])
            ->assertRedirect(route('customer.orders.index'));

        $this->assertDatabaseHas('invoices', ['customer_id' => $customer->id, 'type' => 'dp', 'amount' => 250000]);
    }
}
