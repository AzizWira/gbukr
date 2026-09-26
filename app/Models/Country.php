<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'code',
        'name',
        'currency_code',
        'currency_symbol',
        'rate',
        'admin_fee_idr',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:4',
            'admin_fee_idr' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function shippingOptions()
    {
        return $this->hasMany(ShippingOption::class);
    }

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

    public function moneySymbol(): string
    {
        return trim((string) ($this->currency_symbol ?: $this->currency_code));
    }
}
