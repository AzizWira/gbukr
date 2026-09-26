<?php
namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\{Batch,Invoice,Order,OrderAdjustment};
use App\Notifications\InvoiceCreatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BatchAdjustmentController extends Controller
{
    public function store(Request $request,Batch $batch)
    {
        $data=$request->validate([
            'reason'=>['required',Rule::in(['tax','shipping_actual','weight','rate','weight_rate','other'])],
            'mode'=>['required',Rule::in(['per_item','per_customer','custom'])],
            'order_ids'=>['required','array','min:1'],'order_ids.*'=>['integer'],
            'shared_amount_idr'=>['nullable','integer','min:1','max:999999999'],'custom_amounts'=>['nullable','array'],'custom_amounts.*'=>['nullable','integer','min:1','max:999999999'],
            'estimated_weight_grams'=>['nullable','integer','min:1','max:1000000'],'actual_weight_grams'=>['nullable','integer','min:1','max:1000000'],
            'estimated_shipping_idr'=>['nullable','integer','min:0','max:999999999'],'actual_shipping_idr'=>['nullable','integer','min:0','max:999999999'],
            'original_rate'=>['nullable','numeric','min:0.0001','max:999999999'],'final_rate'=>['nullable','numeric','min:0.0001','max:999999999'],
            'deadline_at'=>['nullable','date'],'notes'=>['nullable','string','max:2000'],
        ]);
        if($data['mode']!=='custom'&&empty($data['shared_amount_idr']))throw ValidationException::withMessages(['shared_amount_idr'=>'Nominal wajib diisi untuk mode yang dipilih.']);
        $orders=Order::with(['customer','items'])->where('batch_id',$batch->id)->whereIn('id',$data['order_ids'])->get();
        if($orders->count()!==count(array_unique($data['order_ids'])))throw ValidationException::withMessages(['order_ids'=>'Ada order yang bukan bagian dari Batch ini.']);
        $this->validateContext($data);

        $invoices=DB::transaction(function()use($request,$batch,$orders,$data){$made=collect();foreach($orders as $order){$amount=$this->amount($order,$data);if($amount<1)throw ValidationException::withMessages(['custom_amounts.'.$order->id=>'Nominal untuk '.$order->customer->name.' wajib diisi.']);
            $invoice=Invoice::create(['customer_id'=>$order->customer_id,'order_id'=>$order->id,'invoice_number'=>$this->number(),'type'=>'kekurangan','amount'=>$amount,'paid_amount'=>0,'penalty_amount'=>0,'deadline_at'=>$data['deadline_at']??null,'status'=>'unpaid','notes'=>trim('Tagihan tambahan Batch '.$batch->code.': '.$this->label($data['reason']).'. '.($data['notes']??''))]);
            OrderAdjustment::create(['order_id'=>$order->id,'invoice_id'=>$invoice->id,'type'=>'kekurangan','reason'=>$data['reason'],'amount_idr'=>$amount,'estimated_weight_grams'=>$data['estimated_weight_grams']??null,'actual_weight_grams'=>$data['actual_weight_grams']??null,'estimated_shipping_idr'=>$data['estimated_shipping_idr']??null,'actual_shipping_idr'=>$data['actual_shipping_idr']??null,'original_rate'=>$data['original_rate']??$order->rate_snapshot,'final_rate'=>$data['final_rate']??null,'notes'=>trim('Bulk Batch '.$batch->code.'. '.($data['notes']??'')),'created_by'=>$request->user()->id]);$made->push($invoice);}return $made;});
        $invoices->each(fn($i)=>$i->load('customer')->customer?->notify(new InvoiceCreatedNotification($i)));
        return redirect()->route('owner.batches.show',$batch)->with('success',$invoices->count().' tagihan tambahan berhasil dibuat.');
    }
    private function validateContext(array $d): void
    {
        $e = [];
        $r = $d['reason'];

        if (in_array($r, ['weight', 'weight_rate'], true)) {
            if (empty($d['estimated_weight_grams'])) {
                $e['estimated_weight_grams'] = 'Berat estimasi wajib diisi.';
            }
            if (empty($d['actual_weight_grams'])) {
                $e['actual_weight_grams'] = 'Berat aktual wajib diisi.';
            }
            if (!empty($d['estimated_weight_grams']) && !empty($d['actual_weight_grams'])
                && (int) $d['actual_weight_grams'] < (int) $d['estimated_weight_grams']) {
                $e['actual_weight_grams'] = 'Berat aktual tidak boleh lebih kecil dari berat estimasi untuk penyesuaian kenaikan.';
            }
        }

        if (in_array($r, ['rate', 'weight_rate'], true)) {
            if (empty($d['original_rate'])) {
                $e['original_rate'] = 'Rate awal wajib diisi.';
            }
            if (empty($d['final_rate'])) {
                $e['final_rate'] = 'Rate akhir wajib diisi.';
            }
        }

        if ($r === 'shipping_actual' && !isset($d['actual_shipping_idr'])) {
            $e['actual_shipping_idr'] = 'Shipping aktual wajib diisi.';
        }
        if ($r === 'other' && empty(trim((string) ($d['notes'] ?? '')))) {
            $e['notes'] = 'Catatan wajib diisi untuk jenis Lainnya.';
        }

        if ($e) {
            throw ValidationException::withMessages($e);
        }
    }
    private function amount(Order $o,array $d):int{return match($d['mode']){'per_item'=>(int)$d['shared_amount_idr']*max(1,(int)$o->items->sum('qty')),'per_customer'=>(int)$d['shared_amount_idr'],default=>(int)($d['custom_amounts'][$o->id]??0)};}
    private function number():string{do{$n='INV-TMB-'.now()->format('ymd').'-'.strtoupper(Str::random(6));}while(Invoice::where('invoice_number',$n)->exists());return $n;}
    private function label(string $r):string{return match($r){'tax'=>'Tax / Pajak','shipping_actual'=>'Shipping Aktual','weight'=>'Penyesuaian Berat','rate'=>'Penyesuaian Rate','weight_rate'=>'Berat + Rate',default=>'Kekurangan Lainnya'};}
}
