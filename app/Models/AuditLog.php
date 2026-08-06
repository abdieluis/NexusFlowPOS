<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditLog extends Model
{
    use SoftDeletes;

    protected $table = 'audit_logs';

    protected $fillable = [
        'business_id',
        'user_id',
        'action',
        'model',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function business()
    {
        return $this->belongsTo(Businesses::class);
    }


    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];
}
