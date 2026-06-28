<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Employee extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'employee_no'         => $this->employee_no,
            'first_name'          => $this->first_name,
            'middle_name'         => $this->middle_name,
            'last_name'           => $this->last_name,
            'name_ext'            => $this->name_ext,
            'full_name'           => trim($this->last_name . ', ' . $this->first_name . ' ' . $this->middle_name),
            'gender'              => $this->gender,
            'contact_no'          => $this->contact_no,
            'job_title'           => $this->job_title,
            'is_active'           => $this->is_active,
            'image'               => $this->image,
            'title_id'            => $this->title_id,
            'office_id'           => $this->office_id,
            'office_name'         => $this->office?->name,
            'employment_type_id'  => $this->employment_type_id,
            'employment_type'     => $this->employmentType?->name,
            'position_id'         => $this->position_id,
            'position'            => $this->position?->name,
            'office_division_id'  => $this->office_division_id,
            'office_division'     => $this->officeDivision?->name,
        ];
    }
}
