<?php

namespace Tests\Feature;

use App\Models\{Batch, Country, GoGroup, Order, OrderItem, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BatchAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_bulk_tax_invoices_per_item_without_reentering_customers(): void
    {
        Notification::fake();
        $owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $a = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $b = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $country = Country::create([
            'code' => 'KR', 'name' => 'Korea Selatan', 'currency_code' => 'KRW',
            'rate' => 12, 'admin_fee_idr' => 25000, 'active' => true,
        ]);
        $group = GoGroup::create(['name' => 'GO Demo', 'status' => 'active']);
        $batch = Batch::create([
            'go_group_id' => $group->id,
            'country_id' => $country->id,
            'code' => 'KR-260926-001',
            'name' => 'Batch Demo',
            'status' => 'arrived_indo',
        ]);

        $orderA = $this->batchOrder($batch, $a, 'ORD-A', 2);
        $orderB = $this->batchOrder($batch, $b, 'ORD-B', 1);

        $response = $this->actingAs($owner)->post(route('owner.batches.adjustments.store', $batch), [
            'reason' => 'tax',
            'mode' => 'per_item',
            'shared_amount_idr' => 10000,
            'order_ids' => [$orderA->id, $orderB->id],
            'notes' => 'Tax aktual saat tiba Indonesia.',
        ]);

        $response->assertRedirect(route('owner.batches.show', $batch));
        $this->assertDatabaseHas('invoices', ['order_id' => $orderA->id, 'type' => 'kekurangan', 'amount' => 20000]);
        $this->assertDatabaseHas('invoices', ['order_id' => $orderB->id, 'type' => 'kekurangan', 'amount' => 10000]);
        $this->assertDatabaseHas('order_adjustments', ['order_id' => $orderA->id, 'reason' => 'tax', 'amount_idr' => 20000]);
        $this->assertDatabaseHas('order_adjustments', ['order_id' => $orderB->id, 'reason' => 'tax', 'amount_idr' => 10000]);
    }

    public function test_custom_bulk_adjustment_only_uses_orders_from_the_selected_batch(): void
    {
        Notification::fake();
        $owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $customer = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);
        $country = Country::create([
            'code' => 'KR', 'name' => 'Korea Selatan', 'currency_code' => 'KRW',
            'rate' => 12, 'admin_fee_idr' => 25000, 'active' => true,
        ]);
        $batchA = Batch::create(['country_id' => $country->id, 'code' => 'KR-A', 'name' => 'A', 'status' => 'ordered']);
        $batchB = Batch::create(['country_id' => $country->id, 'code' => 'KR-B', 'name' => 'B', 'status' => 'ordered']);
        $foreignOrder = $this->batchOrder($batchB, $customer, 'ORD-X', 1);

        $response = $this->actingAs($owner)
            ->from(route('owner.batches.show', $batchA))
            ->post(route('owner.batches.adjustments.store', $batchA), [
                'reason' => 'shipping_actual',
                'mode' => 'custom',
                'order_ids' => [$foreignOrder->id],
                'custom_amounts' => [$foreignOrder->id => 25000],
            ]);

        $response->assertSessionHasErrors('order_ids');
        $this->assertDatabaseCount('order_adjustments', 0);
    }

    public function test_admin_mode_cannot_create_financial_adjustment_from_batch(): void
    {
        Notification::fake();
        $admin = User::factory()->create([
            'role' => 'customer',
            'admin_enabled' => true,
            'email_verified_at' => now(),
        ]);
        $country = Country::create([
            'code' => 'KR', 'name' => 'Korea Selatan', 'currency_code' => 'KRW',
            'rate' => 12, 'admin_fee_idr' => 25000, 'active' => true,
        ]);
        $batch = Batch::create(['country_id' => $country->id, 'code' => 'KR-ADMIN', 'name' => 'Admin Guard', 'status' => 'ordered']);

        $this->actingAs($admin)
            ->withSession(['acting_as' => 'admin'])
            ->post(route('owner.batches.adjustments.store', $batch), [
                'reason' => 'tax',
                'mode' => 'per_customer',
                'shared_amount_idr' => 10000,
                'order_ids' => [999],
            ])
            ->assertForbidden();
    }

    private function batchOrder(Batch $batch, User $customer, string $number, int $qty): Order
    {
        $order = Order::create([
            'customer_id' => $customer->id,
            'batch_id' => $batch->id,
            'source_type' => 'batch',
            'order_number' => $number,
            'status' => $batch->status,
            'currency_code' => $batch->country->currency_code,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'item_name' => 'Album Demo',
            'qty' => $qty,
        ]);
        return $order;
    }
}
