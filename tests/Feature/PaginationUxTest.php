<?php

namespace Tests\Feature;

use App\Models\{Country, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationUxTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_page_supports_per_page_and_direct_page_input_ui(): void
    {
        $owner = User::create(['name'=>'Owner','email'=>'owner-page@test.local','password'=>'password1234','role'=>'owner','active'=>true,'email_verified_at'=>now()]);
        Country::create(['code'=>'KR','name'=>'Korea','currency_code'=>'KRW','rate'=>12,'admin_fee_idr'=>0,'active'=>true]);

        $response = $this->actingAs($owner)->get(route('owner.orders.index',['per_page'=>50]));
        $response->assertOk()->assertSee('50 / halaman')->assertSee('Halaman');
    }
}
