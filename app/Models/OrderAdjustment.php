<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderAdjustment extends Model
{
    protected $fillable = [
        'order_id',
        'invoice_id',
        'type',
        'reason',
        'amount_idr',
        'estimated_weight_grams',
        'actual_weight_grams',
        'estimated_shipping_idr',
        'actual_shipping_idr',
        'original_rate',
        'final_rate',
        'notes',
        'legacy_key',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'original_rate' => 'decimal:4',
            'final_rate' => 'decimal:4',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reasonLabel(): string
    {
        return match ($this->reason) {
            'weight' => 'Berat aktual lebih tinggi',
            'rate' => 'Rate berubah',
            'weight_rate' => 'Berat dan rate berubah',
            'tax' => 'Tax / pajak',
            'shipping_actual' => 'Shipping aktual',
            default => 'Penyesuaian lainnya',
        };
    }
}
