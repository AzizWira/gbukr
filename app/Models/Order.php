<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'go_group_id',
        'preorder_id',
        'batch_id',
        'source_type',
        'order_number',
        'status',
        'rate_snapshot',
        'currency_code',
        'arrived_gbu_at',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'rate_snapshot' => 'decimal:4',
            'arrived_gbu_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function goGroup()
    {
        return $this->belongsTo(GoGroup::class);
    }

    public function preorder()
    {
        return $this->belongsTo(Preorder::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function adjustments()
    {
        return $this->hasMany(OrderAdjustment::class);
    }
}
