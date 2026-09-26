<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Throwable;

class VerificationController extends Controller
{
    public function notice(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->redirectForMode($request);
        }

        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->redirectForMode($request)
                ->withErrors(['email' => 'Link verifikasi ini sudah pernah digunakan.']);
        }

        $request->fulfill();

        return $this->redirectForMode($request)
            ->with('success', 'Email berhasil diverifikasi. Link verifikasi tersebut tidak dapat digunakan kembali.');
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->redirectForMode($request)
                ->with('success', 'Email akunmu sudah terverifikasi.');
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'email' => 'Email verifikasi belum berhasil dikirim. Periksa koneksi atau konfigurasi email lalu coba lagi.',
            ]);
        }

        return back()->with('success', 'Link verifikasi baru sudah dikirim ke emailmu.');
    }

    private function redirectForMode(Request $request)
    {
        $user = $request->user();

        if ($user->isOwner()) {
            $request->session()->put('acting_as', 'owner');
            return redirect()->route('owner.dashboard');
        }

        if ($user->canAccessAdmin() && !in_array($request->session()->get('acting_as'), ['customer', 'admin'], true)) {
            return redirect()->route('auth.mode');
        }

        return redirect()->route($user->isStaff() ? 'owner.dashboard' : 'customer.dashboard');
    }
}
