<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Manufacturer extends Model
{
    protected $fillable = ['name'];

    public function brands()
    {
        return $this->hasMany(Brand::class);
    }
}
