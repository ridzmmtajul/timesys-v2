<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeOffice extends Model
{
    protected $fillable = [
        'employee_id',
        'office_id',
        'employee_no',
        'synced_from',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function office()
    {
        return $this->belongsTo(Office::class);
    }
}
