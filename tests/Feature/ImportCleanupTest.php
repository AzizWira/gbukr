<?php

namespace Tests\Feature;

use App\Models\{Batch, Country, GoGroup, ImportRun, Invoice, Order, OrderDeletionRequest, OrderItem, Payment, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportCleanupTest extends TestCase
{
    use RefreshDatabase;

    private function base(string $name='cleanup.xlsx'): array
    {
        $owner=User::create(['name'=>'Owner','email'=>uniqid('owner').'@test.local','password'=>'password1234','role'=>'owner','active'=>true,'email_verified_at'=>now()]);
        $country=Country::create(['code'=>'CH','name'=>'China','currency_code'=>'CNY','rate'=>2400,'admin_fee_idr'=>0,'active'=>true]);
        $go=GoGroup::create(['name'=>uniqid('GO-'),'status'=>'active']);
        $run=ImportRun::create(['user_id'=>$owner->id,'go_group_id'=>$go->id,'original_name'=>$name,'stored_path'=>'imports/'.uniqid().'.xlsx','status'=>'completed','total_rows'=>1,'processed_rows'=>1]);
        $batch=Batch::create(['go_group_id'=>$go->id,'country_id'=>$country->id,'code'=>uniqid('CH-LEG-G1-'),'name'=>'Legacy','status'=>'ordered']);
        return [$owner,$country,$go,$run,$batch];
    }

    public function test_old_import_without_snapshot_is_reviewed_manually_and_can_be_cleaned(): void
    {
        [$owner,,$go,$run,$batch]=$this->base();
        $customer=User::create(['name'=>'Legacy Edit','email'=>'legacy+'.uniqid().'@placeholder.local','password'=>null,'role'=>'customer','active'=>true]);
        $order=Order::create(['customer_id'=>$customer->id,'go_group_id'=>$go->id,'batch_id'=>$batch->id,'import_run_id'=>$run->id,'source_type'=>'batch','order_number'=>'LEG-CH-CLEAN','status'=>'ordered','notes'=>'Migrasi cleanup.xlsx | TAGIHAN CH | Ref: 99']);
        OrderItem::create(['order_id'=>$order->id,'item_name'=>'Album','qty'=>1]);
        Invoice::create(['customer_id'=>$customer->id,'order_id'=>$order->id,'import_run_id'=>$run->id,'invoice_number'=>'LEG-INV-CLEAN','type'=>'pelunasan','amount'=>50000,'paid_amount'=>0,'status'=>'unpaid']);

        $review=$this->actingAs($owner)->getJson(route('owner.import.cleanup.review',$run));
        $review->assertOk()->assertJsonPath('review_count',1)->assertJsonPath('safe_count',0);

        $this->actingAs($owner)->delete(route('owner.import.cleanup',$run),['review_order_ids'=>[$order->id],'reason'=>'Nama customer sudah dikoreksi, data tetap perlu dibersihkan'])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('orders',['id'=>$order->id]);
        $this->assertSame('rolled_back',$run->fresh()->status);
    }

    public function test_linked_paid_import_creates_customer_approval_instead_of_forcing_delete(): void
    {
        [$owner,,$go,$run,$batch]=$this->base('paid.xlsx');
        $customer=User::create(['name'=>'Linked Paid','email'=>'linked@example.test','password'=>'password1234','role'=>'customer','active'=>true,'email_verified_at'=>now()]);
        $order=Order::create(['customer_id'=>$customer->id,'go_group_id'=>$go->id,'batch_id'=>$batch->id,'import_run_id'=>$run->id,'source_type'=>'batch','order_number'=>'LEG-PAID','status'=>'ordered','notes'=>'Migrasi paid.xlsx | TAGIHAN CH | Ref: 1']);
        OrderItem::create(['order_id'=>$order->id,'item_name'=>'Album','qty'=>1]);
        $invoice=Invoice::create(['customer_id'=>$customer->id,'order_id'=>$order->id,'import_run_id'=>$run->id,'invoice_number'=>'LEG-INV-PAID','type'=>'pelunasan','amount'=>50000,'paid_amount'=>50000,'status'=>'paid']);
        $payment=Payment::create(['customer_id'=>$customer->id,'payment_number'=>'PAY-LEG-PAID','amount'=>50000,'status'=>'approved']);
        $invoice->payments()->attach($payment->id,['allocated_amount'=>50000]);

        $this->actingAs($owner)->delete(route('owner.import.cleanup',$run),['review_order_ids'=>[$order->id],'reason'=>'Duplikat hasil import'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('orders',['id'=>$order->id]);
        $this->assertDatabaseHas('order_deletion_requests',['order_id'=>$order->id,'status'=>'pending','import_run_id'=>$run->id]);
        $this->assertSame('completed',$run->fresh()->status);
    }
    public function test_import_cleanup_dialog_has_select_all_control(): void
    {
        [$owner]=$this->base('select-all.xlsx');

        $this->actingAs($owner)
            ->get(route('owner.import.index'))
            ->assertOk()
            ->assertSee('Pilih semua')
            ->assertSee('data-cleanup-select-all', false);
    }

    public function test_cleanup_selection_payload_is_not_limited_to_500_items(): void
    {
        [$owner,,$go,$run,$batch]=$this->base('large-review.xlsx');
        $customer=User::create(['name'=>'Legacy Large Review','email'=>'legacy+'.uniqid().'@placeholder.local','password'=>null,'role'=>'customer','active'=>true]);
        $order=Order::create(['customer_id'=>$customer->id,'go_group_id'=>$go->id,'batch_id'=>$batch->id,'import_run_id'=>$run->id,'source_type'=>'batch','order_number'=>'LEG-LARGE-REVIEW','status'=>'ordered','notes'=>'Migrasi large-review.xlsx | TAGIHAN CH | Ref: 1']);

        $ids=array_fill(0, 650, $order->id);

        $this->actingAs($owner)
            ->delete(route('owner.import.cleanup',$run),[
                'review_order_ids_json'=>json_encode($ids),
                'reason'=>'Cleanup review dalam jumlah besar',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('orders',['id'=>$order->id]);
    }

}
