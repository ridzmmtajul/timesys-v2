<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkTimeRule extends Model
{
    use HasFactory;

    protected $fillable = ['rule', 'description', 'time', 'offices'];

    protected $casts = [
        'offices' => 'array',
    ];
}
