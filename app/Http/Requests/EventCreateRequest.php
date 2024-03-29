<?php

namespace App\Http\Requests;

use App\Models\Designation;
use App\Models\EventCategory;
use App\Models\User;
use App\Rules\EventDesignationGradingValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class EventCreateRequest extends FormRequest
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
            'event_category_id' => ['required',
                                    function($attr, $val, $fail) {
                                        // fetch active category with provided id
                                        $cat = EventCategory::status()->find($val);

                                        // if not found, show error
                                        if (!$cat)
                                        {
                                            $fail('No active extravaganza category found.');
                                        }
                                    }],
            'lead_user_id'      => ['required','integer',
                                    function($attr, $val, $fail) {
                                        // fetch active user with provided id
                                        $user = User::status()->find($val);

                                        // if no user found, show error
                                        if (!$user)
                                        {
                                            $fail('No active user found.');
                                        }
                                    }],
            // title must be a string of maximum length 150
            'title'             => 'required|string|max:150',
            // detail must be a string of maximum length 490
            'detail'            => 'required|string|max:490',
            // from date must have a format of Y-m-d H:i
            'from_date'         => 'required|date_format:Y-m-d H:i',
            // to date is not required, since event can happen only one day
            // if present, the date must be after from date and have a format of Y-m-d H:i
            'to_date'           => 'nullable|date_format:Y-m-d H:i|after:from_date',
            // not required, but if present then must be a string of maximum length 500
            'remarks'           => 'nullable|string|max:500',
            // must be an array of minimum length 3, meaning an event must have at least 3 participants
            'participants'      => ['required','array','min:3',
                                    function($attr, $val, $fail) {
                                        // fetch active users count with provided ids
                                        $users = User::whereIn('id', $val)
                                            ->status()->count();

                                        // if count does not match provided users array's length, return error
                                        if (count(array_unique($val)) != count($val))
                                        {
                                            $fail('Duplicate participants detected.');
                                        }

                                        else if (count($val) !== $users)
                                        {
                                            $fail('Some of the participants are not active.');
                                        }

                                        else if (!in_array($this->input('lead_user_id'), $val))
                                        {
                                            $fail('Lead user must be present in participant list.');
                                        }
                                    }],
            'designation_gradings'          => ['required','array', new EventDesignationGradingValidationRule()],
            'designation_gradings.*.amount' => 'required|numeric|min:0',
            'is_public'                     => 'sometimes|in:1',
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
