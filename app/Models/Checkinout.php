<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Checkinout extends Model
{
    use HasFactory;

    protected $table = 'checkinout';

    protected $fillable = [
        'badge_number',
        'check_time',
        'serial_number',
        'date_posted',
        'posted_by',
        'status',
    ];

    protected $casts = [
        'check_time' => 'datetime',
        'date_posted' => 'datetime',
        'status' => 'boolean',
    ];
}
