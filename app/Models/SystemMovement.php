<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SystemMovement extends Model
{
    use SoftDeletes;

    protected $table = 'system_movements';


    protected $fillable = [
        'business_id',
        'branch_id',
        'user_id',
        'module',
        'type',
        'description',
        'reference_type',
        'reference_id',
    ];


    protected $casts = [
        'reference_id' => 'integer',
    ];


    // Empresa
    public function business()
    {
        return $this->belongsTo(Businesses::class);
    }


    // Sucursal donde ocurrió
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }


    // Usuario que realizó el movimiento
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
