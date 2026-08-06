<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sale_id',
        'product_id',
        'product_variant_id',
        'product_unit_id',
        'sale_type_id',
        'quantity',
        'price',
        'discount',
        'tax',
        'subtotal',
    ];


    protected $casts = [
        'quantity' => 'decimal:3',
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];


    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }


    public function product()
    {
        return $this->belongsTo(Product::class);
    }


    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }


    public function productUnit()
    {
        return $this->belongsTo(ProductUnit::class);
    }


    public function saleType()
    {
        return $this->belongsTo(SaleType::class);
    }
}
