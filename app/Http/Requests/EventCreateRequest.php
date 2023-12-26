<?php

namespace App\Http\Requests;

use App\Models\Designation;
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
            'event_category_id' => 'required|exists:event_categories,id',
            'lead_user_id'      => ['required','integer',
                                    function($attr, $val, $fail) {
                                        $user = User::find($val);

                                        if (!$user || !$user->status)
                                        {
                                            $fail('No active user found.');
                                        }
                                    }],
            'title'             => 'required|string|max:150',
            'detail'            => 'required|string',
            'from_date'         => 'required|date_format:Y-m-d H:i|after_or_equal:today',
            'to_date'           => 'nullable|date_format:Y-m-d H:i|after:from_date',
            'remarks'           => 'nullable|string|max:500',
            'participants'      => ['required','array','min:1',
                                    function($attr, $val, $fail) {
                                        $users = User::whereIn('id', $val)
                                            ->where('status', true)->count();

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
            'designation_gradings.*.amount' => 'required|numeric|min:10'
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
