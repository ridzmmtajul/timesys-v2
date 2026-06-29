<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class WorkScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $common = [
            'employee_id'      => 'required|exists:employees,id',
            'schedule_for'     => 'required|string|max:255',
            'schedule_id'      => 'nullable|exists:schedules,id',
            'schedule_type_id' => 'nullable|exists:schedule_types,id',
            'timein_AM'        => 'nullable|date_format:H:i',
            'timeout_AM'       => 'nullable|date_format:H:i',
            'timein_PM'        => 'nullable|date_format:H:i',
            'timeout_PM'       => 'nullable|date_format:H:i',
            'from_date'        => 'nullable|date',
            'to_date'          => 'nullable|date',
            'is_others'        => 'boolean',
            'no_lunch_gap'     => 'boolean',
            'days'             => 'nullable|array',
            'days.*'           => 'string',
        ];

        if ($this->method() === 'POST') {
            return $common;
        }

        return array_merge($common, [
            'id' => 'required|exists:work_schedules,id',
        ]);
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
