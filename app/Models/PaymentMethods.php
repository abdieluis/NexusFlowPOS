<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethods extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'payment_methods';

    protected $fillable = [
        'business_id',
        'name',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Businesses::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'payment_method_id');
    }

    public function salePayments(): HasMany
    {
        return $this->hasMany(SalePayment::class, 'payment_method_id');
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(CashMovement::class, 'payment_method_id');
    }
}
