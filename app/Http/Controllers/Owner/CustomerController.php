<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\{Invoice, Order, OrderDeletionRequest, Payment, User};
use App\Support\Search;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Search::term($request->query('q'));

        $customers = User::where('role', 'customer')
            ->with('customerProfile')
            ->withCount(['orders', 'invoices'])
            ->when($query !== '', fn ($q) => $q->where(function ($user) use ($query) {
                $user->where('name', 'like', '%' . $query . '%')
                    ->orWhere('email', 'like', '%' . $query . '%')
                    ->orWhereHas('customerProfile', fn ($profile) => $profile
                        ->where('legacy_name', 'like', '%' . $query . '%')
                        ->orWhere('username', 'like', '%' . $query . '%')
                        ->orWhere('whatsapp', 'like', '%' . $query . '%')
                        ->orWhere('line_id', 'like', '%' . $query . '%'));
            }))
            ->latest()
            ->paginate(\App\Support\Listing::perPage($request, 20))
            ->withQueryString();

        return view('owner.customers.index', compact('customers'));
    }

    public function update(Request $request, User $customer)
    {
        abort_unless($customer->role === 'customer', 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['nullable', 'string', 'max:100'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'line_id' => ['nullable', 'string', 'max:100'],
            'source_channel' => ['nullable', 'in:whatsapp,line,other'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $customer->update(['name' => trim($data['name'])]);
        $customer->customerProfile()->updateOrCreate([], [
            'username' => $data['username'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'line_id' => $data['line_id'] ?? null,
            'source_channel' => $data['source_channel'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Data customer diperbarui.');
    }

    public function toggle(User $customer)
    {
        abort_unless($customer->role === 'customer', 404);
        $customer->update(['active' => !$customer->active]);

        return back()->with('success', $customer->active ? 'Customer diaktifkan.' : 'Customer dinonaktifkan.');
    }

    public function destroy(User $customer)
    {
        abort_unless($customer->role === 'customer', 404);

        $state = $this->deletionState($customer);

        if ($customer->admin_enabled) {
            throw ValidationException::withMessages([
                'delete' => 'Customer ini masih memiliki akses Admin. Cabut akses Admin terlebih dahulu sebelum menghapus akun.',
            ]);
        }

        if ($state['has_active_transactions']) {
            throw ValidationException::withMessages([
                'delete' => 'Customer belum dapat dihapus karena masih mempunyai Order, Tagihan, atau Pembayaran aktif. Selesaikan atau hapus data aktif tersebut terlebih dahulu.',
            ]);
        }

        if ($state['has_protected_financial_history']) {
            throw ValidationException::withMessages([
                'delete' => 'Customer belum dapat dihapus karena masih mempunyai histori finansial yang belum pernah disetujui untuk dihapus. Nonaktifkan akun atau tinjau transaksi terkait terlebih dahulu.',
            ]);
        }

        $proofPaths = [];

        try {
            DB::transaction(function () use ($customer, $state, &$proofPaths) {
                if ($state['archived_order_count'] > 0
                    || $state['archived_invoice_count'] > 0
                    || $state['archived_payment_count'] > 0) {
                    $proofPaths = $this->purgeArchivedCustomerData($customer);
                }

                DB::table('notifications')
                    ->where('notifiable_type', User::class)
                    ->where('notifiable_id', $customer->id)
                    ->delete();
                DB::table('sessions')->where('user_id', $customer->id)->delete();
                DB::table('password_reset_tokens')->where('email', $customer->email)->delete();

                $customer->delete();
            });
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() !== '23000'
                && !str_contains(strtolower($exception->getMessage()), 'foreign key constraint')) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'delete' => 'Customer belum dapat dihapus permanen karena masih terhubung dengan data sistem yang dilindungi. Muat ulang halaman dan periksa transaksi yang masih aktif.',
            ]);
        }

        if ($proofPaths) {
            try {
                Storage::delete(array_values(array_unique($proofPaths)));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        $hadArchivedResidue = $state['archived_order_count'] > 0
            || $state['archived_invoice_count'] > 0
            || $state['archived_payment_count'] > 0;

        $message = $hadArchivedResidue
            ? 'Customer berhasil dihapus permanen. Data operasional yang sebelumnya sudah dihapus/di-approve ikut dibersihkan; jejak audit penghapusan tetap tersimpan.'
            : 'Customer yang belum pernah bertransaksi berhasil dihapus permanen.';

        return redirect()->route('owner.customers.index')->with('success', $message);
    }

    public function merge(Request $request, User $customer)
    {
        abort_unless($customer->role === 'customer', 404);
        $request->merge(['target_email' => strtolower(trim((string) $request->input('target_email')))]);
        $data = $request->validate([
            'target_email' => ['required', 'email', Rule::exists('users', 'email')->where(fn ($q) => $q->where('role', 'customer')->where('active', true))],
        ]);

        $target = User::where('role', 'customer')->where('active', true)->where('email', $data['target_email'])->firstOrFail();
        abort_if($target->id === $customer->id, 422, 'Target customer harus berbeda.');

        DB::transaction(function () use ($customer, $target) {
            // Termasuk data yang sudah soft-delete agar tidak meninggalkan foreign key tersembunyi.
            Order::withTrashed()->where('customer_id', $customer->id)->update(['customer_id' => $target->id]);
            Invoice::withTrashed()->where('customer_id', $customer->id)->update(['customer_id' => $target->id]);
            Payment::withTrashed()->where('customer_id', $customer->id)->update(['customer_id' => $target->id]);
            OrderDeletionRequest::where('customer_id', $customer->id)->update(['customer_id' => $target->id]);

            DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $customer->id)
                ->update(['notifiable_id' => $target->id]);

            if (!$target->customerProfile && $customer->customerProfile) {
                $customer->customerProfile->update(['user_id' => $target->id]);
            } elseif ($customer->customerProfile) {
                if ($target->customerProfile && !$target->customerProfile->legacy_name) {
                    $target->customerProfile->update(['legacy_name' => $customer->customerProfile->legacy_name]);
                }
                $customer->customerProfile->delete();
            }

            $customer->delete();
        });

        return redirect()->route('owner.customers.show', $target)->with('success', 'Data customer lama berhasil digabungkan.');
    }

    public function show(User $customer)
    {
        abort_unless($customer->role === 'customer', 404);
        $customer->load(['customerProfile', 'orders.items', 'invoices', 'payments']);
        $customerDeletionState = $this->deletionState($customer);
        $deletionUsage = [
            'order' => $customerDeletionState['active_order_count'] + $customerDeletionState['archived_order_count'],
            'tagihan' => $customerDeletionState['active_invoice_count'] + $customerDeletionState['archived_invoice_count'],
            'pembayaran' => $customerDeletionState['active_payment_count'] + $customerDeletionState['archived_payment_count'],
        ];
        $customerUsageCount = array_sum($deletionUsage);

        return view('owner.customers.show', compact('customer', 'customerUsageCount', 'deletionUsage', 'customerDeletionState'));
    }

    /**
     * Customer dapat hard-delete bila:
     * - tidak ada transaksi aktif; dan
     * - tidak ada histori finansial yang belum mendapat otorisasi penghapusan.
     *
     * Histori finansial dari Order yang sudah disetujui customer untuk dihapus
     * (atau data placeholder yang memang tidak mempunyai akun) dianggap aman
     * untuk dipurge. Audit tetap tersimpan di order_deletion_requests.snapshot.
     */
    private function deletionState(User $customer): array
    {
        $activeOrders = $customer->orders()->count();
        $activeInvoices = $customer->invoices()->count();
        $activePayments = $customer->payments()->count();
        $archivedOrders = $customer->orders()->onlyTrashed()->count();
        $archivedInvoices = $customer->invoices()->onlyTrashed()->count();
        $archivedPayments = $customer->payments()->onlyTrashed()->count();

        $authorizedOrderIds = $this->authorizedDeletedOrderIds($customer);
        $authorizedLookup = array_fill_keys($authorizedOrderIds, true);

        $financialInvoices = $customer->invoices()
            ->withTrashed()
            ->where(function ($query) {
                $query->where('paid_amount', '>', 0)
                    ->orWhereIn('status', ['paid', 'partial']);
            })
            ->get(['id', 'order_id', 'deleted_at']);

        $protectedFinancialInvoices = $financialInvoices->filter(function ($invoice) use ($authorizedLookup) {
            return !$invoice->order_id || !isset($authorizedLookup[(int) $invoice->order_id]);
        })->count();

        $archivedPaymentRows = $customer->payments()->onlyTrashed()->get(['id']);
        $protectedArchivedPayments = 0;
        $authorizedArchivedPayments = 0;

        if ($archivedPaymentRows->isNotEmpty()) {
            $links = DB::table('invoice_payment')
                ->join('invoices', 'invoices.id', '=', 'invoice_payment.invoice_id')
                ->whereIn('invoice_payment.payment_id', $archivedPaymentRows->pluck('id'))
                ->get([
                    'invoice_payment.payment_id',
                    'invoices.customer_id',
                    'invoices.order_id',
                    'invoices.deleted_at',
                ])
                ->groupBy('payment_id');

            foreach ($archivedPaymentRows as $payment) {
                $paymentLinks = $links->get($payment->id, collect());
                $authorized = $paymentLinks->isNotEmpty()
                    && $paymentLinks->every(function ($link) use ($customer, $authorizedLookup) {
                        return (int) $link->customer_id === (int) $customer->id
                            && $link->deleted_at !== null
                            && $link->order_id !== null
                            && isset($authorizedLookup[(int) $link->order_id]);
                    });

                if ($authorized) {
                    $authorizedArchivedPayments++;
                } else {
                    $protectedArchivedPayments++;
                }
            }
        }

        $hasActiveTransactions = $activeOrders > 0 || $activeInvoices > 0 || $activePayments > 0;
        $hasProtectedFinancialHistory = $protectedFinancialInvoices > 0 || $protectedArchivedPayments > 0;

        return [
            'active_order_count' => $activeOrders,
            'active_invoice_count' => $activeInvoices,
            'active_payment_count' => $activePayments,
            'archived_order_count' => $archivedOrders,
            'archived_invoice_count' => $archivedInvoices,
            'archived_payment_count' => $archivedPayments,
            'financial_invoice_count' => $financialInvoices->count(),
            'authorized_financial_invoice_count' => max(0, $financialInvoices->count() - $protectedFinancialInvoices),
            'authorized_payment_count' => $authorizedArchivedPayments,
            'protected_financial_invoice_count' => $protectedFinancialInvoices,
            'protected_payment_count' => $protectedArchivedPayments,
            'authorized_deleted_order_ids' => $authorizedOrderIds,
            'has_active_transactions' => $hasActiveTransactions,
            'has_protected_financial_history' => $hasProtectedFinancialHistory,
            // compatibility key untuk view/logic lama
            'has_financial_history' => $hasProtectedFinancialHistory,
            'can_hard_delete' => !$customer->admin_enabled && !$hasActiveTransactions && !$hasProtectedFinancialHistory,
        ];
    }

    private function authorizedDeletedOrderIds(User $customer): array
    {
        $statuses = str_ends_with((string) $customer->email, '@placeholder.local')
            ? ['approved', 'executed']
            : ['approved'];

        return OrderDeletionRequest::where('customer_id', $customer->id)
            ->whereIn('status', $statuses)
            ->whereNotNull('completed_at')
            ->get(['order_id', 'snapshot'])
            ->map(function (OrderDeletionRequest $request) {
                return (int) ($request->order_id ?: data_get($request->snapshot, 'order_id'));
            })
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Purge residu yang sudah tidak tampil. Method hanya dipanggil setelah
     * deletionState memastikan tidak ada transaksi aktif / financial history
     * yang masih dilindungi.
     *
     * @return array<int,string> path bukti pembayaran yang perlu dihapus dari storage
     */
    private function purgeArchivedCustomerData(User $customer): array
    {
        $orderIds = $customer->orders()->onlyTrashed()->pluck('id');
        $invoiceIds = $customer->invoices()->onlyTrashed()->pluck('id');
        $paymentIds = $customer->payments()->onlyTrashed()->pluck('id');
        $proofPaths = $paymentIds->isEmpty()
            ? collect()
            : DB::table('payment_proofs')->whereIn('payment_id', $paymentIds)->pluck('path');

        if ($invoiceIds->isNotEmpty()) {
            DB::table('invoice_payment')->whereIn('invoice_id', $invoiceIds)->delete();
            Invoice::onlyTrashed()->whereIn('id', $invoiceIds)->forceDelete();
        }

        if ($paymentIds->isNotEmpty()) {
            $stillLinked = DB::table('invoice_payment')
                ->whereIn('payment_id', $paymentIds)
                ->pluck('payment_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $purgeablePaymentIds = $paymentIds
                ->map(fn ($id) => (int) $id)
                ->reject(fn ($id) => in_array($id, $stillLinked, true))
                ->values();

            if ($purgeablePaymentIds->count() !== $paymentIds->count()) {
                throw ValidationException::withMessages([
                    'delete' => 'Customer belum dapat dihapus karena masih ada pembayaran yang terhubung ke tagihan lain.',
                ]);
            }

            Payment::onlyTrashed()->whereIn('id', $purgeablePaymentIds)->forceDelete();
        }

        if ($orderIds->isNotEmpty()) {
            DB::table('status_histories')
                ->where('entity_type', 'order')
                ->whereIn('entity_id', $orderIds)
                ->delete();
            DB::table('shipments')
                ->where('source_type', 'order')
                ->whereIn('source_id', $orderIds)
                ->delete();
            Order::onlyTrashed()->whereIn('id', $orderIds)->forceDelete();
        }

        return $proofPaths->filter()->values()->all();
    }
}
