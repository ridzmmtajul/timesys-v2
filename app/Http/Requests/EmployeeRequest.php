<?php

namespace App\Http\Requests;

use App\Rules\UniqueEmployeeName;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name'  => $this->first_name  ? ucwords(strtolower($this->first_name))  : $this->first_name,
            'middle_name' => $this->middle_name  ? ucwords(strtolower($this->middle_name)) : $this->middle_name,
            'last_name'   => $this->last_name    ? ucwords(strtolower($this->last_name))   : $this->last_name,
            'name_ext'    => $this->name_ext     ? ucwords(strtolower($this->name_ext))    : $this->name_ext,
        ]);
    }

    public function rules(): array
    {
        if ($this->method() == 'POST') {
            return [
                'employee_no'        => 'required|integer|unique:employees,employee_no',
                'first_name'         => 'required|string|max:255',
                'middle_name'        => 'nullable|string|max:255',
                'last_name'          => [
                    'required', 'string', 'max:255',
                    new UniqueEmployeeName($this->first_name, $this->middle_name, $this->name_ext),
                ],
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
            $employee = $this->route('employee');

            return [
                'employee_no'        => 'required|integer|unique:employees,employee_no,' . $employee->id,
                'first_name'         => 'required|string|max:255',
                'middle_name'        => 'nullable|string|max:255',
                'last_name'          => [
                    'required', 'string', 'max:255',
                    new UniqueEmployeeName($this->first_name, $this->middle_name, $this->name_ext, $employee->id),
                ],
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
        }
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
