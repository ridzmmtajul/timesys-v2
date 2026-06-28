<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class EmploymentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->method() == "POST") {
            return [
                'name'        => 'required|string|max:255|unique:employment_types,name',
                'description' => 'nullable|string|max:255',
            ];
        } else {
            return [
                'name'        => 'required|string|max:255|unique:employment_types,name,' . $this->id,
                'description' => 'nullable|string|max:255',
                'id'          => 'required|exists:employment_types,id',
            ];
        }
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
