<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        abort_unless($user && $user->active, 403);

        if ($user->isOwner()) {
            abort_unless(in_array('owner', $roles, true) || in_array('admin', $roles, true), 403);
            return $next($request);
        }

        $hasAdmin = $user->canAccessAdmin();
        $mode = $request->session()->get('acting_as');

        if (!$hasAdmin && $mode === 'admin' && $user->role === 'customer') {
            $request->session()->put('acting_as', 'customer');
            $mode = 'customer';
        }

        if ($hasAdmin && !in_array($mode, ['admin', 'customer'], true)) {
            return redirect()->route('auth.mode');
        }

        $mode ??= 'customer';

        if ($mode === 'admin') {
            if (!$hasAdmin) {
                abort(403);
            }

            if (in_array('admin', $roles, true)) {
                return $next($request);
            }

            if (in_array('customer', $roles, true)) {
                return redirect()->route('auth.mode')->withErrors([
                    'mode' => 'Beralih ke mode Customer untuk menggunakan fitur belanja dan akun customer.',
                ]);
            }

            abort(403);
        }

        if (!in_array('customer', $roles, true) || $user->role !== 'customer') {
            if ($hasAdmin && in_array('admin', $roles, true)) {
                return redirect()->route('auth.mode')->withErrors([
                    'mode' => 'Beralih ke mode Admin untuk membuka halaman operasional.',
                ]);
            }

            abort(403);
        }

        return $next($request);
    }
}
