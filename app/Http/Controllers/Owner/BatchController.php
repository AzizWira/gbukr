<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\{Batch, Country, GoGroup, Invoice, Order, OrderItem, Shipment, User, Warehouse};
use App\Notifications\InvoiceCreatedNotification;
use App\Services\{BatchTrackingService, OrderCleanupService, OrderStatusService};
use App\Support\Search;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BatchController extends Controller
{
    public function index(Request $request)
    {
        $query = Search::term($request->query('q'));
        $status = $request->query('status');
        if ($status && !in_array($status, OrderStatusService::statuses(), true)) {
            $status = null;
        }

        $batches = Batch::with(['country', 'goGroup', 'warehouse'])->withCount('orders')
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($sub) use ($query) {
                    Search::code($sub, 'code', $query)
                        ->orWhere('name', 'like', '%' . $query . '%')
                        ->orWhere('tracking_number', 'like', '%' . $query . '%')
                        ->orWhereHas('goGroup', fn ($go) => $go->where('name', 'like', '%' . $query . '%'))
                        ->orWhereHas('country', fn ($country) => $country->where('name', 'like', '%' . $query . '%')->orWhere('code', 'like', '%' . $query . '%'))
                        ->orWhereHas('warehouse', fn ($warehouse) => $warehouse->where('code', 'like', '%' . $query . '%')->orWhere('name', 'like', '%' . $query . '%'));
                });
            })
            ->when($status, fn ($builder) => $builder->where('status', $status))
            ->latest()
            ->paginate(\App\Support\Listing::perPage($request, 20))
            ->withQueryString();

        return view('owner.batches.index', compact('batches'));
    }

    public function create()
    {
        return view('owner.batches.form', [
            'countries' => Country::where('active', true)->orderBy('name')->get(),
            'groups' => GoGroup::where('status', 'active')->orderBy('name')->get(),
            'warehouses' => Warehouse::with('country')->where('active', true)->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request, BatchTrackingService $tracking)
    {
        $data = $this->validateBatch($request);
        $country = Country::where('active', true)->findOrFail($data['country_id']);
        $group = !empty($data['go_group_id'])
            ? GoGroup::where('status', 'active')->findOrFail($data['go_group_id'])
            : null;

        $batch = DB::transaction(function () use ($data, $country, $group, $tracking) {
            $batch = Batch::create([
                'go_group_id' => $group?->id,
                'country_id' => $country->id,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'code' => Batch::generateCode($country->code, $group?->name ?: $data['name']),
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'tracking_number' => $data['tracking_number'] ?? null,
                'status' => 'ordered',
            ]);

            $tracking->sync($batch);

            return $batch;
        });

        return redirect()
            ->route('owner.batches.show', $batch)
            ->with('success', 'Batch ' . $batch->code . ' berhasil dibuat.');
    }

    public function edit(Batch $batch)
    {
        return view('owner.batches.form', [
            'batch' => $batch,
            'countries' => Country::where('active', true)->orderBy('name')->get(),
            'groups' => GoGroup::where('status', 'active')->orderBy('name')->get(),
            'warehouses' => Warehouse::with('country')->where('active', true)->orderBy('code')->get(),
        ]);
    }

    public function update(Request $request, Batch $batch, BatchTrackingService $tracking)
    {
        $data = $this->validateBatch($request);

        if ($batch->orders()->exists()) {
            if ((int) $data['country_id'] !== (int) $batch->country_id) {
                throw ValidationException::withMessages(['country_id' => 'Negara Batch yang sudah memiliki order tidak dapat diubah.']);
            }
            if ((int) ($data['go_group_id'] ?? 0) !== (int) ($batch->go_group_id ?? 0)) {
                throw ValidationException::withMessages(['go_group_id' => 'GO Batch yang sudah memiliki order tidak dapat diubah.']);
            }
        }

        DB::transaction(function () use ($batch, $data, $tracking) {
            $batch->update([
                'go_group_id' => $data['go_group_id'] ?? null,
                'country_id' => $data['country_id'],
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'tracking_number' => $data['tracking_number'] ?? null,
            ]);
            $tracking->sync($batch->fresh(['country', 'orders.items']));
        });

        return redirect()->route('owner.batches.show', $batch)->with('success', 'Data Batch berhasil diperbarui.');
    }

    public function destroy(Request $request, Batch $batch, OrderCleanupService $cleanup)
    {
        $orders=$batch->orders()->with(['customer.customerProfile','invoices.payments'])->get();
        $needsApproval=$orders->filter(fn($order)=>$cleanup->policy($order)['action']==='approval_required');
        if($needsApproval->isNotEmpty()){
            foreach($needsApproval as $order){
                $cleanup->processDeletion($order,$request->user(),'Penghapusan Batch '.$batch->code,'batch_cleanup');
            }
            return back()->with('success',$needsApproval->count().' order memerlukan persetujuan customer. Permintaan sudah dikirim; Batch belum dihapus agar relasi tetap utuh sampai keputusan customer selesai.');
        }

        $deleted=0;
        DB::transaction(function() use($request,$batch,$orders,$cleanup,&$deleted){
            foreach($orders as $order){
                $result=$cleanup->processDeletion($order,$request->user(),'Penghapusan Batch '.$batch->code,'batch_cleanup');
                if($result['status']==='deleted') $deleted++;
            }
            Shipment::where('source_type','batch')->where('source_id',$batch->id)->delete();
            $batch->delete();
        });
        return redirect()->route('owner.batches.index')->with('success','Batch berhasil dihapus. '.$deleted.' order terkait ikut dibersihkan sesuai aturan akun dan pembayaran.');
    }

    public function show(Batch $batch, BatchTrackingService $tracking, OrderCleanupService $cleanup)
    {
        if (!\App\Models\Shipment::where('source_type', 'batch')->where('source_id', $batch->id)->exists()) {
            $tracking->sync($batch->loadMissing(['country', 'orders.items']));
        }

        $batch->load([
            'country',
            'goGroup',
            'warehouse',
            'shipment',
            'orders.customer.customerProfile',
            'orders.items',
            'orders.invoices.payments',
            'orders.adjustments.invoice.customer',
        ]);

        $customers = User::where('role', 'customer')
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $deleteBlockers = $batch->orders->mapWithKeys(fn ($order) => [$order->id => $cleanup->blocker($order)]);

        return view('owner.batches.show', compact('batch', 'customers', 'deleteBlockers'));
    }

    public function addOrder(Request $request, Batch $batch, BatchTrackingService $tracking)
    {
        $data = $request->validate([
            'customer_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'customer')->where('active', true)),
            ],
            'customer_name' => ['nullable', 'required_without:customer_id', 'string', 'max:120'],
            'customer_username' => ['nullable', 'string', 'max:100'],
            'customer_whatsapp' => ['nullable', 'string', 'max:30'],
            'customer_line' => ['nullable', 'string', 'max:100'],
            'item_name' => ['required', 'string', 'max:180'],
            'details' => ['nullable', 'string', 'max:180'],
            'description_type' => ['required', 'string', 'max:100'],
            'qty' => ['required', 'integer', 'min:1', 'max:999'],
            'invoice_type' => ['nullable', 'required_with:invoice_amount', 'in:full,dp,cicilan,pelunasan,kenaikan,penyesuaian'],
            'invoice_amount' => ['nullable', 'integer', 'min:1'],
            'deadline_at' => ['nullable', 'date'],
        ]);

        if (empty($data['customer_id'])) {
            $hasContact = filled($data['customer_username'] ?? null)
                || filled($data['customer_whatsapp'] ?? null)
                || filled($data['customer_line'] ?? null);

            if (!$hasContact) {
                throw ValidationException::withMessages([
                    'customer_name' => 'Customer baru perlu minimal satu identitas kontak: username, WhatsApp, atau LINE.',
                ]);
            }
        }

        $invoice = null;

        DB::transaction(function () use ($data, $batch, $tracking, &$invoice) {
            $customerId = $data['customer_id'] ?? null;

            if (!$customerId) {
                $identity = implode('|', [
                    mb_strtolower(trim($data['customer_name'])),
                    mb_strtolower(trim((string) ($data['customer_username'] ?? ''))),
                    preg_replace('/\s+/', '', (string) ($data['customer_whatsapp'] ?? '')),
                    mb_strtolower(trim((string) ($data['customer_line'] ?? ''))),
                ]);

                $email = 'legacy+' . substr(sha1($identity), 0, 24) . '@placeholder.local';
                $user = User::where('email', $email)->first();

                if (!$user) {
                    $user = User::create([
                        'name' => trim($data['customer_name']),
                        'email' => $email,
                        'password' => null,
                        'role' => 'customer',
                        'active' => true,
                    ]);

                    $user->customerProfile()->create([
                        'legacy_name' => trim($data['customer_name']),
                        'username' => $data['customer_username'] ?? null,
                        'whatsapp' => $data['customer_whatsapp'] ?? null,
                        'line_id' => $data['customer_line'] ?? null,
                        'source_channel' => filled($data['customer_line'] ?? null)
                            ? 'line'
                            : (filled($data['customer_whatsapp'] ?? null) ? 'whatsapp' : 'other'),
                    ]);
                }

                $customerId = $user->id;
            }

            $order = Order::create([
                'customer_id' => $customerId,
                'go_group_id' => $batch->go_group_id,
                'batch_id' => $batch->id,
                'source_type' => 'batch',
                'order_number' => $this->generateUniqueNumber('ORD-B'),
                'status' => $batch->status,
                'currency_code' => $batch->country->currency_code,
                'notes' => 'Input manual Batch ' . $batch->code,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'item_name' => trim($data['item_name']),
                'details' => $data['details'] ?? null,
                'description_type' => trim($data['description_type']),
                'qty' => $data['qty'],
            ]);

            if (!empty($data['invoice_amount'])) {
                $invoice = Invoice::create([
                    'customer_id' => $customerId,
                    'order_id' => $order->id,
                    'invoice_number' => $this->generateUniqueNumber('INV'),
                    'type' => $data['invoice_type'] ?? 'pelunasan',
                    'amount' => $data['invoice_amount'],
                    'deadline_at' => $data['deadline_at'] ?? null,
                    'status' => 'unpaid',
                ]);
            }

            $tracking->sync($batch->fresh(['country', 'orders.items']));
        });

        if ($invoice) {
            $invoice->load('customer');
            $invoice->customer?->notify(new InvoiceCreatedNotification($invoice));
        }

        return back()->with('success', 'Order customer berhasil ditambahkan ke Batch.');
    }

    public function destroyOrders(Request $request, Batch $batch, OrderCleanupService $cleanup, BatchTrackingService $tracking)
    {
        $data=$request->validate([
            'order_ids'=>['required','array','min:1','max:200'],
            'order_ids.*'=>['integer'],
        ],['order_ids.required'=>'Pilih minimal satu order yang akan diproses.']);
        $orders=$batch->orders()->with(['customer.customerProfile','invoices.payments'])->whereIn('id',$data['order_ids'])->get();
        if($orders->count()!==count(array_unique($data['order_ids']))) throw ValidationException::withMessages(['order_ids'=>'Ada order yang tidak berasal dari Batch ini.']);
        $deleted=0; $pending=0;
        foreach($orders as $order){
            $result=$cleanup->processDeletion($order,$request->user(),'Cleanup order dari Batch '.$batch->code,'batch_cleanup');
            $result['status']==='pending_approval' ? $pending++ : $deleted++;
        }
        $tracking->sync($batch->fresh(['country','orders.items']));
        $message=$deleted.' order berhasil dibersihkan.';
        if($pending>0) $message.=' '.$pending.' order tetap berada di Batch sambil menunggu persetujuan customer.';
        return back()->with('success',$message);
    }

    public function updateStatus(
        Request $request,
        Batch $batch,
        OrderStatusService $statusService,
        BatchTrackingService $tracking
    ) {
        $data = $request->validate([
            'status' => ['required', Rule::in(OrderStatusService::statuses())],
            'tracking_number' => ['nullable', 'string', 'max:180'],
        ]);

        DB::transaction(function () use ($data, $batch, $request, $statusService, $tracking) {
            $batch->update([
                'status' => $data['status'],
                'tracking_number' => $data['tracking_number'] ?? $batch->tracking_number,
                'arrived_gbu_at' => $data['status'] === 'arrived_gbu' && !$batch->arrived_gbu_at
                    ? now()
                    : $batch->arrived_gbu_at,
            ]);

            $batch->load('orders.customer');
            foreach ($batch->orders as $order) {
                $statusService->update(
                    $order,
                    $data['status'],
                    $request->user()->id,
                    'Update melalui Batch ' . $batch->code
                );
            }

            $tracking->sync($batch->fresh(['country', 'orders.items']));
        });

        return back()->with('success', 'Status Batch, order, dan tracking diperbarui.');
    }

    private function validateBatch(Request $request): array
    {
        $countryId = $request->input('country_id');

        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'country_id' => [
                'required',
                Rule::exists('countries', 'id')->where(fn ($q) => $q->where('active', true)),
            ],
            'go_group_id' => [
                'nullable',
                Rule::exists('go_groups', 'id')->where(fn ($q) => $q->where('status', 'active')),
            ],
            'warehouse_id' => [
                'nullable',
                Rule::exists('warehouses', 'id')->where(function ($q) use ($countryId) {
                    $q->where('active', true);
                    if ($countryId) {
                        $q->where('country_id', $countryId);
                    }
                }),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'tracking_number' => ['nullable', 'string', 'max:180'],
        ], [
            'warehouse_id.exists' => 'Warehouse harus aktif dan berasal dari negara yang dipilih.',
        ]);
    }

    private function generateUniqueNumber(string $prefix): string
    {
        do {
            $number = $prefix . '-' . now()->format('ymd') . '-' . strtoupper(Str::random(7));
        } while (
            ($prefix === 'INV' && Invoice::where('invoice_number', $number)->exists())
            || ($prefix !== 'INV' && Order::where('order_number', $number)->exists())
        );

        return $number;
    }
}
