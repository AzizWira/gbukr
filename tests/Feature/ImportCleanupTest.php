<?php

namespace Tests\Feature;

use App\Models\{Batch, Country, GoGroup, ImportRun, Invoice, Order, OrderItem, Payment, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_import_can_cleanup_unpaid_orders_and_empty_legacy_batch(): void
    {
        $owner = User::create(['name'=>'Owner','email'=>'owner-import-clean@test.local','password'=>'password1234','role'=>'owner','active'=>true,'email_verified_at'=>now()]);
        $customer = User::create(['name'=>'Legacy','email'=>'legacy-clean@test.local','password'=>null,'role'=>'customer','active'=>true]);
        $country = Country::create(['code'=>'CH','name'=>'China','currency_code'=>'CNY','rate'=>2400,'admin_fee_idr'=>0,'active'=>true]);
        $go = GoGroup::create(['name'=>'Cleanup GO','status'=>'active']);
        $run = ImportRun::create(['user_id'=>$owner->id,'go_group_id'=>$go->id,'original_name'=>'cleanup.xlsx','stored_path'=>'imports/cleanup.xlsx','status'=>'completed','total_rows'=>1,'processed_rows'=>1]);
        $batch = Batch::create(['go_group_id'=>$go->id,'country_id'=>$country->id,'code'=>'CH-LEG-G1-99','name'=>'Legacy 99','status'=>'ordered']);
        $order = Order::create(['customer_id'=>$customer->id,'go_group_id'=>$go->id,'batch_id'=>$batch->id,'import_run_id'=>$run->id,'source_type'=>'batch','order_number'=>'LEG-CH-CLEAN','status'=>'ordered','notes'=>'Migrasi cleanup.xlsx | TAGIHAN CH | Ref: 99']);
        OrderItem::create(['order_id'=>$order->id,'item_name'=>'Album','qty'=>1]);
        Invoice::create(['customer_id'=>$customer->id,'order_id'=>$order->id,'import_run_id'=>$run->id,'invoice_number'=>'LEG-INV-CLEAN','type'=>'pelunasan','amount'=>50000,'paid_amount'=>0,'status'=>'unpaid']);

        $response = $this->actingAs($owner)->delete(route('owner.import.cleanup',$run));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('orders',['id'=>$order->id]);
        $this->assertDatabaseMissing('batches',['id'=>$batch->id]);
        $this->assertSame('rolled_back',$run->fresh()->status);
    }

    public function test_cleanup_keeps_paid_financial_history_but_removes_legacy_batch_relation(): void
    {
        $owner = User::create(['name'=>'Owner','email'=>'owner-import-paid@test.local','password'=>'password1234','role'=>'owner','active'=>true,'email_verified_at'=>now()]);
        $customer = User::create(['name'=>'Legacy Paid','email'=>'legacy-paid@test.local','password'=>null,'role'=>'customer','active'=>true]);
        $country = Country::create(['code'=>'KR','name'=>'Korea','currency_code'=>'KRW','rate'=>12,'admin_fee_idr'=>0,'active'=>true]);
        $go = GoGroup::create(['name'=>'Paid GO','status'=>'active']);
        $run = ImportRun::create(['user_id'=>$owner->id,'go_group_id'=>$go->id,'original_name'=>'paid.xlsx','stored_path'=>'imports/paid.xlsx','status'=>'completed','total_rows'=>1,'processed_rows'=>1]);
        $batch = Batch::create(['go_group_id'=>$go->id,'country_id'=>$country->id,'code'=>'KR-LEG-G1-PAID','name'=>'Legacy Paid','status'=>'ordered']);
        $order = Order::create(['customer_id'=>$customer->id,'go_group_id'=>$go->id,'batch_id'=>$batch->id,'import_run_id'=>$run->id,'source_type'=>'batch','order_number'=>'LEG-KR-PAID','status'=>'ordered','notes'=>'Migrasi paid.xlsx | TAGIHAN KR | Ref: PAID']);
        OrderItem::create(['order_id'=>$order->id,'item_name'=>'Album','qty'=>1]);
        $invoice = Invoice::create(['customer_id'=>$customer->id,'order_id'=>$order->id,'import_run_id'=>$run->id,'invoice_number'=>'LEG-INV-PAID','type'=>'pelunasan','amount'=>50000,'paid_amount'=>50000,'status'=>'paid']);
        $payment = Payment::create(['customer_id'=>$customer->id,'payment_number'=>'PAY-LEG-PAID','amount'=>50000,'status'=>'approved']);
        $invoice->payments()->attach($payment->id,['allocated_amount'=>50000]);

        $response = $this->actingAs($owner)->delete(route('owner.import.cleanup',$run));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('batches',['id'=>$batch->id]);
        $this->assertDatabaseHas('orders',['id'=>$order->id,'batch_id'=>null]);
        $this->assertDatabaseHas('invoices',['id'=>$invoice->id,'status'=>'paid']);
        $this->assertDatabaseHas('payments',['id'=>$payment->id,'status'=>'approved']);
        $this->assertSame('rolled_back',$run->fresh()->status);
    }
}
