<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'business_id',
        'parent_id',
        'name',
        'description',
    ];

    public function sources()
    {
        return $this->hasMany(CategorySource::class);
    }
}
