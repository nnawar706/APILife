<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class EventApproveLockRequest extends FormRequest
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
            // provided event id must be an integer
            'event_id' => ['required', 'integer',
                            function($attr, $val, $fail) {
                                // fetch event
                                $event = Event::find($val);

                                // if event not found, show error
                                if (!$event)
                                {
                                    $fail('Invalid extravaganza detected.');
                                }
                                else {
                                    // if event's status is not locked, show error (event_status_id = 2 / locked)
                                    if ($event->event_status_id != 2)
                                    {
                                        $fail('Unable to approve extravaganza until it has been locked.');
                                    }
                                    // check if auth user belongs to the participant list
                                    else if ($event->eventParticipants()
                                        ->where('user_id', auth()->user()->id)
                                        ->doesntExist())
                                    {
                                        $fail('You do not belong to the extravaganza participant list.');
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
