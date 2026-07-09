<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BiometricLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'biometric_id',
        'device_name',
        'action',
        'status',
        'message',
        'meta',
        'performed_by',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function biometric()
    {
        return $this->belongsTo(Biometric::class);
    }
}
