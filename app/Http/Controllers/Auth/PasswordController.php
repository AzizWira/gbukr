<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PasswordResetCompletedNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Throwable;

class PasswordController extends Controller
{
    private const RESET_CONTEXT_KEY = 'password_reset_context';

    public function forgot()
    {
        return view('auth.forgot-password');
    }

    public function sendReset(Request $request)
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            Password::sendResetLink($request->only('email'));
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'email' => 'Email reset password belum berhasil dikirim. Silakan coba lagi beberapa saat lagi.',
            ])->onlyInput('email');
        }

        // Jangan bocorkan apakah alamat email terdaftar atau tidak.
        return back()->with('success', 'Jika email sesuai dengan akun, instruksi reset password akan dikirim.');
    }

    public function reset(string $token, Request $request)
    {
        $email = strtolower(trim((string) $request->query('email')));
        $user = $email !== '' ? User::whereRaw('LOWER(email) = ?', [$email])->first() : null;

        if (!$user || !Password::broker()->tokenExists($user, $token)) {
            $request->session()->forget(self::RESET_CONTEXT_KEY);

            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'Link reset password tidak valid, sudah digunakan, atau sudah kedaluwarsa.']);
        }

        $request->session()->put(self::RESET_CONTEXT_KEY, [
            'user_id' => $user->id,
            'token' => $token,
            'created_at' => now()->timestamp,
        ]);

        return view('auth.reset-password', [
            'email' => $user->email,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password belum sama.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.letters' => 'Password harus mengandung minimal satu huruf.',
            'password.numbers' => 'Password harus mengandung minimal satu angka.',
        ]);

        $context = $request->session()->get(self::RESET_CONTEXT_KEY);
        if (!is_array($context) || empty($context['user_id']) || empty($context['token'])) {
            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'Sesi reset password tidak tersedia. Buka kembali link terbaru dari email.']);
        }

        $user = User::find($context['user_id']);
        if (!$user || !Password::broker()->tokenExists($user, (string) $context['token'])) {
            $request->session()->forget(self::RESET_CONTEXT_KEY);

            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'Link reset password tidak valid, sudah digunakan, atau sudah kedaluwarsa.']);
        }

        // Email dan token sengaja berasal dari server/session. Input email/token tambahan
        // dari client (termasuk hasil manipulasi Inspect Element) tidak pernah dipakai.
        $status = Password::reset([
            'email' => $user->email,
            'token' => (string) $context['token'],
            'password' => $data['password'],
            'password_confirmation' => $request->input('password_confirmation'),
        ], function (User $resetUser, string $password) {
            $resetUser->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($resetUser));
            $resetUser->notify(new PasswordResetCompletedNotification);
        });

        if ($status !== Password::PASSWORD_RESET) {
            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'Link reset password tidak valid, sudah digunakan, atau sudah kedaluwarsa.']);
        }

        $request->session()->forget(self::RESET_CONTEXT_KEY);

        return redirect()->route('login')->with('success', 'Password berhasil diubah. Link reset tersebut tidak dapat digunakan kembali.');
    }
}
