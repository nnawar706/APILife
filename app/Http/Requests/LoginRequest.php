<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Notifications\UserNotification;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class LoginRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            // phone no must be a valid bd number
            'phone_no' => ['required', 'regex:/^(?:\+88|88)?(01[3-9]\d{8})$/',
                            function($attr, $val, $fail) {
                                // fetch active user with provided phone no
                                $user = User::status()->where('phone_no', $val)->first();

                                // if not found, show error
                                if (!$user)
                                {
                                    $fail('No active account found with given phone number.');
                                }
                            }],
            // password must be a string of minimum length 6
            'password' => 'required|string|min:6'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => false,
            'error'   => $validator->errors()->first(),
        ], Response::HTTP_BAD_REQUEST));
    }
}
