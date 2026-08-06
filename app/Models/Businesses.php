<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Businesses extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'businesses';

     */
    protected $fillable = [
        'name',
        'business_name',
        'tax_id',
        'phone',
        'email',
        'address',
        'logo',
        'currency',
        'timezone',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethods::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }
}
