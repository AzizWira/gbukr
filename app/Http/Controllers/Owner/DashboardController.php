<?php
namespace App\Http\Controllers\Owner;
use App\Http\Controllers\Controller;
use App\Models\{Batch,Invoice,Order,Payment,Preorder,User};
class DashboardController extends Controller
{
 public function __invoke(){return view('owner.dashboard',['stats'=>['customers'=>User::where('role','customer')->count(),'orders'=>Order::count(),'unpaid'=>Invoice::whereIn('status',['unpaid','partial','overdue'])->count(),'pendingPayments'=>Payment::where('status','pending')->count(),'activeBatches'=>Batch::whereNotIn('status',['completed'])->count(),'openPo'=>Preorder::where('status','open')->where(fn($q)=>$q->whereNull('close_at')->orWhere('close_at','>',now()))->count(),'unclaimed'=>Order::where('status','unclaimed')->count()],'payments'=>Payment::with('customer')->where('status','pending')->latest()->take(8)->get(),'orders'=>Order::with('customer')->latest()->take(8)->get()]);}
}
