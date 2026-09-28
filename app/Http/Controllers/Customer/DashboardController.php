<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\OrderDeletionRequest;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user=$request->user();
        $orders=$user->orders()->with(['items','batch','preorder.product','goGroup'])->latest()->take(5)->get();
        $invoices=$user->invoices()->with('order')->latest()->take(6)->get();
        foreach($invoices as $invoice) $invoice->recalculatePenalty();
        $deletionRequests=OrderDeletionRequest::where('customer_id',$user->id)->where('status','pending')->latest()->get();
        $summary=[
            'orders'=>$user->orders()->count(),
            'unpaid'=>$user->invoices()->whereIn('status',['unpaid','partial','overdue'])->count(),
            'pending'=>$user->payments()->where('status','pending')->count(),
            'outstanding'=>$user->invoices()->get()->sum(function($invoice){$invoice->recalculatePenalty();return $invoice->outstanding();}),
        ];
        return view('customer.dashboard',compact('orders','invoices','summary','deletionRequests'));
    }
}
