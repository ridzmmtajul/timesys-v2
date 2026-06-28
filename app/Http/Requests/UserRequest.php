<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->method() == 'POST') {
            return [
                'username'    => 'required|string|max:255|unique:users,username',
                'employee_id' => 'required|exists:employees,id',
                'role_id'     => 'required|exists:roles,id',
            ];
        } else {
            return [
                'username'    => 'required|string|max:255|unique:users,username,' . $this->id,
                'employee_id' => 'required|exists:employees,id',
                'role_id'     => 'required|exists:roles,id',
                'id'          => 'required|exists:users,id',
            ];
        }
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
