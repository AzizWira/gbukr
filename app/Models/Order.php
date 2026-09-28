<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'customer_id',
        'go_group_id',
        'preorder_id',
        'batch_id',
        'import_run_id',
        'import_snapshot',
        'imported_at',
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
            'import_snapshot' => 'array',
            'imported_at' => 'datetime',
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

    public function importRun()
    {
        return $this->belongsTo(ImportRun::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(StatusHistory::class, 'entity_id')
            ->where('entity_type', 'order')
            ->orderByDesc('changed_at');
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


    public function deletionRequests()
    {
        return $this->hasMany(OrderDeletionRequest::class);
    }

    public function hasFinancialHistory(): bool
    {
        return $this->invoices()
            ->where(function ($query) {
                $query->where('paid_amount', '>', 0)
                    ->orWhereHas('payments');
            })
            ->exists();
    }

    public function canBeDeletedPermanently(): bool
    {
        return !$this->hasFinancialHistory();
    }

    public function deleteBlockReason(): ?string
    {
        if ($this->hasFinancialHistory()) {
            return 'Order sudah memiliki riwayat pembayaran. Histori finansial harus dipertahankan.';
        }

        return null;
    }
}
