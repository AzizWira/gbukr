<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportRun extends Model
{
    protected $fillable = [
        'user_id',
        'go_group_id',
        'original_name',
        'stored_path',
        'status',
        'total_rows',
        'processed_rows',
        'summary',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'summary' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function goGroup()
    {
        return $this->belongsTo(GoGroup::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function progressPercent(): int
    {
        if ($this->total_rows <= 0) {
            return in_array($this->status, ['completed', 'failed'], true) ? 100 : 0;
        }

        return min(100, (int) round(($this->processed_rows / $this->total_rows) * 100));
    }
    public function statusLabel(): string
    {
        return match ($this->status) {
            'queued' => 'Menunggu antrean',
            'running' => 'Sedang diproses',
            'completed' => 'Selesai',
            'failed' => 'Gagal',
            'rolled_back' => 'Sudah dibersihkan',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function friendlyErrorMessage(): ?string
    {
        if ($this->status !== 'failed') {
            return null;
        }

        $message = trim((string) $this->error_message);
        if ($message === '') {
            return 'Import belum berhasil diselesaikan. File asli tetap tersimpan dan proses dapat dicoba ulang.';
        }

        // Jangan pernah expose SQLSTATE, query, path vendor, atau stack trace ke UI Owner.
        $technicalMarkers = ['SQLSTATE[', 'select * from', 'insert into', 'vendor\\', 'QueryException', 'FatalError'];
        foreach ($technicalMarkers as $marker) {
            if (stripos($message, $marker) !== false) {
                return 'Import berhenti karena kendala teknis saat memproses data. File asli tetap tersimpan dan proses dapat dicoba ulang setelah sistem diperbarui.';
            }
        }

        return $message;
    }

}
