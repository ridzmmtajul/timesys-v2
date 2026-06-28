<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class TitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->method() == "POST") {
            return [
                'abbreviation' => 'required|string|max:255|unique:titles,abbreviation',
                'description'  => 'nullable|string|max:255',
            ];
        } else {
            return [
                'abbreviation' => 'required|string|max:255|unique:titles,abbreviation,' . $this->id,
                'description'  => 'nullable|string|max:255',
                'id'           => 'required|exists:titles,id',
            ];
        }
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
