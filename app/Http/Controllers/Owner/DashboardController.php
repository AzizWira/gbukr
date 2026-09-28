<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\{Batch, Invoice, Order, Payment, Preorder, User};

class DashboardController extends Controller
{
    public function __invoke()
    {
        $todayEnd = now()->endOfDay();
        $todayStart = now()->startOfDay();

        $stats = [
            'customers' => User::where('role', 'customer')->count(),
            'orders' => Order::count(),
            'unpaid' => Invoice::whereIn('status', ['unpaid', 'partial', 'overdue'])->count(),
            'pendingPayments' => Payment::where('status', 'pending')->count(),
            'activeBatches' => Batch::whereNotIn('status', ['completed'])->count(),
            'openPo' => Preorder::where('status', 'open')->where(fn ($q) => $q->whereNull('close_at')->orWhere('close_at', '>', now()))->count(),
            'unclaimed' => Order::where('status', 'unclaimed')->count(),
            'overdue' => Invoice::where('status', 'overdue')->count(),
            'missingTracking' => Batch::whereNull('tracking_number')->whereNotIn('status', ['completed'])->count(),
            'arrivedIndo' => Batch::where('status', 'arrived_indo')->count(),
            'poClosingToday' => Preorder::where('status', 'open')->whereBetween('close_at', [$todayStart, $todayEnd])->count(),
        ];

        return view('owner.dashboard', [
            'stats' => $stats,
            'payments' => Payment::with('customer')->where('status', 'pending')->latest()->take(8)->get(),
            'orders' => Order::with('customer')->latest()->take(8)->get(),
        ]);
    }
}
