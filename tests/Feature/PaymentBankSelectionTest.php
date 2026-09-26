<?php
namespace Tests\Feature;
use App\Models\{BankAccount,Invoice,Order,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentBankSelectionTest extends TestCase
{
    use RefreshDatabase;
    public function test_customer_must_choose_active_bank_and_payment_remembers_it(): void
    {
        Notification::fake(); Storage::fake('local');
        $u=User::factory()->create(['role'=>'customer','email_verified_at'=>now()]);
        $order=Order::create(['customer_id'=>$u->id,'source_type'=>'batch','order_number'=>'ORD-PAY-BANK','status'=>'ordered','currency_code'=>'KRW']);
        $invoice=Invoice::create(['customer_id'=>$u->id,'order_id'=>$order->id,'invoice_number'=>'INV-PAY-BANK','type'=>'pelunasan','amount'=>50000,'status'=>'unpaid']);
        $active=BankAccount::create(['bank_name'=>'BCA','account_number'=>'123','account_name'=>'GBUKR','active'=>true]);
        $inactive=BankAccount::create(['bank_name'=>'BNI','account_number'=>'999','account_name'=>'GBUKR','active'=>false]);

        $this->actingAs($u)->post(route('customer.payments.store'),[
            'invoice_ids'=>[$invoice->id],'bank_account_id'=>$inactive->id,'proof'=>UploadedFile::fake()->image('proof.jpg'),
        ])->assertSessionHasErrors('bank_account_id');

        $this->actingAs($u)->post(route('customer.payments.store'),[
            'invoice_ids'=>[$invoice->id],'bank_account_id'=>$active->id,'proof'=>UploadedFile::fake()->image('proof.jpg'),
        ])->assertRedirect(route('customer.invoices.index'));

        $this->assertDatabaseHas('payments',['customer_id'=>$u->id,'bank_account_id'=>$active->id,'status'=>'pending']);
    }
}
