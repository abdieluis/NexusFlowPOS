<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $table = 'customers';

    protected $fillable = [
        'business_id',
        'name',
        'phone',
        'email',
        'address',
        'credit_limit',
        'current_balance'
    ];

    public function creditMovements()
    {
        return $this->hasMany(CustomerCreditMovement::class);
    }
}
