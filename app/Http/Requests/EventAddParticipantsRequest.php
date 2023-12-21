<?php

namespace App\Http\Requests;

use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class EventAddParticipantsRequest extends FormRequest
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
            'users.*'  => 'required|integer|distinct',
            'users'    => ['required','array',
                        function($attr, $val, $fail) {
                            $users = User::whereIn('id', $val)->where('status', true)->count();

                            if ($users !== count($val))
                            {
                                $fail('Some of the participants are not active.');
                            }
                        }]
        ];
    }

    public function messages()
    {
        return [
            'users.*.distinct'  => 'Duplicate users detected.'
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
