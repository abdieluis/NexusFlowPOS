<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_id',
        'category_id',
        'tax_id',
        'sku',
        'barcode',
        'name',
        'brand',
        'slug',
        'description',
        'image',
        'cost',
        'price',
        'wholesale_price',
        'stock_alert',
        'track_stock',
        'has_variants',
        'status',
    ];

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'product_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

}
