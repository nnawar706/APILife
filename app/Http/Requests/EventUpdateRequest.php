<?php

namespace App\Http\Requests;

use App\Models\Designation;
use App\Models\EventCategory;
use App\Models\User;
use App\Rules\EventDesignationGradingValidationRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class EventUpdateRequest extends FormRequest
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
            'event_category_id'   => ['required',
                                        function($attr, $val, $fail) {
                                            $cat = EventCategory::status()->find($val);

                                            if (!$cat)
                                            {
                                                $fail('No active extravaganza category found.');
                                            }
                                        }],
            'lead_user_id'        => ['required','integer',
                                        function($attr, $val, $fail) {
                                            $user = User::status()->find($val);

                                            if (!$user)
                                            {
                                                $fail('No active user found.');
                                            }
                                        }],
            'title'               => 'required|string|max:150',
            'detail'              => 'required|string|max:490',
            'from_date'           => 'required|date_format:Y-m-d H:i|after_or_equal:today',
            'to_date'             => 'nullable|date_format:Y-m-d H:i|after:from_date',
            'remarks'             => 'nullable|string|max:500',
            'designation_gradings'=> ['required','array', new EventDesignationGradingValidationRule()],
            'designation_gradings.*.amount' => 'required|numeric|min:0'
        ];
    }

    public function messages()
    {
        return [
            'event_category_id.exists' => 'Invalid extravaganza category detected.'
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
