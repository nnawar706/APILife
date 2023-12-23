<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class EventUpdateStatusRequest extends FormRequest
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
            'event_status_id' => ['required','in:1,2,5', // 2: locked, 5: canceled
                                    function($attr, $val, $fail) {
                                        $event = Event::find($this->route('id'));

                                        if (!$event)
                                        {
                                            $fail('Invalid event detected.');
                                        }
                                        else {
                                            if ($val == 2)
                                            {
                                                if ($event->expensePayers()->sum('amount') != $event->amount)
                                                {
                                                    $fail('Unable to lock event when payment is not complete.');
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
