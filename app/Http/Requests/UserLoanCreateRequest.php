<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class UserLoanCreateRequest extends FormRequest
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
            'selected_user_id' => ['required','integer','not_in:'.auth()->user()->id,
                                    function($attr, $val, $fail) {
                                        $user = User::find($val);

                                        if(!$user)
                                        {
                                            $fail('No active user found.');
                                        }
                                    }],
            'amount'           => 'required|numeric|min:1',
            'type'             => 'required|in:1,2',
        ];
    }

    public function messages()
    {
        return [
            'type.in'         => 'Loan type can either be debit or credit.',
            'user_id.not_in'  => 'Unable to create loan for this user.'
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
