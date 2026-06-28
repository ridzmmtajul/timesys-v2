<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class HolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->method() == "POST") {
            return [
                'name'  => 'required|string|max:255',
                'month' => 'required|string',
                'day'   => 'required|integer|min:1|max:31',
            ];
        } else {
            return [
                'name'      => 'required|string|max:255',
                'month'     => 'required|string',
                'day'       => 'required|integer|min:1|max:31',
                'is_active' => 'required|boolean',
                'id'        => 'required|exists:holidays,id',
            ];
        }
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
