<?php

namespace Tests\Feature;

use App\Models\{Batch, Country, Invoice, Order, OrderDeletionRequest, OrderItem, Payment, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCleanupUxTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::create(['name'=>'Owner Cleanup','email'=>'owner-cleanup@test.local','password'=>'password1234','role'=>'owner','active'=>true,'email_verified_at'=>now()]);
    }

    private function batchOrder(bool $withPayment=false, bool $placeholder=false): array
    {
        $customer=User::create([
            'name'=>'Customer Cleanup',
            'email'=>$placeholder ? 'legacy+'.uniqid().'@placeholder.local' : uniqid('customer',true).'@test.local',
            'password'=>$placeholder ? null : 'password1234','role'=>'customer','active'=>true,
            'email_verified_at'=>$placeholder ? null : now(),
        ]);
        $country=Country::create(['code'=>'KR','name'=>'Korea','currency_code'=>'KRW','rate'=>12,'admin_fee_idr'=>0,'active'=>true]);
        $batch=Batch::create(['country_id'=>$country->id,'code'=>uniqid('KR-TEST-'),'name'=>'Cleanup Batch','status'=>'ordered']);
        $order=Order::create(['customer_id'=>$customer->id,'batch_id'=>$batch->id,'source_type'=>'batch','order_number'=>uniqid('ORD-B-'),'status'=>'ordered']);
        OrderItem::create(['order_id'=>$order->id,'item_name'=>'Album','qty'=>1]);
        $invoice=Invoice::create(['customer_id'=>$customer->id,'order_id'=>$order->id,'invoice_number'=>uniqid('INV-'),'type'=>'pelunasan','amount'=>100000,'paid_amount'=>$withPayment?100000:0,'status'=>$withPayment?'paid':'unpaid']);
        $payment=null;
        if($withPayment){
            $payment=Payment::create(['customer_id'=>$customer->id,'payment_number'=>uniqid('PAY-'),'amount'=>100000,'status'=>'approved']);
            $invoice->payments()->attach($payment->id,['allocated_amount'=>100000]);
        }
        return [$batch,$order,$invoice,$payment,$customer];
    }

    public function test_linked_order_without_payment_is_deleted_and_audited(): void
    {
        [, $order] = $this->batchOrder(false,false);
        $response=$this->actingAs($this->owner())->delete(route('owner.orders.destroy',$order),['reason'=>'Salah input']);
        $response->assertSessionHasNoErrors()->assertRedirect(route('owner.orders.index'));
        $this->assertDatabaseMissing('orders',['id'=>$order->id]);
        $this->assertDatabaseHas('order_deletion_requests',['status'=>'executed','requires_customer_approval'=>0]);
    }

    public function test_linked_order_with_approved_payment_requires_customer_approval(): void
    {
        [$batch,$order,$invoice,$payment,$customer]=$this->batchOrder(true,false);
        $response=$this->actingAs($this->owner())->delete(route('owner.orders.destroy',$order),['reason'=>'Data duplikat']);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('orders',['id'=>$order->id,'batch_id'=>$batch->id]);
        $request=OrderDeletionRequest::where('order_id',$order->id)->firstOrFail();
        $this->assertSame('pending',$request->status);

        $this->actingAs($customer)->post(route('customer.deletion-requests.approve',$request))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('orders',['id'=>$order->id]);
        $this->assertDatabaseMissing('invoices',['id'=>$invoice->id]);
        $this->assertDatabaseMissing('payments',['id'=>$payment->id]);
        $freshRequest=$request->fresh();
        $this->assertSame('approved',$freshRequest->status);
        $this->assertNull($freshRequest->order_id);
        $this->assertSame($order->order_number,$freshRequest->snapshot['order_number']);
    }

    public function test_unlinked_order_with_financial_history_can_be_deleted_without_customer_approval_but_is_soft_deleted(): void
    {
        [, $order,$invoice,$payment]=$this->batchOrder(true,true);
        $this->actingAs($this->owner())->delete(route('owner.orders.destroy',$order),['reason'=>'Cleanup data legacy'])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('orders',['id'=>$order->id]);
        $this->assertDatabaseMissing('invoices',['id'=>$invoice->id]);
        $this->assertDatabaseMissing('payments',['id'=>$payment->id]);
        $this->assertDatabaseHas('order_deletion_requests',['status'=>'executed','requires_customer_approval'=>0]);
    }

    public function test_batch_delete_waits_when_any_order_requires_customer_approval(): void
    {
        [$batch,$order]=$this->batchOrder(true,false);
        $response=$this->actingAs($this->owner())->delete(route('owner.batches.destroy',$batch));
        $response->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('batches',['id'=>$batch->id]);
        $this->assertDatabaseHas('orders',['id'=>$order->id,'batch_id'=>$batch->id]);
        $this->assertDatabaseHas('order_deletion_requests',['order_id'=>$order->id,'status'=>'pending']);
    }
}
