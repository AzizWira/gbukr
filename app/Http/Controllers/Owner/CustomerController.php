<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

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

    public function merge(Request $request, User $customer)
    {
        abort_unless($customer->role === 'customer', 404);

        $request->merge(['target_email' => strtolower(trim((string) $request->input('target_email')))]);

        $data = $request->validate([
            'target_email' => [
                'required',
                'email',
                Rule::exists('users', 'email')->where(fn ($q) => $q->where('role', 'customer')->where('active', true)),
            ],
        ]);

        $target = User::where('role', 'customer')
            ->where('active', true)
            ->where('email', $data['target_email'])
            ->firstOrFail();

        abort_if($target->id === $customer->id, 422, 'Target customer harus berbeda.');

        DB::transaction(function () use ($customer, $target) {
            $customer->orders()->update(['customer_id' => $target->id]);
            $customer->invoices()->update(['customer_id' => $target->id]);
            $customer->payments()->update(['customer_id' => $target->id]);

            DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $customer->id)
                ->update(['notifiable_id' => $target->id]);

            if (!$target->customerProfile && $customer->customerProfile) {
                $customer->customerProfile->update(['user_id' => $target->id]);
            } elseif ($customer->customerProfile) {
                if ($target->customerProfile && !$target->customerProfile->legacy_name) {
                    $target->customerProfile->update([
                        'legacy_name' => $customer->customerProfile->legacy_name,
                    ]);
                }
                $customer->customerProfile->delete();
            }

            $customer->delete();
        });

        return redirect()
            ->route('owner.customers.show', $target)
            ->with('success', 'Data customer lama berhasil digabungkan.');
    }

    public function show(User $customer)
    {
        abort_unless($customer->role === 'customer', 404);
        $customer->load(['customerProfile', 'orders.items', 'invoices']);

        return view('owner.customers.show', compact('customer'));
    }
}
