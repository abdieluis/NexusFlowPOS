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
        'is_weighted',
        'unit',
        'status',
    ];

    protected $appends = ['stock'];

    public function getStockAttribute()
    {
        return $this->relationLoaded('inventories')
            ? (float) $this->inventories->sum('stock')
            : (float) $this->inventories()->sum('stock');
    }

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

    public function productUnits()
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function packageOpenings()
    {
        return $this->hasMany(ProductPackageOpening::class);
    }

    public function defaultUnit()
    {
        return $this->hasOne(ProductUnit::class)
            ->where('is_default', true);
    }

    public function saleUnits()
    {
        return $this->hasMany(ProductUnit::class)
            ->orderByDesc('is_default');
    }

}
