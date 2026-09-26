<?php
namespace Tests\Feature;
use App\Models\{Batch,Country,StatusDefinition,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomStatusTest extends TestCase
{
    use RefreshDatabase;
    public function test_owner_can_create_colored_status_and_use_it_on_batch(): void
    {
        $owner=User::factory()->create(['role'=>'owner','email_verified_at'=>now()]);
        $country=Country::create(['code'=>'KR','name'=>'Korea','currency_code'=>'KRW','rate'=>12,'admin_fee_idr'=>0,'active'=>true]);
        $batch=Batch::create(['country_id'=>$country->id,'code'=>'KR-STATUS','name'=>'Status Demo','status'=>'ordered']);

        $this->actingAs($owner)->post(route('owner.master.status'),['code'=>'packing now','label'=>'Packing Now','color'=>'#ec4899','sort_order'=>55,'active'=>1])->assertRedirect();
        $this->assertDatabaseHas('status_definitions',['code'=>'packing_now','label'=>'Packing Now','color'=>'#ec4899','active'=>1]);

        $this->actingAs($owner)->patch(route('owner.batches.status',$batch),['status'=>'packing_now'])->assertRedirect();
        $this->assertDatabaseHas('batches',['id'=>$batch->id,'status'=>'packing_now']);
    }
}
