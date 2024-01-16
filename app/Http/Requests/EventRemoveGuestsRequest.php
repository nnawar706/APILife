<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class EventRemoveGuestsRequest extends FormRequest
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
            'user_id'  => ['required','integer',
                function($attr, $val, $fail) {
                    $event = Event::find($this->route('id'));

                    if (!$event)
                    {
                        $fail('Invalid extravaganza detected.');
                    }

                    else if ($event->eventGuests()
                        ->where('user_id', $val)->doesntExist()) {
                        $fail('Selected user does not belong to the guest list.');
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
