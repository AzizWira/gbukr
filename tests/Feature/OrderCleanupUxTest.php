<?php

namespace Tests\Feature;

use App\Models\{Batch, Country, Invoice, Order, OrderItem, Payment, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCleanupUxTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::create([
            'name' => 'Owner Cleanup',
            'email' => 'owner-cleanup@test.local',
            'password' => 'password1234',
            'role' => 'owner',
            'active' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function batchOrder(bool $withPayment = false): array
    {
        $customer = User::create([
            'name' => 'Customer Cleanup',
            'email' => uniqid('customer', true) . '@test.local',
            'password' => 'password1234',
            'role' => 'customer',
            'active' => true,
            'email_verified_at' => now(),
        ]);
        $country = Country::create(['code' => 'KR', 'name' => 'Korea', 'currency_code' => 'KRW', 'rate' => 12, 'admin_fee_idr' => 0, 'active' => true]);
        $batch = Batch::create(['country_id' => $country->id, 'code' => uniqid('KR-TEST-'), 'name' => 'Cleanup Batch', 'status' => 'ordered']);
        $order = Order::create(['customer_id' => $customer->id, 'batch_id' => $batch->id, 'source_type' => 'batch', 'order_number' => uniqid('ORD-B-'), 'status' => 'ordered']);
        OrderItem::create(['order_id' => $order->id, 'item_name' => 'Album', 'qty' => 1]);
        $invoice = Invoice::create(['customer_id' => $customer->id, 'order_id' => $order->id, 'invoice_number' => uniqid('INV-'), 'type' => 'pelunasan', 'amount' => 100000, 'paid_amount' => $withPayment ? 100000 : 0, 'status' => $withPayment ? 'paid' : 'unpaid']);
        if ($withPayment) {
            $payment = Payment::create(['customer_id' => $customer->id, 'payment_number' => uniqid('PAY-'), 'amount' => 100000, 'status' => 'approved']);
            $invoice->payments()->attach($payment->id, ['allocated_amount' => 100000]);
        }
        return [$batch, $order, $invoice];
    }

    public function test_owner_can_delete_batch_order_without_financial_history(): void
    {
        [$batch, $order] = $this->batchOrder(false);
        $response = $this->actingAs($this->owner())->delete(route('owner.orders.destroy', $order));
        $response->assertRedirect(route('owner.batches.show', $batch));
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_order_with_payment_history_is_detached_from_batch_instead_of_deleted(): void
    {
        [$batch, $order, $invoice] = $this->batchOrder(true);
        $response = $this->actingAs($this->owner())->delete(route('owner.orders.destroy', $order));
        $response->assertRedirect(route('owner.batches.show', $batch));
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'batch_id' => null]);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'paid']);
        $this->assertDatabaseHas('invoice_payment', ['invoice_id' => $invoice->id]);
    }

    public function test_batch_bulk_cleanup_deletes_safe_order_and_detaches_paid_order(): void
    {
        [$batch, $safe] = $this->batchOrder(false);
        $customer = User::create(['name'=>'Paid Customer','email'=>'paid-cleanup@test.local','password'=>'password1234','role'=>'customer','active'=>true,'email_verified_at'=>now()]);
        $blocked = Order::create(['customer_id'=>$customer->id,'batch_id'=>$batch->id,'source_type'=>'batch','order_number'=>'ORD-B-PAID','status'=>'ordered']);
        OrderItem::create(['order_id'=>$blocked->id,'item_name'=>'PC','qty'=>1]);
        $invoice = Invoice::create(['customer_id'=>$customer->id,'order_id'=>$blocked->id,'invoice_number'=>'INV-PAID-CLEANUP','type'=>'pelunasan','amount'=>50000,'paid_amount'=>50000,'status'=>'paid']);

        $response = $this->actingAs($this->owner())->delete(route('owner.batches.orders.destroy',$batch), [
            'order_ids' => [$safe->id, $blocked->id],
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('orders', ['id'=>$safe->id]);
        $this->assertDatabaseHas('orders', ['id'=>$blocked->id, 'batch_id'=>null]);
        $this->assertDatabaseHas('invoices', ['id'=>$invoice->id]);
    }

    public function test_batch_delete_cleans_safe_orders_and_preserves_financial_history(): void
    {
        [$batch, $safe] = $this->batchOrder(false);
        $customer = User::create(['name'=>'Paid Keep','email'=>'paid-keep@test.local','password'=>'password1234','role'=>'customer','active'=>true,'email_verified_at'=>now()]);
        $protected = Order::create(['customer_id'=>$customer->id,'batch_id'=>$batch->id,'source_type'=>'batch','order_number'=>'ORD-B-KEEP','status'=>'ordered']);
        OrderItem::create(['order_id'=>$protected->id,'item_name'=>'PC','qty'=>1]);
        $invoice = Invoice::create(['customer_id'=>$customer->id,'order_id'=>$protected->id,'invoice_number'=>'INV-KEEP','type'=>'pelunasan','amount'=>50000,'paid_amount'=>50000,'status'=>'paid']);

        $response = $this->actingAs($this->owner())->delete(route('owner.batches.destroy',$batch));
        $response->assertRedirect(route('owner.batches.index'));
        $this->assertDatabaseMissing('batches', ['id'=>$batch->id]);
        $this->assertDatabaseMissing('orders', ['id'=>$safe->id]);
        $this->assertDatabaseHas('orders', ['id'=>$protected->id, 'batch_id'=>null]);
        $this->assertDatabaseHas('invoices', ['id'=>$invoice->id]);
    }
}
