<?php

namespace Tests\Feature;

use App\Models\{Batch, Country, GoGroup, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_batch_search_finds_exact_legacy_code_and_compact_code(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'active' => true,
            'email_verified_at' => now(),
        ]);
        $country = Country::create([
            'code' => 'TH',
            'name' => 'Thailand',
            'currency_code' => 'THB',
            'currency_symbol' => '฿',
            'rate' => 470,
            'admin_fee_idr' => 15000,
            'active' => true,
        ]);
        $group = GoGroup::create(['name' => 'Demo GO', 'status' => 'active']);

        Batch::create([
            'go_group_id' => $group->id,
            'country_id' => $country->id,
            'code' => 'TH-LEG-G3-31',
            'name' => 'Legacy Batch 31',
            'status' => 'ordered',
        ]);
        Batch::create([
            'go_group_id' => $group->id,
            'country_id' => $country->id,
            'code' => 'TH-LEG-G3-99',
            'name' => 'Batch Lain',
            'status' => 'ordered',
        ]);

        $this->actingAs($owner)
            ->get(route('owner.batches.index', ['q' => 'TH-LEG-G3-31']))
            ->assertOk()
            ->assertSee('TH-LEG-G3-31')
            ->assertDontSee('TH-LEG-G3-99');

        $this->actingAs($owner)
            ->get(route('owner.batches.index', ['q' => 'thlegg331']))
            ->assertOk()
            ->assertSee('TH-LEG-G3-31');
    }

    public function test_filter_page_contains_clear_and_jump_page_controls(): void
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

        for ($i = 1; $i <= 22; $i++) {
            Batch::create([
                'country_id' => $country->id,
                'code' => 'KR-TEST-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'name' => 'Batch Test ' . $i,
                'status' => 'ordered',
            ]);
        }

        $this->actingAs($owner)
            ->get(route('owner.batches.index', ['q' => 'KR-TEST']))
            ->assertOk()
            ->assertSee('Clear')
            ->assertSee('dari 2')
            ->assertSee('name="page"', false);
    }
}
