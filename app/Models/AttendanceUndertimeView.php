<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceUndertimeView extends Model
{
    protected $table      = 'attendance_undertime_view';
    public    $timestamps = false;
    public    $incrementing = false;
    protected $primaryKey = null;

    protected $casts = [
        'date'                        => 'date:Y-m-d',
        'grace_minutes'               => 'integer',
        'late_minutes_AM'             => 'integer',
        'undertime_lunch_out_minutes' => 'integer',
        'late_minutes_PM'             => 'integer',
        'undertime_end_of_day_minutes'=> 'integer',
        'total_undertime_minutes'     => 'integer',
    ];
}
