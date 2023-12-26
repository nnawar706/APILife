<?php

namespace App\Http\Requests;

use App\Models\Event;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class TreasurerCreateRequest extends FormRequest
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
            'user_id' => ['required','integer',
                        function($attr, $val, $fail) {
                            $user = User::find($val);

                            if (!$user)
                            {
                                $fail('Invalid user detected.');
                            }
                            else if (!$user->status)
                            {
                                $fail('Selected user is not active.');
                            }
                        }],
            'events'   => ['required','array','min:1','distinct',
                            function($attr, $val, $fail) {
                                $events = Event::whereIn('id', $val);

                                if ($events->clone()->count() != count($val))
                                {
                                    $fail('Duplicate extravaganzas detected.');
                                }

                                else if ($events->clone()->whereHas('treasurer')->exists())
                                {
                                    $fail('Some of the selected extravaganzas have treasurer.');
                                }

                                else if ($events->clone()->where('event_status_id', '=', 1)->exists())
                                {
                                    $fail('Some of the extravaganzas are not approved yet.');
                                }
                            }]
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
