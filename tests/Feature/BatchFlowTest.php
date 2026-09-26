<?php

namespace Tests\Feature;

use App\Models\{Batch, Country, GoGroup, Shipment, User, Warehouse};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_code_is_generated_and_public_tracking_is_created(): void
    {
        $owner = User::create([
            'name' => 'Owner Test',
            'email' => 'owner@test.local',
            'password' => 'password1234',
            'role' => 'owner',
            'active' => true,
            'email_verified_at' => now(),
        ]);

        $country = Country::create([
            'code' => 'KR',
            'name' => 'Korea Selatan',
            'currency_code' => 'KRW',
            'rate' => 12,
            'admin_fee_idr' => 25000,
            'active' => true,
        ]);

        $warehouse = Warehouse::create([
            'country_id' => $country->id,
            'code' => 'KR-WH01',
            'name' => 'Warehouse Korea',
            'active' => true,
        ]);

        $go = GoGroup::create(['name' => 'GBUKPOP', 'status' => 'active']);

        $response = $this->actingAs($owner)->post(route('owner.batches.store'), [
            'name' => 'CORTIS Seller Batch',
            'country_id' => $country->id,
            'warehouse_id' => $warehouse->id,
            'go_group_id' => $go->id,
            'tracking_number' => 'KR-TRACK-001',
        ]);

        $batch = Batch::firstOrFail();
        $response->assertRedirect(route('owner.batches.show', $batch));
        $this->assertSame('KR-GBUKPOP-001', $batch->code);

        $shipment = Shipment::where('source_type', 'batch')->where('source_id', $batch->id)->firstOrFail();
        $this->assertSame($batch->code, $shipment->reference);
        $this->assertSame('KR-TRACK-001', $shipment->tracking_number);
        $this->assertTrue($shipment->visible_publicly);

        $this->actingAs($owner)->get(route('owner.batches.show', $batch))->assertOk()->assertSee($batch->code);
        $this->get(route('tracking'))->assertOk()->assertSee($batch->code);
    }

    public function test_warehouse_must_belong_to_selected_country(): void
    {
        $owner = User::create([
            'name' => 'Owner Test',
            'email' => 'owner2@test.local',
            'password' => 'password1234',
            'role' => 'owner',
            'active' => true,
            'email_verified_at' => now(),
        ]);

        $kr = Country::create(['code' => 'KR', 'name' => 'Korea', 'currency_code' => 'KRW', 'rate' => 12, 'admin_fee_idr' => 0, 'active' => true]);
        $ph = Country::create(['code' => 'PH', 'name' => 'Filipina', 'currency_code' => 'PHP', 'rate' => 290, 'admin_fee_idr' => 0, 'active' => true]);
        $krWarehouse = Warehouse::create(['country_id' => $kr->id, 'code' => 'KR-WH01', 'name' => 'Korea WH', 'active' => true]);

        $response = $this->actingAs($owner)->from(route('owner.batches.create'))->post(route('owner.batches.store'), [
            'name' => 'Filipina Batch',
            'country_id' => $ph->id,
            'warehouse_id' => $krWarehouse->id,
        ]);

        $response->assertRedirect(route('owner.batches.create'));
        $response->assertSessionHasErrors('warehouse_id');
        $this->assertDatabaseCount('batches', 0);
    }
}
