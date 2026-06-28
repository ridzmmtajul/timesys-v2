<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class User extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'username'      => $this->username,
            'employee_id'   => $this->employee_id,
            'employee_name' => $this->employee
                ? $this->employee->last_name . ', ' . $this->employee->first_name
                : null,
            'employee_no'   => $this->employee?->employee_no,
            'role_id'       => $this->role_id,
            'role_name'     => $this->role?->name,
            'isNew'         => $this->isNew,
        ];
    }
}
