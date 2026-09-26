<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'source_label',
        'details',
        'estimated_weight_grams',
        'price_foreign',
        'price_idr',
        'dp_amount_idr',
        'stock',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'price_foreign' => 'decimal:4',
            'price_idr' => 'integer',
            'dp_amount_idr' => 'integer',
            'estimated_weight_grams' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
