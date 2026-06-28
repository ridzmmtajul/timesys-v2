<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->method() == 'POST') {
            return [
                'employee_no'        => 'required|integer|unique:employees,employee_no',
                'first_name'         => 'required|string|max:255',
                'middle_name'        => 'nullable|string|max:255',
                'last_name'          => 'required|string|max:255',
                'name_ext'           => 'nullable|string|max:255',
                'gender'             => 'nullable|string|in:Male,Female',
                'contact_no'         => 'nullable|string|max:255',
                'job_title'          => 'nullable|string|max:255',
                'is_active'          => 'boolean',
                'office_id'          => 'required|exists:offices,id',
                'employment_type_id' => 'nullable|exists:employment_types,id',
                'position_id'        => 'nullable|exists:positions,id',
                'office_division_id' => 'nullable|exists:office_divisions,id',
                'title_id'           => 'nullable|integer',
            ];
        } else {
            return [
                'employee_no'        => 'required|integer|unique:employees,employee_no,' . $this->id,
                'first_name'         => 'required|string|max:255',
                'middle_name'        => 'nullable|string|max:255',
                'last_name'          => 'required|string|max:255',
                'name_ext'           => 'nullable|string|max:255',
                'gender'             => 'nullable|string|in:Male,Female',
                'contact_no'         => 'nullable|string|max:255',
                'job_title'          => 'nullable|string|max:255',
                'is_active'          => 'boolean',
                'office_id'          => 'required|exists:offices,id',
                'employment_type_id' => 'nullable|exists:employment_types,id',
                'position_id'        => 'nullable|exists:positions,id',
                'office_division_id' => 'nullable|exists:office_divisions,id',
                'title_id'           => 'nullable|integer',
                'id'                 => 'required|exists:employees,id',
            ];
        }
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
