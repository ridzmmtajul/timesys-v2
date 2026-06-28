<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class WorkTimeRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->method() == "POST") {
            return [
                'rule'        => 'required|in:Grace Period,Work Time',
                'time'        => 'required|integer|min:0|max:15',
                'description' => 'nullable|string|max:255',
                'offices'     => 'nullable|array',
            ];
        } else {
            return [
                'rule'        => 'required|in:Grace Period,Work Time',
                'time'        => 'required|integer|min:0|max:15',
                'description' => 'nullable|string|max:255',
                'offices'     => 'nullable|array',
                'id'          => 'required|exists:work_time_rules,id',
            ];
        }
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
