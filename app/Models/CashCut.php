<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashCut extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cash_register_id',
        'user_id',
        'opening_amount',
        'total_sales',
        'cash_sales',
        'card_sales',
        'transfer_sales',
        'total_expenses',
        'total_incomes',
        'expected_cash',
        'real_cash',
        'difference_amount',
        'notes',
    ];

    // Caja a la que pertenece el corte
    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }

    // Usuario que realizó el corte
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
