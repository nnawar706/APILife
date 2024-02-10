<?php

namespace App\Http\Requests;

use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            // values of users array must be integer and unique
            'users.*'  => 'required|integer|distinct',
            // users must be an array of minimum length 1
            'users'    => ['required','array','min:1',
                        function($attr, $val, $fail) {
                            // fetch active users count whose ids are provided
                            $users = User::whereIn('id', $val)->status()->count();

                            // if count does not match provided users array's length, return error
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
