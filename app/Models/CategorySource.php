<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategorySource extends Model
{
    protected $fillable = [
        'category_id',
        'source',
        'source_name'
    ];


    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
