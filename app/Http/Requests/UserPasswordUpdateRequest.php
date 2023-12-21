<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class UserPasswordUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password'      => ['required','string',
                                        function($attr, $val, $fail) {
                                            if (!Hash::check($val, auth()->user()->password))
                                            {
                                                $fail('Current password does not match.');
                                            }
                                        }],
            'password'              => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => false,
            'error'   => $validator->errors()->first(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
