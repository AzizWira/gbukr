<?php
namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\{Invoice,Order,OrderAdjustment};
use App\Notifications\InvoiceCreatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdjustmentController extends Controller
{
    public function store(Request $request, Order $order)
    {
        $data=$request->validate([
            'reason'=>['required',Rule::in(['weight','rate','weight_rate','tax','shipping_actual','other'])],
            'amount_idr'=>['required','integer','min:1','max:999999999'],
            'estimated_weight_grams'=>['nullable','integer','min:1','max:1000000'],
            'actual_weight_grams'=>['nullable','integer','min:1','max:1000000'],
            'estimated_shipping_idr'=>['nullable','integer','min:0','max:999999999'],
            'actual_shipping_idr'=>['nullable','integer','min:0','max:999999999'],
            'original_rate'=>['nullable','numeric','min:0.0001','max:999999999'],
            'final_rate'=>['nullable','numeric','min:0.0001','max:999999999'],
            'deadline_at'=>['nullable','date'],
            'notes'=>['nullable','string','max:2000'],
        ]);
        $this->validateContext($data);

        $invoice=DB::transaction(function()use($request,$order,$data){
            $invoice=Invoice::create(['customer_id'=>$order->customer_id,'order_id'=>$order->id,'invoice_number'=>$this->number(),'type'=>'kekurangan','amount'=>$data['amount_idr'],'paid_amount'=>0,'penalty_amount'=>0,'deadline_at'=>$data['deadline_at']??null,'status'=>'unpaid','notes'=>trim('Tagihan tambahan: '.$this->label($data['reason']).'. '.($data['notes']??''))]);
            OrderAdjustment::create(['order_id'=>$order->id,'invoice_id'=>$invoice->id,'type'=>'kekurangan','reason'=>$data['reason'],'amount_idr'=>$data['amount_idr'],'estimated_weight_grams'=>$data['estimated_weight_grams']??null,'actual_weight_grams'=>$data['actual_weight_grams']??null,'estimated_shipping_idr'=>$data['estimated_shipping_idr']??null,'actual_shipping_idr'=>$data['actual_shipping_idr']??null,'original_rate'=>$data['original_rate']??$order->rate_snapshot,'final_rate'=>$data['final_rate']??null,'notes'=>$data['notes']??null,'created_by'=>$request->user()->id]);
            return $invoice;
        });
        $invoice->load('customer'); $invoice->customer?->notify(new InvoiceCreatedNotification($invoice));
        return redirect()->route('owner.orders.show',$order)->with('success','Tagihan tambahan berhasil dibuat.');
    }

    private function validateContext(array $d):void
    {
        $err=[];$r=$d['reason'];
        if (in_array($r, ['weight', 'weight_rate'], true)) {
            if (empty($d['estimated_weight_grams'])) {
                $err['estimated_weight_grams'] = 'Berat estimasi wajib diisi.';
            }
            if (empty($d['actual_weight_grams'])) {
                $err['actual_weight_grams'] = 'Berat aktual wajib diisi.';
            }
            if (!empty($d['estimated_weight_grams']) && !empty($d['actual_weight_grams'])
                && (int) $d['actual_weight_grams'] < (int) $d['estimated_weight_grams']) {
                $err['actual_weight_grams'] = 'Berat aktual tidak boleh lebih kecil dari berat estimasi untuk penyesuaian kenaikan.';
            }
        }
        if(in_array($r,['rate','weight_rate'],true)){if(empty($d['original_rate']))$err['original_rate']='Rate awal wajib diisi.';if(empty($d['final_rate']))$err['final_rate']='Rate akhir wajib diisi.';}
        if($r==='shipping_actual' && !isset($d['actual_shipping_idr']))$err['actual_shipping_idr']='Shipping aktual wajib diisi.';
        if($r==='other' && empty(trim((string)($d['notes']??''))))$err['notes']='Catatan wajib diisi untuk jenis Lainnya.';
        if($err)throw ValidationException::withMessages($err);
    }
    private function number():string{do{$n='INV-TMB-'.now()->format('ymd').'-'.strtoupper(Str::random(6));}while(Invoice::where('invoice_number',$n)->exists());return $n;}
    private function label(string $r):string{return match($r){'tax'=>'Tax / Pajak','shipping_actual'=>'Shipping Aktual','weight'=>'Penyesuaian Berat','rate'=>'Penyesuaian Rate','weight_rate'=>'Berat + Rate',default=>'Kekurangan Lainnya'};}
}
