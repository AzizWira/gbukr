<?php
namespace Tests\Feature;
use App\Models\{Country,Product,ProductVariant,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartUxTest extends TestCase
{
    use RefreshDatabase;
    public function test_add_to_cart_returns_to_product_page_and_keeps_cart_in_session(): void
    {
        $u=User::factory()->create(['role'=>'customer','email_verified_at'=>now()]);
        $country=Country::create(['code'=>'JP','name'=>'Jepang','currency_code'=>'JPY','rate'=>110,'admin_fee_idr'=>0,'active'=>true]);
        $product=Product::create(['country_id'=>$country->id,'type'=>'ready','name'=>'Demo Cart','slug'=>'demo-cart','active'=>true,'tax_status'=>'excluded','payment_scheme'=>['type'=>'full']]);
        $variant=ProductVariant::create(['product_id'=>$product->id,'name'=>'Blue','price_idr'=>85000,'stock'=>5,'active'=>true]);
        $this->actingAs($u)->from(route('catalog.show',$product))->post(route('cart.store'),['variant_id'=>$variant->id,'qty'=>2])
            ->assertRedirect(route('catalog.show',$product))->assertSessionHas('cart_added',true);
        $this->assertSame(2,session('cart')[$variant->id]);
    }
}
