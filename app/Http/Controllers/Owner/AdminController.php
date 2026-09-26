<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function index()
    {
        return view('owner.admins.index', [
            'admins' => User::where('role', 'customer')
                ->where('admin_enabled', true)
                ->orderBy('name')
                ->get(),
            'members' => User::where('role', 'customer')
                ->where('active', true)
                ->where('admin_enabled', false)
                ->whereNotNull('email_verified_at')
                ->where('email', 'not like', '%@placeholder.local')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q
                    ->where('role', 'customer')
                    ->where('active', true)
                    ->where('admin_enabled', false)
                    ->whereNotNull('email_verified_at')),
            ],
        ]);

        $user = User::findOrFail($data['user_id']);
        abort_if($user->isOwner(), 422, 'Owner tidak perlu ditambahkan sebagai Admin.');

        $user->update(['admin_enabled' => true]);
        $user->customerProfile()->firstOrCreate([]);

        return back()->with('success', $user->name . ' sekarang memiliki akses Admin.');
    }

    public function toggle(User $admin)
    {
        abort_unless($admin->role === 'customer' && !$admin->isOwner(), 404);

        $admin->update(['admin_enabled' => !$admin->admin_enabled]);

        return back()->with(
            'success',
            $admin->admin_enabled
                ? 'Akses Admin ' . $admin->name . ' diaktifkan.'
                : 'Akses Admin ' . $admin->name . ' dicabut. Akun customer tetap aktif.'
        );
    }
}
