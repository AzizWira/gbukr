<?php

namespace Tests\Feature;

use App\Models\{BankAccount, Batch, Country, ShippingOption, User, Warehouse};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_edit_and_deactivate_master_data_without_hard_delete(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'active' => true,
            'email_verified_at' => now(),
        ]);
        $country = Country::create([
            'code' => 'KR',
            'name' => 'Korea Selatan',
            'currency_code' => 'KRW',
            'currency_symbol' => '₩',
            'rate' => 12,
            'admin_fee_idr' => 25000,
            'active' => true,
        ]);

        $this->actingAs($owner)->post(route('owner.master.shipping'), [
            'country_id' => $country->id,
            'label' => 'Shipping Demo',
            'amount_foreign' => 5000,
        ])->assertSessionHasNoErrors();

        $shipping = ShippingOption::firstOrFail();
        $this->assertTrue($shipping->active);

        $this->actingAs($owner)->post(route('owner.master.shipping', $shipping), [
            'country_id' => $country->id,
            'label' => 'Shipping Edit',
            'amount_foreign' => 0,
        ])->assertSessionHasNoErrors();

        $this->assertSame('Shipping Edit', $shipping->fresh()->label);
        $this->assertEquals(0, (float) $shipping->fresh()->amount_foreign);

        $this->actingAs($owner)->post(route('owner.master.shipping.toggle', $shipping));
        $this->assertFalse($shipping->fresh()->active);

        $this->actingAs($owner)->post(route('owner.master.bank'), [
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'GBUKR Demo',
        ])->assertSessionHasNoErrors();

        $bank = BankAccount::firstOrFail();
        $this->actingAs($owner)->post(route('owner.master.bank', $bank), [
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'GBUKR Updated',
        ])->assertSessionHasNoErrors();
        $this->assertSame('GBUKR Updated', $bank->fresh()->account_name);
    }

    public function test_unused_master_data_can_be_hard_deleted_but_used_data_is_blocked(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'active' => true,
            'email_verified_at' => now(),
        ]);
        $country = Country::create([
            'code' => 'US',
            'name' => 'Amerika Serikat',
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'rate' => 17000,
            'admin_fee_idr' => 10000,
            'active' => true,
        ]);

        $shipping = ShippingOption::create([
            'country_id' => $country->id,
            'label' => 'Temporary',
            'amount_foreign' => 10,
            'active' => true,
        ]);

        $this->actingAs($owner)
            ->delete(route('owner.master.shipping.destroy', $shipping))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('shipping_options', ['id' => $shipping->id]);

        $warehouse = Warehouse::create([
            'country_id' => $country->id,
            'code' => 'US-TEMP',
            'name' => 'Temporary Warehouse',
            'active' => true,
        ]);
        Batch::create([
            'country_id' => $country->id,
            'warehouse_id' => $warehouse->id,
            'code' => 'US-DEMO-001',
            'name' => 'Demo Batch',
            'status' => 'ordered',
        ]);

        $response = $this->actingAs($owner)
            ->from(route('owner.data.index'))
            ->delete(route('owner.master.warehouse.destroy', $warehouse));

        $response->assertRedirect(route('owner.data.index'));
        $response->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id]);
    }
}
