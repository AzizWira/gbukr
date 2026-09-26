<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_enabled_member_must_choose_mode_and_can_use_both_sides(): void
    {
        $member = User::create([
            'name' => 'Member Admin',
            'email' => 'member-admin@test.local',
            'password' => 'password1234',
            'role' => 'customer',
            'admin_enabled' => true,
            'active' => true,
            'email_verified_at' => now(),
        ]);
        $member->customerProfile()->create(['username' => 'memberadmin']);

        $this->actingAs($member)
            ->get(route('customer.dashboard'))
            ->assertRedirect(route('auth.mode'));

        $this->actingAs($member)
            ->post(route('auth.mode.store'), ['mode' => 'customer'])
            ->assertRedirect(route('customer.dashboard'));

        $this->actingAs($member)
            ->withSession(['acting_as' => 'customer'])
            ->get(route('customer.dashboard'))
            ->assertOk();

        $this->actingAs($member)
            ->withSession(['acting_as' => 'customer'])
            ->get(route('owner.dashboard'))
            ->assertRedirect(route('auth.mode'));

        $this->actingAs($member)
            ->withSession(['acting_as' => 'admin'])
            ->get(route('owner.dashboard'))
            ->assertOk();

        $this->actingAs($member)
            ->withSession(['acting_as' => 'admin'])
            ->get(route('owner.products.create'))
            ->assertForbidden();

        $this->actingAs($member)
            ->withSession(['acting_as' => 'admin'])
            ->get(route('cart.index'))
            ->assertRedirect(route('auth.mode'));
    }

    public function test_owner_can_promote_existing_customer_without_changing_customer_role(): void
    {
        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner-role@test.local',
            'password' => 'password1234',
            'role' => 'owner',
            'active' => true,
            'email_verified_at' => now(),
        ]);

        $member = User::create([
            'name' => 'Member',
            'email' => 'member@test.local',
            'password' => 'password1234',
            'role' => 'customer',
            'admin_enabled' => false,
            'active' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($owner)
            ->post(route('owner.admins.store'), ['user_id' => $member->id])
            ->assertRedirect();

        $member->refresh();
        $this->assertSame('customer', $member->role);
        $this->assertTrue($member->admin_enabled);
    }
}
