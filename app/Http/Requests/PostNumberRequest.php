<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class PostNumberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->method() == "POST") {
            return [
                'name' => 'required|string|max:255|unique:post_numbers,name',
            ];
        } else {
            return [
                'name' => 'required|string|max:255|unique:post_numbers,name,' . $this->id,
                'id'   => 'required|exists:post_numbers,id',
            ];
        }
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
