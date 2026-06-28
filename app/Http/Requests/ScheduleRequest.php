<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class ScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->method() == "POST") {
            return [
                'name'               => 'required|string|max:255|unique:schedules,name',
                'description'        => 'nullable|string|max:255',
                'default_timein_AM'  => 'nullable|date_format:H:i',
                'default_timeout_AM' => 'nullable|date_format:H:i',
                'default_timein_PM'  => 'nullable|date_format:H:i',
                'default_timeout_PM' => 'nullable|date_format:H:i',
                'schedule_type_id'   => 'nullable|exists:schedule_types,id',
                'no_lunch_gap'       => 'nullable|boolean',
            ];
        } else {
            return [
                'name'               => 'required|string|max:255|unique:schedules,name,' . $this->id,
                'description'        => 'nullable|string|max:255',
                'default_timein_AM'  => 'nullable|date_format:H:i',
                'default_timeout_AM' => 'nullable|date_format:H:i',
                'default_timein_PM'  => 'nullable|date_format:H:i',
                'default_timeout_PM' => 'nullable|date_format:H:i',
                'schedule_type_id'   => 'nullable|exists:schedule_types,id',
                'no_lunch_gap'       => 'nullable|boolean',
                'id'                 => 'required|exists:schedules,id',
            ];
        }
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
