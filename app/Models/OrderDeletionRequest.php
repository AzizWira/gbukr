<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDeletionRequest extends Model
{
    protected $fillable = [
        'order_id', 'owner_id', 'customer_id', 'import_run_id', 'source', 'status',
        'requires_customer_approval', 'reason', 'snapshot', 'requested_at',
        'responded_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'requires_customer_approval' => 'boolean',
            'snapshot' => 'array',
            'requested_at' => 'datetime',
            'responded_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function order(){ return $this->belongsTo(Order::class)->withTrashed(); }
    public function owner(){ return $this->belongsTo(User::class, 'owner_id'); }
    public function customer(){ return $this->belongsTo(User::class, 'customer_id'); }
    public function importRun(){ return $this->belongsTo(ImportRun::class); }
}
