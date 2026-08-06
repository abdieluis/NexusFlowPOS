<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductUnit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'sale_type_id',
        'conversion_factor',
        'price',
        'is_default',
        'created_by',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:3',
        'price' => 'decimal:2',
        'is_default' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }


    public function saleType()
    {
        return $this->belongsTo(SaleType::class);
    }
}
