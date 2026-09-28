<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\OrderDeletionRequest;
use App\Services\OrderCleanupService;
use Illuminate\Http\Request;

class OrderDeletionRequestController extends Controller
{
    public function show(Request $request, OrderDeletionRequest $deletionRequest)
    {
        abort_unless($deletionRequest->customer_id === $request->user()->id, 403);
        return view('customer.deletion-requests.show', compact('deletionRequest'));
    }

    public function approve(Request $request, OrderDeletionRequest $deletionRequest, OrderCleanupService $cleanup)
    {
        abort_unless($deletionRequest->customer_id === $request->user()->id, 403);
        $cleanup->approve($deletionRequest,$request->user());
        return redirect()->route('customer.dashboard')->with('success','Penghapusan order disetujui. Order sudah dihapus dari data operasional.');
    }

    public function reject(Request $request, OrderDeletionRequest $deletionRequest, OrderCleanupService $cleanup)
    {
        abort_unless($deletionRequest->customer_id === $request->user()->id, 403);
        $cleanup->reject($deletionRequest,$request->user());
        return redirect()->route('customer.dashboard')->with('success','Permintaan penghapusan ditolak. Order tetap tersimpan.');
    }
}
