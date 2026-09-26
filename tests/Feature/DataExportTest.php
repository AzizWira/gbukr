<?php

namespace Tests\Feature;

use App\Models\{GoGroup, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_download_full_backup_workbook(): void
    {
        $owner = User::create([
            'name' => 'Owner Export',
            'email' => 'owner-export@test.local',
            'password' => 'password1234',
            'role' => 'owner',
            'active' => true,
            'email_verified_at' => now(),
        ]);
        GoGroup::create(['name' => 'CORTIS', 'status' => 'active']);

        $response = $this->actingAs($owner)->post(route('owner.export.full'));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $response->headers->get('content-type')
        );
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
    }

    public function test_customer_cannot_export_backup(): void
    {
        $customer = User::create([
            'name' => 'Customer Export',
            'email' => 'customer-export@test.local',
            'password' => 'password1234',
            'role' => 'customer',
            'active' => true,
            'email_verified_at' => now(),
        ]);
        $customer->customerProfile()->create([]);

        $this->actingAs($customer)
            ->withSession(['acting_as' => 'customer'])
            ->post(route('owner.export.full'))
            ->assertForbidden();
    }
}
