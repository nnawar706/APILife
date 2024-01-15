<?php

namespace App\Http\Requests;

use App\Models\EventParticipant;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class EventAddGuestsRequest extends FormRequest
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
                    $users = User::whereIn('id', $val)->status()->count();

                    if ($users !== count($val))
                    {
                        $fail('Some of the participants are not active.');
                    }

                    else if (EventParticipant::where('event_id', $this->route('id'))
                            ->whereIn('user_id', $val)->participant()->exists()) {
                        $fail('Participants cannot be added to the extravaganza guest list.');
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
