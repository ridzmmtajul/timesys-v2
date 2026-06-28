<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Schedule extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'description'      => $this->description,
            'default_timein_AM'  => $this->default_timein_AM,
            'default_timeout_AM' => $this->default_timeout_AM,
            'default_timein_PM'  => $this->default_timein_PM,
            'default_timeout_PM' => $this->default_timeout_PM,
            'schedule_type_id'   => $this->schedule_type_id,
            'schedule_type'      => $this->whenLoaded('scheduleType', fn() => [
                'id'   => $this->scheduleType->id,
                'name' => $this->scheduleType->name,
            ]),
            'no_lunch_gap'     => (bool) $this->no_lunch_gap,
        ];
    }
}
