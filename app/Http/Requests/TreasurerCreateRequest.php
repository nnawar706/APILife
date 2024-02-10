<?php

namespace App\Http\Requests;

use App\Models\Event;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required','integer',
                        function($attr, $val, $fail) {
                            // fetch active user with provided user_id
                            $user = User::status()->find($val);

                            // if not found, show error
                            if (!$user)
                            {
                                $fail('No active user found.');
                            }
                        }],
            // deadline must be a date of format Y-m-d and after today
            'deadline' => 'required|date|date_format:Y-m-d|after:today',
            'events'   => ['required','array','min:1','distinct',
                            function($attr, $val, $fail) {
                                $events = Event::whereIn('id', $val);

                                if ($events->clone()->count() != count($val))
                                {
                                    $fail('Invalid extravaganzas detected.');
                                }

                                else if ($events->clone()->whereHas('treasurer')->exists())
                                {
                                    $fail('Some of the selected extravaganzas have treasurer.');
                                }

                                else if ($events->clone()->where('event_status_id', '!=', 3)->exists())
                                {
                                    $fail('Some of the extravaganzas are not approved yet.');
                                }

                                else {
                                    foreach ($events->clone()->get() as $event) {

                                        if($event->addParticipants()->where('user_id', $this->input('user_id'))->doesntExist())
                                        {
                                            $fail('Selected user did not participate in ' . $event->title);
                                            return;
                                        }
                                    }
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
