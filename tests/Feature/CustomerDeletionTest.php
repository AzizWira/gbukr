<?php

namespace Tests\Feature;

use App\Models\{Invoice, Order, OrderDeletionRequest, Payment, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::create([
            'name' => 'Owner',
            'email' => 'owner-customer-delete@test.local',
            'password' => 'password1234',
            'role' => 'owner',
            'active' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function customer(string $suffix): User
    {
        return User::create([
            'name' => 'Customer '.$suffix,
            'email' => 'customer-'.$suffix.'@test.local',
            'password' => 'password1234',
            'role' => 'customer',
            'active' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_customer_without_transaction_history_can_be_deleted_permanently(): void
    {
        $owner = $this->owner();
        $customer = $this->customer('empty');

        $this->actingAs($owner)
            ->delete(route('owner.customers.destroy', $customer))
            ->assertRedirect(route('owner.customers.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
    }

    public function test_customer_with_live_invoice_is_not_deleted(): void
    {
        $owner = $this->owner();
        $customer = $this->customer('live-invoice');

        Invoice::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-CUST-LIVE',
            'type' => 'full_payment',
            'amount' => 100000,
            'status' => 'unpaid',
        ]);

        $this->actingAs($owner)
            ->from(route('owner.customers.show', $customer))
            ->delete(route('owner.customers.destroy', $customer))
            ->assertRedirect(route('owner.customers.show', $customer))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('users', ['id' => $customer->id]);
    }

    public function test_soft_deleted_unpaid_invoice_is_purged_with_customer(): void
    {
        $owner = $this->owner();
        $customer = $this->customer('archived-invoice');

        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-CUST-ARCHIVED',
            'type' => 'full_payment',
            'amount' => 100000,
            'paid_amount' => 0,
            'status' => 'cancelled',
        ]);
        $invoice->delete();

        $this->actingAs($owner)
            ->get(route('owner.customers.show', $customer))
            ->assertOk()
            ->assertSee('Hapus customer')
            ->assertSee('Akan dibersihkan: 0 order · 1 tagihan · 0 pembayaran');

        $this->actingAs($owner)
            ->delete(route('owner.customers.destroy', $customer))
            ->assertRedirect(route('owner.customers.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
    }

    public function test_soft_deleted_order_and_unpaid_invoice_are_purged_with_customer(): void
    {
        $owner = $this->owner();
        $customer = $this->customer('archived-order');

        $order = Order::create([
            'customer_id' => $customer->id,
            'source_type' => 'batch',
            'order_number' => 'ORD-CUST-ARCHIVED',
            'status' => 'ordered',
        ]);
        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'order_id' => $order->id,
            'invoice_number' => 'INV-CUST-ORDER-ARCHIVED',
            'type' => 'full_payment',
            'amount' => 100000,
            'paid_amount' => 0,
            'status' => 'cancelled',
        ]);
        $invoice->delete();
        $order->delete();

        $this->actingAs($owner)
            ->delete(route('owner.customers.destroy', $customer))
            ->assertRedirect(route('owner.customers.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
    }

    public function test_soft_deleted_paid_invoice_still_protects_customer(): void
    {
        $owner = $this->owner();
        $customer = $this->customer('archived-paid');

        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-CUST-ARCHIVED-PAID',
            'type' => 'full_payment',
            'amount' => 100000,
            'paid_amount' => 100000,
            'status' => 'paid',
        ]);
        $invoice->delete();

        $this->actingAs($owner)
            ->get(route('owner.customers.show', $customer))
            ->assertOk()
            ->assertDontSee('Hapus customer')
            ->assertSee('histori finansial');

        $this->actingAs($owner)
            ->from(route('owner.customers.show', $customer))
            ->delete(route('owner.customers.destroy', $customer))
            ->assertRedirect(route('owner.customers.show', $customer))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('users', ['id' => $customer->id]);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'customer_id' => $customer->id]);
    }

    public function test_customer_can_be_deleted_after_paid_order_deletion_was_approved(): void
    {
        $owner = $this->owner();
        $customer = $this->customer('approved-delete');

        $order = Order::create([
            'customer_id' => $customer->id,
            'source_type' => 'batch',
            'order_number' => 'ORD-CUST-APPROVED-DELETE',
            'status' => 'ordered',
        ]);
        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'order_id' => $order->id,
            'invoice_number' => 'INV-CUST-APPROVED-DELETE',
            'type' => 'full_payment',
            'amount' => 100000,
            'paid_amount' => 100000,
            'status' => 'paid',
        ]);
        $payment = Payment::create([
            'customer_id' => $customer->id,
            'payment_number' => 'PAY-CUST-APPROVED-DELETE',
            'amount' => 100000,
            'status' => 'approved',
        ]);
        $invoice->payments()->attach($payment->id, ['allocated_amount' => 100000]);

        $this->actingAs($owner)
            ->delete(route('owner.orders.destroy', $order), ['reason' => 'Data duplikat'])
            ->assertSessionHasNoErrors();

        $deletionRequest = OrderDeletionRequest::where('order_id', $order->id)->firstOrFail();

        $this->actingAs($customer)
            ->post(route('customer.deletion-requests.approve', $deletionRequest))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);

        $this->actingAs($owner)
            ->get(route('owner.customers.show', $customer))
            ->assertOk()
            ->assertSee('Hapus customer');

        $this->actingAs($owner)
            ->delete(route('owner.customers.destroy', $customer))
            ->assertRedirect(route('owner.customers.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
        $this->assertDatabaseHas('order_deletion_requests', [
            'id' => $deletionRequest->id,
            'status' => 'approved',
            'customer_id' => null,
        ]);
    }

    public function test_old_soft_deleted_paid_history_with_completed_approval_can_be_purged_with_customer(): void
    {
        $owner = $this->owner();
        $customer = $this->customer('legacy-approved');

        $order = Order::create([
            'customer_id' => $customer->id,
            'source_type' => 'batch',
            'order_number' => 'ORD-CUST-LEGACY-APPROVED',
            'status' => 'ordered',
        ]);
        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'order_id' => $order->id,
            'invoice_number' => 'INV-CUST-LEGACY-APPROVED',
            'type' => 'full_payment',
            'amount' => 100000,
            'paid_amount' => 100000,
            'status' => 'paid',
        ]);
        $payment = Payment::create([
            'customer_id' => $customer->id,
            'payment_number' => 'PAY-CUST-LEGACY-APPROVED',
            'amount' => 100000,
            'status' => 'approved',
        ]);
        $invoice->payments()->attach($payment->id, ['allocated_amount' => 100000]);

        OrderDeletionRequest::create([
            'order_id' => $order->id,
            'owner_id' => $owner->id,
            'customer_id' => $customer->id,
            'source' => 'owner',
            'status' => 'approved',
            'requires_customer_approval' => true,
            'reason' => 'Sudah disetujui customer pada versi sebelumnya',
            'snapshot' => ['order_id' => $order->id, 'order_number' => $order->order_number],
            'requested_at' => now()->subMinute(),
            'responded_at' => now(),
            'completed_at' => now(),
        ]);

        $invoice->delete();
        $payment->delete();
        $order->delete();

        $this->actingAs($owner)
            ->get(route('owner.customers.show', $customer))
            ->assertOk()
            ->assertSee('Hapus customer')
            ->assertSee('1 pembayaran');

        $this->actingAs($owner)
            ->delete(route('owner.customers.destroy', $customer))
            ->assertRedirect(route('owner.customers.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }
}
