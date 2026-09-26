<?php
namespace App\Http\Controllers\Customer;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class DashboardController extends Controller
{
 public function __invoke(Request $r){$u=$r->user();$orders=$u->orders()->with(['items','batch','preorder.product','goGroup'])->latest()->take(5)->get();$invoices=$u->invoices()->with('order')->latest()->take(6)->get();foreach($invoices as $i)$i->recalculatePenalty();$summary=['orders'=>$u->orders()->count(),'unpaid'=>$u->invoices()->whereIn('status',['unpaid','partial','overdue'])->count(),'pending'=>$u->payments()->where('status','pending')->count(),'outstanding'=>$u->invoices()->get()->sum(function($i){$i->recalculatePenalty();return $i->outstanding();})];return view('customer.dashboard',compact('orders','invoices','summary'));}
}
