<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $fillable = [
        'source_type',
        'source_id',
        'reference',
        'item_details',
        'description_type',
        'info',
        'qty',
        'country_id',
        'tracking_number',
        'status',
        'visible_publicly',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'visible_publicly' => 'boolean',
        ];
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
