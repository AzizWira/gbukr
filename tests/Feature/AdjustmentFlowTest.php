<?php

namespace Tests\Feature;

use App\Models\{Country, Order, OrderItem, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdjustmentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_kekurangan_invoice_with_weight_and_rate_context(): void
    {
        Notification::fake();
        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner-adjust@test.local',
            'password' => 'password1234',
            'role' => 'owner',
            'active' => true,
            'email_verified_at' => now(),
        ]);
        $customer = User::create([
            'name' => 'Customer',
            'email' => 'customer-adjust@test.local',
            'password' => 'password1234',
            'role' => 'customer',
            'active' => true,
            'email_verified_at' => now(),
        ]);
        $customer->customerProfile()->create([]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'source_type' => 'po',
            'order_number' => 'ORD-ADJ-001',
            'status' => 'arrived_indo',
            'rate_snapshot' => 13.8,
            'currency_code' => 'KRW',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'item_name' => 'Barang A',
            'qty' => 1,
            'unit_price_idr' => 150000,
            'subtotal_idr' => 150000,
        ]);

        $response = $this->actingAs($owner)->post(route('owner.orders.adjustments.store', $order), [
            'reason' => 'weight_rate',
            'amount_idr' => 50000,
            'estimated_weight_grams' => 500,
            'actual_weight_grams' => 700,
            'original_rate' => 13.8,
            'final_rate' => 14,
            'notes' => 'Berat aktual dan rate naik.',
        ]);

        $response->assertRedirect(route('owner.orders.show', $order));
        $this->assertDatabaseHas('invoices', [
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'type' => 'kekurangan',
            'amount' => 50000,
            'status' => 'unpaid',
        ]);
        $this->assertDatabaseHas('order_adjustments', [
            'order_id' => $order->id,
            'reason' => 'weight_rate',
            'amount_idr' => 50000,
            'estimated_weight_grams' => 500,
            'actual_weight_grams' => 700,
        ]);
    }

    public function test_weight_adjustment_rejects_actual_weight_below_estimate(): void
    {
        $owner = User::create([
            'name' => 'Owner', 'email' => 'owner-adjust2@test.local', 'password' => 'password1234',
            'role' => 'owner', 'active' => true, 'email_verified_at' => now(),
        ]);
        $customer = User::create([
            'name' => 'Customer', 'email' => 'customer-adjust2@test.local', 'password' => 'password1234',
            'role' => 'customer', 'active' => true, 'email_verified_at' => now(),
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'source_type' => 'po',
            'order_number' => 'ORD-ADJ-002',
            'status' => 'ordered',
        ]);

        $response = $this->actingAs($owner)
            ->from(route('owner.orders.show', $order))
            ->post(route('owner.orders.adjustments.store', $order), [
                'reason' => 'weight',
                'amount_idr' => 10000,
                'estimated_weight_grams' => 700,
                'actual_weight_grams' => 500,
            ]);

        $response->assertRedirect(route('owner.orders.show', $order));
        $response->assertSessionHasErrors('actual_weight_grams');
        $this->assertDatabaseCount('order_adjustments', 0);
    }
}
