<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class OfficeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->method() == "POST") {
            return [
                'code' => 'required|string|max:255|unique:offices,code',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:255',
                'prefix' => 'nullable|string|max:255',
                'latest_employee_no' => 'nullable|integer|min:0',
            ];
        } else {
            return [
                'code' => 'required|string|max:255|unique:offices,code,' . $this->id,
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:255',
                'prefix' => 'nullable|string|max:255',
                'latest_employee_no' => 'nullable|integer|min:0',
                'id' => 'required|exists:offices,id',
            ];
        }
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
