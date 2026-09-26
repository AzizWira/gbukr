<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoGroup extends Model
{
    protected $table = 'go_groups';

    protected $fillable = ['name', 'status', 'notes'];

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function importRuns()
    {
        return $this->hasMany(ImportRun::class);
    }
}
