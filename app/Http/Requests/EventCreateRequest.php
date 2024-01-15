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
                                        $cat = EventCategory::status()->find($val);

                                        if (!$cat)
                                        {
                                            $fail('No active extravaganza category found.');
                                        }
                                    }],
            'lead_user_id'      => ['required','integer',
                                    function($attr, $val, $fail) {
                                        $user = User::status()->find($val);

                                        if (!$user)
                                        {
                                            $fail('No active user found.');
                                        }
                                    }],
            'title'             => 'required|string|max:150',
            'detail'            => 'required|string|max:490',
            'from_date'         => 'required|date_format:Y-m-d H:i',
            'to_date'           => 'nullable|date_format:Y-m-d H:i|after:from_date',
            'remarks'           => 'nullable|string|max:500',
            'participants'      => ['required','array','min:1',
                                    function($attr, $val, $fail) {
                                        $users = User::whereIn('id', $val)
                                            ->status()->count();

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
            'guests'             => ['sometimes','array','min:1',
                                    function($attr, $val, $fail) {
                                        $users = User::whereIn('id', $val)
                                            ->status()->count();

                                        if (count(array_unique($val)) != count($val))
                                        {
                                            $fail('Duplicate guests detected.');
                                        }

                                        else if (count($val) !== $users)
                                        {
                                            $fail('Some of the guests are not active.');
                                        }
                                        else if (in_array($this->input('lead_user_id'), $val))
                                        {
                                            $fail('Lead user must not be present in guest list.');
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
