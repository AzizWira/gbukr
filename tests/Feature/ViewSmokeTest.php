<?php

namespace Tests\Feature;

use App\Models\{Country, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_public_and_owner_pages_render_without_blade_errors(): void
    {
        $owner = User::create([
            'name' => 'Owner Smoke',
            'email' => 'owner-smoke@test.local',
            'password' => 'password1234',
            'role' => 'owner',
            'active' => true,
            'email_verified_at' => now(),
        ]);

        Country::create([
            'code' => 'KR',
            'name' => 'Korea Selatan',
            'currency_code' => 'KRW',
            'rate' => 12,
            'admin_fee_idr' => 25000,
            'active' => true,
        ]);

        $this->get(route('home'))->assertOk();
        $this->get(route('catalog.index'))->assertOk();
        $this->get(route('calculator'))->assertOk();
        $this->get(route('tracking'))->assertOk();

        foreach ([
            'owner.dashboard',
            'owner.batches.index',
            'owner.batches.create',
            'owner.products.index',
            'owner.products.create',
            'owner.orders.index',
            'owner.customers.index',
            'owner.invoices.index',
            'owner.payments.index',
            'owner.master.index',
            'owner.admins.index',
            'owner.import.index',
            'owner.export.index',
            'owner.tracking.index',
        ] as $route) {
            $this->actingAs($owner)->get(route($route))->assertOk();
        }
    }
}
