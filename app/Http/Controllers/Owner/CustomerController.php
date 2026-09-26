<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Search;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->paginate(25)
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

        $usage = [
            'order' => $customer->orders()->count(),
            'tagihan' => $customer->invoices()->count(),
            'pembayaran' => $customer->payments()->count(),
        ];
        $used = array_filter($usage, fn ($count) => (int) $count > 0);

        if ($customer->admin_enabled) {
            throw ValidationException::withMessages([
                'delete' => 'Customer ini masih memiliki akses Admin. Cabut akses Admin terlebih dahulu sebelum menghapus akun.',
            ]);
        }

        if ($used) {
            $details = collect($used)->map(fn ($count, $label) => $count . ' ' . $label)->implode(', ');
            throw ValidationException::withMessages([
                'delete' => 'Customer tidak dapat dihapus permanen karena masih memiliki ' . $details . '. Nonaktifkan akun agar histori tetap utuh.',
            ]);
        }

        DB::transaction(function () use ($customer) {
            DB::table('notifications')->where('notifiable_type', User::class)->where('notifiable_id', $customer->id)->delete();
            DB::table('sessions')->where('user_id', $customer->id)->delete();
            DB::table('password_reset_tokens')->where('email', $customer->email)->delete();
            $customer->delete();
        });

        return redirect()->route('owner.customers.index')->with('success', 'Customer yang belum pernah bertransaksi berhasil dihapus permanen.');
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
            $customer->orders()->update(['customer_id' => $target->id]);
            $customer->invoices()->update(['customer_id' => $target->id]);
            $customer->payments()->update(['customer_id' => $target->id]);
            DB::table('notifications')->where('notifiable_type', User::class)->where('notifiable_id', $customer->id)->update(['notifiable_id' => $target->id]);

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
        $customerUsageCount = $customer->orders->count() + $customer->invoices->count() + $customer->payments->count();
        return view('owner.customers.show', compact('customer', 'customerUsageCount'));
    }
}
