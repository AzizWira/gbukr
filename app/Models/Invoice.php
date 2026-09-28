<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'customer_id',
        'order_id',
        'import_run_id',
        'invoice_number',
        'type',
        'amount',
        'paid_amount',
        'penalty_amount',
        'deadline_at',
        'overdue_notified_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_amount' => 'integer',
            'penalty_amount' => 'integer',
            'deadline_at' => 'datetime',
            'overdue_notified_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function importRun()
    {
        return $this->belongsTo(ImportRun::class);
    }

    public function payments()
    {
        return $this->belongsToMany(Payment::class, 'invoice_payment')
            ->withPivot('allocated_amount')
            ->withTimestamps();
    }

    public function adjustment()
    {
        return $this->hasOne(OrderAdjustment::class);
    }

    public function recalculatePenalty(): int
    {
        if ($this->type !== 'dp' || !$this->deadline_at || now()->lte($this->deadline_at) || $this->status === 'paid') {
            return $this->penalty_amount;
        }

        $days = $this->deadline_at->copy()->startOfDay()->diffInDays(now()->startOfDay());
        $this->penalty_amount = max(0, $days) * 2000;
        $this->save();

        return $this->penalty_amount;
    }

    public function outstanding(): int
    {
        return max(0, ($this->amount + $this->penalty_amount) - $this->paid_amount);
    }
}
