<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $request->user()->load('customerProfile');
        return view('customer.profile', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['nullable', 'string', 'max:100'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'line_id' => ['nullable', 'string', 'max:100'],
            'source_channel' => ['nullable', 'in:whatsapp,line,other'],
        ]);

        if (($data['source_channel'] ?? null) === 'whatsapp' && blank($data['whatsapp'] ?? null)) {
            throw ValidationException::withMessages(['whatsapp' => 'Isi nomor WhatsApp jika WhatsApp dipilih sebagai channel utama.']);
        }

        if (($data['source_channel'] ?? null) === 'line' && blank($data['line_id'] ?? null)) {
            throw ValidationException::withMessages(['line_id' => 'Isi LINE jika LINE dipilih sebagai channel utama.']);
        }

        $request->user()->update(['name' => trim($data['name'])]);
        $request->user()->customerProfile()->updateOrCreate([], array_diff_key($data, ['name' => true]));

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
