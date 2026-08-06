<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerCreditMovement extends Model
{
    use SoftDeletes;

    protected $table = 'customer_credit_movements';

    protected $fillable = [
        'business_id',
        'customer_id',
        'user_id',
        'sale_id',
        'payment_method_id',
        'type',
        'amount',
        'previous_balance',
        'new_balance',
        'notes',
    ];


    protected $casts = [
        'amount' => 'decimal:2',
        'previous_balance' => 'decimal:2',
        'new_balance' => 'decimal:2',
    ];

    // Empresa
    public function business()
    {
        return $this->belongsTo(Businesses::class);
    }


    // Cliente dueño del crédito
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }


    // Usuario que realizó el movimiento
    public function user()
    {
        return $this->belongsTo(User::class);
    }


    // Venta relacionada (si viene de una venta a crédito)
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
