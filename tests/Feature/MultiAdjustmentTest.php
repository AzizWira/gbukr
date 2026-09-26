<?php
namespace Tests\Feature;
use App\Models\{Batch,Country,Order,OrderItem,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MultiAdjustmentTest extends TestCase
{
    use RefreshDatabase;
    public function test_same_order_can_have_tax_and_rate_as_separate_adjustments(): void
    {
        Notification::fake();
        $owner=User::factory()->create(['role'=>'owner','email_verified_at'=>now()]);
        $customer=User::factory()->create(['role'=>'customer','email_verified_at'=>now()]);
        $country=Country::create(['code'=>'KR','name'=>'Korea','currency_code'=>'KRW','rate'=>12,'admin_fee_idr'=>0,'active'=>true]);
        $batch=Batch::create(['country_id'=>$country->id,'code'=>'KR-MULTI','name'=>'Multi','status'=>'arrived_indo']);
        $order=Order::create(['customer_id'=>$customer->id,'batch_id'=>$batch->id,'source_type'=>'batch','order_number'=>'ORD-MULTI','status'=>'arrived_indo','currency_code'=>'KRW','rate_snapshot'=>12]);
        OrderItem::create(['order_id'=>$order->id,'item_name'=>'Album','qty'=>1]);

        $this->actingAs($owner)->post(route('owner.orders.adjustments.store',$order),['reason'=>'tax','amount_idr'=>25000])->assertRedirect(route('owner.orders.show',$order));
        $this->actingAs($owner)->post(route('owner.orders.adjustments.store',$order),['reason'=>'rate','amount_idr'=>15000,'original_rate'=>12,'final_rate'=>12.5])->assertRedirect(route('owner.orders.show',$order));
        $this->assertDatabaseCount('order_adjustments',2);
        $this->assertDatabaseHas('order_adjustments',['order_id'=>$order->id,'reason'=>'tax','amount_idr'=>25000]);
        $this->assertDatabaseHas('order_adjustments',['order_id'=>$order->id,'reason'=>'rate','amount_idr'=>15000]);
    }

    public function test_rate_adjustment_requires_rate_fields(): void
    {
        $owner=User::factory()->create(['role'=>'owner','email_verified_at'=>now()]);
        $customer=User::factory()->create(['role'=>'customer','email_verified_at'=>now()]);
        $order=Order::create(['customer_id'=>$customer->id,'source_type'=>'batch','order_number'=>'ORD-RATE-VALIDATE','status'=>'ordered','currency_code'=>'KRW']);
        $this->actingAs($owner)->from(route('owner.orders.show',$order))->post(route('owner.orders.adjustments.store',$order),['reason'=>'rate','amount_idr'=>10000])->assertSessionHasErrors(['original_rate','final_rate']);
    }
}
