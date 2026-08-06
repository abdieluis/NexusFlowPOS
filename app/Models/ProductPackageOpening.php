<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductPackageOpening extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'package_quantity',
        'pieces_per_package',
        'remaining_pieces',
        'created_by',
    ];

    protected $casts = [
        'package_quantity' => 'integer',
        'pieces_per_package' => 'integer',
        'remaining_pieces' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class,'product_variant_id');
    }
}
