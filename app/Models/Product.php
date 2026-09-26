<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'country_id',
        'type',
        'name',
        'slug',
        'description',
        'image_path',
        'active',
        'payment_scheme',
        'item_fee_idr',
        'free_shipping',
        'ems_tax',
        'tax_status',
        'apply_fansign',
        'location_note',
        'event_date',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'payment_scheme' => 'array',
            'item_fee_idr' => 'integer',
            'free_shipping' => 'boolean',
            'ems_tax' => 'boolean',
            'apply_fansign' => 'boolean',
            'event_date' => 'date',
        ];
    }

    public function taxLabel(): string
    {
        $status = $this->tax_status ?: ($this->ems_tax ? 'included_estimate' : 'excluded');

        return $status === 'included_estimate'
            ? 'Sudah termasuk estimasi tax'
            : 'Belum termasuk tax';
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function preorder()
    {
        return $this->hasOne(Preorder::class);
    }
}
