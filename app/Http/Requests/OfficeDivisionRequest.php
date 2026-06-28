<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class OfficeDivisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->method() == "POST") {
            return [
                'code'        => 'required|string|max:255|unique:office_divisions,code',
                'name'        => 'required|string|max:255',
                'description' => 'nullable|string|max:255',
                'office_id'   => 'required|exists:offices,id',
            ];
        } else {
            return [
                'code'        => 'required|string|max:255|unique:office_divisions,code,' . $this->id,
                'name'        => 'required|string|max:255',
                'description' => 'nullable|string|max:255',
                'office_id'   => 'required|exists:offices,id',
                'id'          => 'required|exists:office_divisions,id',
            ];
        }
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
