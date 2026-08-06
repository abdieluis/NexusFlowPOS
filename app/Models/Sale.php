<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    protected $table = 'sales';

    protected $fillable = [
        'business_id',
        'branch_id',
        'cash_register_id',
        'user_id',
        'customer_id',
        'subtotal',
        'discount',
        'tax',
        'total',
        'paid',
        'change_amount',
        'payment_method_id',
        'payment_status',
        'status',
        'sold_at',
    ];


    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }
}
