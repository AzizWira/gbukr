@extends('layouts.dashboard')
@section('title', 'Admin')

@section('dashboard')
<div class="page-head">
    <div>
        <div class="eyebrow">AKSES STAFF</div>
        <h1>Admin</h1>
        <p class="muted">Akses Admin diberikan ke akun member yang sudah ada. Akun yang sama tetap dapat dipakai sebagai customer.</p>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h3>Admin saat ini</h3>

        @forelse($admins as $admin)
            <div class="summary-row">
                <div>
                    <strong>{{ $admin->name }}</strong>
                    <div class="small muted">{{ $admin->email }}</div>
                </div>
                <form method="post" action="{{ route('owner.admins.toggle', $admin) }}" data-confirm="{{ $admin->admin_enabled ? 'Cabut akses Admin untuk member ini? Akun customernya tetap aktif.' : 'Aktifkan kembali akses Admin?' }}">
                    @csrf
                    <button class="btn {{ $admin->admin_enabled ? 'btn-danger' : 'btn-soft' }} btn-sm">
                        {{ $admin->admin_enabled ? 'Cabut Admin' : 'Aktifkan Admin' }}
                    </button>
                </form>
            </div>
        @empty
            <div class="empty">Belum ada member yang mendapat akses Admin.</div>
        @endforelse
    </div>

    <form class="card" method="post" action="{{ route('owner.admins.store') }}">
        @csrf
        <h3>Tambahkan Admin dari member</h3>
        <p class="small muted">Pilih akun customer aktif yang emailnya sudah terverifikasi. Saat login, member tersebut akan diminta memilih mode Customer atau Admin.</p>

        <div class="field" style="margin-top:14px">
            <label>Member</label>
            <select class="select" name="user_id" required @disabled($members->isEmpty())>
                <option value="">Pilih member</option>
                @foreach($members as $member)
                    <option value="{{ $member->id }}" @selected(old('user_id') == $member->id)>
                        {{ $member->name }} · {{ $member->email }}
                    </option>
                @endforeach
            </select>
        </div>

        @if($members->isEmpty())
            <div class="notice small" style="margin-top:14px">Belum ada member terverifikasi yang dapat dipromosikan.</div>
        @else
            <button class="btn btn-primary" style="margin-top:14px">Berikan akses Admin</button>
        @endif
    </form>
</div>
@endsection
