<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_open_owner_pages(): void
    {
        $customer = $this->customer('customer-auth@test.local');

        $this->actingAs($customer)
            ->withSession(['acting_as' => 'customer'])
            ->get(route('owner.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_mode_cannot_open_owner_only_configuration(): void
    {
        $adminMember = $this->customer('admin-auth@test.local', true);

        $this->actingAs($adminMember)
            ->withSession(['acting_as' => 'admin'])
            ->get(route('owner.master.index'))
            ->assertForbidden();
    }

    private function customer(string $email, bool $adminEnabled = false): User
    {
        $user = User::create([
            'name' => 'Auth Test',
            'email' => $email,
            'password' => 'password1234',
            'role' => 'customer',
            'admin_enabled' => $adminEnabled,
            'active' => true,
            'email_verified_at' => now(),
        ]);
        $user->customerProfile()->create([]);

        return $user;
    }
}
