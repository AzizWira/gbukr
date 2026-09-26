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

    public function progressPercent(): int
    {
        if ($this->total_rows <= 0) {
            return in_array($this->status, ['completed', 'failed'], true) ? 100 : 0;
        }

        return min(100, (int) round(($this->processed_rows / $this->total_rows) * 100));
    }
}
