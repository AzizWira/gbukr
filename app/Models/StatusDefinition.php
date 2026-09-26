<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusDefinition extends Model
{
    protected $fillable = ['code','label','color','sort_order','active','system'];
    protected function casts(): array { return ['active'=>'boolean','system'=>'boolean','sort_order'=>'integer']; }

    public static function activeOrdered()
    {
        return static::query()->where('active', true)->orderBy('sort_order')->orderBy('id');
    }
}
