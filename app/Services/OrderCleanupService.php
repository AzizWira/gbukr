<?php

namespace App\Services;

use App\Models\{ImportRun, Order, OrderDeletionRequest, Payment, StatusHistory, User};
use App\Notifications\{OrderDeletedNotification, OrderDeletionApprovalRequestedNotification};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderCleanupService
{
    public function __construct(private OrderImportSnapshotService $snapshots) {}

    public function policy(Order $order): array
    {
        $order->loadMissing(['customer.customerProfile','invoices.payments']);
        $linked = $order->customer && !str_ends_with((string)$order->customer->email,'@placeholder.local');
        $payments = $order->invoices->flatMap->payments;
        $hasPaidAmount = $order->invoices->contains(fn($i)=>(int)$i->paid_amount>0);
        $approved = $hasPaidAmount || $payments->contains(fn($p)=>$p->status==='approved');
        $pendingRejected = !$approved && $payments->contains(fn($p)=>in_array($p->status,['pending','rejected'],true));
        $paymentState = $approved ? 'approved' : ($pendingRejected ? 'pending_rejected' : 'none');
        $action = !$linked ? 'direct_delete' : ($paymentState==='none' ? 'delete_notify' : 'approval_required');
        return [
            'linked'=>$linked,
            'account_label'=>$linked ? 'Sudah terhubung akun' : 'Belum terhubung akun',
            'payment_state'=>$paymentState,
            'payment_label'=>match($paymentState){
                'approved'=>'Ada pembayaran approved / riwayat pembayaran',
                'pending_rejected'=>'Ada pembayaran pending/rejected',
                default=>'Belum ada pembayaran',
            },
            'action'=>$action,
            'action_label'=>match($action){
                'approval_required'=>'Kirim permintaan persetujuan customer',
                'delete_notify'=>'Hapus + kirim notifikasi customer',
                default=>'Hapus langsung',
            },
        ];
    }

    public function blocker(Order $order): ?string
    {
        $p=$this->policy($order);
        return $p['action']==='approval_required' ? 'Order terhubung akun dan mempunyai histori pembayaran. Penghapusan memerlukan persetujuan customer.' : null;
    }

    public function canDelete(Order $order): bool { return $this->policy($order)['action'] !== 'approval_required'; }

    public function importReview(Order $order): array
    {
        $changes=$this->snapshots->changes($order);
        $activity=$this->snapshots->hasNewActivity($order);
        $policy=$this->policy($order);
        $autoSafe=!$policy['linked'] && $policy['payment_state']==='none' && !$changes && !$activity;
        return ['auto_safe'=>$autoSafe,'changes'=>$changes,'new_activity'=>$activity,'policy'=>$policy];
    }

    public function processDeletion(Order $order, User $owner, ?string $reason=null, string $source='owner', ?ImportRun $run=null): array
    {
        $policy=$this->policy($order);
        $reason=trim((string)$reason) ?: 'Penghapusan order oleh Owner.';
        $snapshot=$this->auditSnapshot($order,$policy);

        if ($policy['action']==='approval_required') {
            $existing=OrderDeletionRequest::where('order_id',$order->id)->where('status','pending')->first();
            if ($existing) return ['status'=>'pending_approval','request'=>$existing,'policy'=>$policy];
            $request=OrderDeletionRequest::create([
                'order_id'=>$order->id,'owner_id'=>$owner->id,'customer_id'=>$order->customer_id,
                'import_run_id'=>$run?->id ?: $order->import_run_id,'source'=>$source,'status'=>'pending',
                'requires_customer_approval'=>true,'reason'=>$reason,'snapshot'=>$snapshot,'requested_at'=>now(),
            ]);
            $order->customer?->notify(new OrderDeletionApprovalRequestedNotification($request));
            return ['status'=>'pending_approval','request'=>$request,'policy'=>$policy];
        }

        $request=OrderDeletionRequest::create([
            'order_id'=>$order->id,'owner_id'=>$owner->id,'customer_id'=>$order->customer_id,
            'import_run_id'=>$run?->id ?: $order->import_run_id,'source'=>$source,'status'=>'executed',
            'requires_customer_approval'=>false,'reason'=>$reason,'snapshot'=>$snapshot,
            'requested_at'=>now(),'responded_at'=>now(),'completed_at'=>now(),
        ]);
        $this->performDeletion($order);
        if ($policy['linked']) $order->customer?->notify(new OrderDeletedNotification($snapshot,$reason));
        return ['status'=>'deleted','request'=>$request,'policy'=>$policy];
    }

    public function approve(OrderDeletionRequest $request, User $customer): void
    {
        if ($request->customer_id !== $customer->id || $request->status!=='pending') {
            throw ValidationException::withMessages(['request'=>'Permintaan penghapusan ini sudah tidak dapat diproses.']);
        }
        $order=Order::withTrashed()->find($request->order_id);
        if (!$order || $order->trashed()) {
            $request->update(['status'=>'approved','responded_at'=>now(),'completed_at'=>now()]);
            return;
        }
        DB::transaction(function() use($request,$order,$customer){
            $request->update(['status'=>'approved','responded_at'=>now()]);
            $this->performDeletion($order);
            $request->update(['completed_at'=>now()]);
        });
        $customer->notify(new OrderDeletedNotification((array)$request->snapshot,(string)$request->reason));
        $this->finishImportIfEmpty($request);
    }

    public function reject(OrderDeletionRequest $request, User $customer): void
    {
        if ($request->customer_id !== $customer->id || $request->status!=='pending') {
            throw ValidationException::withMessages(['request'=>'Permintaan penghapusan ini sudah tidak dapat diproses.']);
        }
        $request->update(['status'=>'rejected','responded_at'=>now()]);
    }

    public function delete(Order $order): void
    {
        if ($this->policy($order)['action']==='approval_required') {
            throw ValidationException::withMessages(['order'=>'Order memerlukan persetujuan customer sebelum dihapus.']);
        }
        $this->performDeletion($order);
    }

    public function detachFromBatch(Order $order): void
    {
        if (!$order->batch_id) throw ValidationException::withMessages(['order'=>'Order ini sudah tidak terhubung ke Batch.']);
        $order->update(['batch_id'=>null]);
    }

    private function performDeletion(Order $order): void
    {
        DB::transaction(function() use($order){
            $order->loadMissing(['adjustments','invoices.payments','items.variant']);
            $hasFinancial=$order->invoices->contains(fn($i)=>(int)$i->paid_amount>0 || $i->payments->isNotEmpty());
            if ($order->source_type==='ready') {
                foreach($order->items as $item) if($item->variant && $item->variant->stock!==null) $item->variant->increment('stock',(int)$item->qty);
            }
            if (!$hasFinancial) {
                $order->adjustments()->delete();
                foreach($order->invoices as $invoice) $invoice->forceDelete();
                StatusHistory::where('entity_type','order')->where('entity_id',$order->id)->delete();
                $order->forceDelete();
                return;
            }
            $payments=$order->invoices->flatMap->payments->unique('id');
            foreach($order->invoices as $invoice) $invoice->delete();
            foreach($payments as $payment) {
                if (!$payment->invoices()->exists()) $payment->delete();
            }
            $order->delete();
        });
    }

    private function auditSnapshot(Order $order, array $policy): array
    {
        $order->loadMissing(['customer.customerProfile','items','invoices.payments','batch','goGroup']);
        return [
            'order_id'=>$order->id,'order_number'=>$order->order_number,'customer_name'=>$order->customer?->name,
            'customer_email'=>$order->customer?->email,'account_status'=>$policy['account_label'],
            'payment_status'=>$policy['payment_label'],'source_type'=>$order->source_type,
            'batch'=>$order->batch?->code,'go'=>$order->goGroup?->name,
            'items'=>$order->items->map(fn($i)=>['name'=>$i->item_name,'qty'=>(int)$i->qty,'details'=>$i->details])->values()->all(),
            'invoices'=>$order->invoices->map(fn($i)=>[
                'number'=>$i->invoice_number,'amount'=>(int)$i->amount,'paid_amount'=>(int)$i->paid_amount,'status'=>$i->status,
                'payments'=>$i->payments->map(fn($p)=>['number'=>$p->payment_number,'amount'=>(int)$p->amount,'status'=>$p->status])->values()->all(),
            ])->values()->all(),
        ];
    }

    private function finishImportIfEmpty(OrderDeletionRequest $request): void
    {
        if (!$request->import_run_id) return;
        $run=ImportRun::find($request->import_run_id);
        if ($run && !Order::where('import_run_id',$run->id)->exists()) {
            $summary=(array)$run->summary; $summary['cleanup_completed_after_customer_approval']=true;
            $run->update(['status'=>'rolled_back','summary'=>$summary]);
        }
    }
}
