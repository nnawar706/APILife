<?php

namespace App\Http\Requests;

use App\Models\Event;
use App\Models\Expense;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class EventRemoveParticipantsRequest extends FormRequest
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
            'user_id'  => ['required','integer',
                            function($attr, $val, $fail) {
                                $event = Event::find($this->route('id'));

                                if (!$event)
                                {
                                    $fail('Invalid event detected.');
                                }

                                else if ($event->lead_user_id == $val) {
                                    $fail('Event lead user cannot be removed from participant list.');
                                }

                                else {
                                    $userExpenses = $event->expensePayers()->where('user_id', $val)->first();

                                    if ($userExpenses) {
                                        $fail('Users who have expense data can not be removed from participant list.');
                                    }
                                }
                            }],
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
