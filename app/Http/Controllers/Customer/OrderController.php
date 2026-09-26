<?php
namespace App\Http\Controllers\Customer;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
class OrderController extends Controller
{
 public function index(Request $r){$orders=$r->user()->orders()->with(['items','batch','preorder.product','goGroup'])->latest()->paginate(15);return view('customer.orders.index',compact('orders'));}
 public function show(Request $r,Order $order){abort_unless($order->customer_id===$r->user()->id,403);$order->load(['items','batch.country','preorder.product','goGroup','invoices.payments']);foreach($order->invoices as $i)$i->recalculatePenalty();return view('customer.orders.show',compact('order'));}
}
