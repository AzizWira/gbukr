<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\{CustomerProfile, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class GoogleController extends Controller
{
    public function redirect(Request $request)
    {
        $callback = $this->callbackUrl($request);

        // Simpan origin hanya untuk diagnosa/fallback. OAuth state tetap dikelola
        // Socialite melalui session agar perlindungan CSRF tidak dinonaktifkan.
        $request->session()->put('google_oauth_origin', $request->getSchemeAndHttpHost());
        $request->session()->put('google_oauth_callback', $callback);

        return Socialite::driver('google')
            ->redirectUrl($callback)
            ->redirect();
    }

    public function callback(Request $request)
    {
        // Google mengembalikan error=access_denied ketika user menekan Batal.
        // Jangan panggil Socialite->user() pada kondisi ini karena tidak ada code.
        if ($request->filled('error')) {
            $request->session()->forget(['google_oauth_origin', 'google_oauth_callback']);

            if ($request->string('error')->toString() === 'access_denied') {
                return redirect()->route('login')
                    ->with('info', 'Login dengan Google dibatalkan.');
            }

            return redirect()->route('login')
                ->withErrors(['google' => 'Google tidak dapat menyelesaikan proses login. Silakan coba lagi.']);
        }

        $callback = $this->callbackUrl($request);

        try {
            $google = Socialite::driver('google')
                ->redirectUrl($callback)
                ->user();
        } catch (InvalidStateException $e) {
            report($e);
            $request->session()->forget(['google_oauth_origin', 'google_oauth_callback']);

            return redirect()->route('login')->withErrors([
                'google' => 'Sesi login Google tidak cocok atau sudah kedaluwarsa. Buka kembali halaman login dan coba lagi dari alamat website yang sama.',
            ]);
        } catch (Throwable $e) {
            report($e);
            $request->session()->forget(['google_oauth_origin', 'google_oauth_callback']);

            return redirect()->route('login')->withErrors([
                'google' => 'Login Google belum dapat diproses. Silakan coba lagi atau gunakan email dan password.',
            ]);
        }

        $request->session()->forget(['google_oauth_origin', 'google_oauth_callback']);

        $email = strtolower((string) $google->getEmail());
        if ($email === '') {
            return redirect()->route('login')
                ->withErrors(['google' => 'Akun Google tersebut tidak menyediakan alamat email.']);
        }

        $user = User::where('google_id', $google->getId())
            ->orWhere('email', $email)
            ->first();

        if (!$user) {
            $user = User::create([
                'name' => $google->getName() ?: $email,
                'email' => $email,
                'google_id' => $google->getId(),
                'email_verified_at' => now(),
                'role' => 'customer',
                'admin_enabled' => false,
                'active' => true,
            ]);
            CustomerProfile::create(['user_id' => $user->id]);
        } else {
            $user->forceFill([
                'google_id' => $google->getId(),
                'email_verified_at' => $user->email_verified_at ?: now(),
            ])->save();

            if (!$user->isOwner()) {
                $user->customerProfile()->firstOrCreate([]);
            }
        }

        if (!$user->active) {
            return redirect()->route('login')
                ->withErrors(['google' => 'Akun ini sedang tidak aktif. Hubungi Owner jika membutuhkan bantuan.']);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        if ($user->isOwner()) {
            $request->session()->put('acting_as', 'owner');
            return redirect()->route('owner.dashboard');
        }

        if ($user->canAccessAdmin()) {
            $request->session()->forget('acting_as');
            return redirect()->route('auth.mode');
        }

        $request->session()->put('acting_as', 'customer');

        if (!$user->customerProfile?->whatsapp && !$user->customerProfile?->line_id) {
            return redirect()->route('customer.profile')
                ->with('success', 'Login Google berhasil. Lengkapi kontak yang biasa kamu gunakan saat take.');
        }

        return redirect()->route('customer.dashboard');
    }

    private function callbackUrl(Request $request): string
    {
        // Gunakan host request saat ini agar state/session tidak pecah karena
        // perbedaan localhost vs 127.0.0.1 dan agar reverse proxy/ngrok konsisten.
        return rtrim($request->getSchemeAndHttpHost(), '/') . '/auth/google/callback';
    }
}
