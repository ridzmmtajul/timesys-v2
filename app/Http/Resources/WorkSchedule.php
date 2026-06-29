<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkSchedule extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'employee_id'      => $this->employee_id,
            'employee'         => $this->whenLoaded('employee', fn () => $this->employee ? [
                'id'          => $this->employee->id,
                'employee_no' => $this->employee->employee_no,
                'first_name'  => $this->employee->first_name,
                'last_name'   => $this->employee->last_name,
            ] : null),
            'schedule_id'      => $this->schedule_id,
            'schedule'         => $this->whenLoaded('schedule', fn () => $this->schedule ? [
                'id'   => $this->schedule->id,
                'name' => $this->schedule->name,
            ] : null),
            'schedule_type_id' => $this->schedule_type_id,
            'schedule_type'    => $this->whenLoaded('scheduleType', fn () => $this->scheduleType ? [
                'id'   => $this->scheduleType->id,
                'name' => $this->scheduleType->name,
            ] : null),
            'timein_AM'        => $this->timein_AM,
            'timeout_AM'       => $this->timeout_AM,
            'timein_PM'        => $this->timein_PM,
            'timeout_PM'       => $this->timeout_PM,
            'from_date'        => $this->from_date,
            'to_date'          => $this->to_date,
            'is_others'        => $this->is_others,
            'schedule_for'     => $this->schedule_for,
            'days'             => $this->days ?? [],
            'no_lunch_gap'     => $this->no_lunch_gap,
            'created_at'       => $this->created_at,
        ];
    }
}
