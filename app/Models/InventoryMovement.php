<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryMovement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'product_id',
        'variant_id',
        'type',
        'quantity',
        'reference_type',
        'reference_id',
        'notes',
        'created_by'
    ];


    public function product()
    {
        return $this->belongsTo(Product::class);
    }


    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }


    public function variant()
    {
        return $this->belongsTo(ProductVariant::class,'variant_id');
    }
}
