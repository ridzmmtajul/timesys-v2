<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Biometric extends Model
{
    protected $fillable = [
        'device_name',
        'status',
        'ip_address',
        'port',
        'product_name',
        'user_count',
        'admin_count',
        'serial_number',
        'password',
        'log_count',
    ];
}
