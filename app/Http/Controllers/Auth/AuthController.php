<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\{CustomerProfile, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau password belum sesuai.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        if (!$request->user()->active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'Akun ini sedang tidak aktif.']);
        }

        return $this->afterLogin($request);
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password belum sama.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.letters' => 'Password harus mengandung minimal satu huruf.',
            'password.numbers' => 'Password harus mengandung minimal satu angka.',
        ]);

        $user = User::create([
            'name' => trim($data['name']),
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'customer',
            'admin_enabled' => false,
            'active' => true,
        ]);

        CustomerProfile::create(['user_id' => $user->id]);
        Auth::login($user);
        $request->session()->put('acting_as', 'customer');
        try {
            $user->sendEmailVerificationNotification();
            $message = 'Akun berhasil dibuat. Link verifikasi sudah dikirim ke emailmu.';
        } catch (\Throwable $e) {
            report($e);
            $message = 'Akun berhasil dibuat, tetapi email verifikasi belum berhasil dikirim. Gunakan tombol kirim ulang di halaman berikut.';
        }

        return redirect()
            ->route('verification.notice')
            ->with('success', $message);
    }

    public function showMode(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->active && !$user->isOwner() && $user->canAccessAdmin(), 404);

        return view('auth.choose-mode');
    }

    public function chooseMode(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->active && !$user->isOwner() && $user->canAccessAdmin(), 404);

        $data = $request->validate([
            'mode' => ['required', Rule::in(['customer', 'admin'])],
        ]);

        if ($data['mode'] === 'customer') {
            $user->customerProfile()->firstOrCreate([]);
            $request->session()->put('acting_as', 'customer');

            if (!$user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }

            return redirect()->route('customer.dashboard')->with('success', 'Mode Customer aktif.');
        }

        $request->session()->put('acting_as', 'admin');

        if (!$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->route('owner.dashboard')->with('success', 'Mode Admin aktif.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Kamu sudah keluar dari akun.');
    }

    private function afterLogin(Request $request)
    {
        $user = $request->user();

        if ($user->isOwner()) {
            $request->session()->put('acting_as', 'owner');
            return redirect()->route('owner.dashboard');
        }

        $user->customerProfile()->firstOrCreate([]);

        if ($user->canAccessAdmin()) {
            $request->session()->forget('acting_as');
            return redirect()->route('auth.mode');
        }

        $request->session()->put('acting_as', 'customer');

        if (!$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->intended(route('customer.dashboard'));
    }
}
