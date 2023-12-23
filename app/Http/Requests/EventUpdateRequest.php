<?php

namespace App\Http\Requests;

use App\Models\Designation;
use App\Models\User;
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

    // TODO: event status update validation

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_category_id'   => 'required|exists:event_categories,id',
            'lead_user_id'        => ['required','integer',
                                        function($attr, $val, $fail) {
                                            $user = User::find($val);

                                            if (!$user || !$user->status)
                                            {
                                                $fail('No active user found.');
                                            }
                                        }],
            'title'               => 'required|string|max:150',
            'detail'              => 'required|string',
            'from_date'           => 'required|date_format:Y-m-d|after_or_equal:today',
            'to_date'             => 'nullable|date_format:Y-m-d|after:from_date',
            'remarks'             => 'nullable|string|max:500',
            'event_status_id'     => 'required|in:1',
            'designation_gradings'=> ['required','array',
                function($attr, $val, $fail) {
                    try {
                        $designations = Designation::get();

                        if ($designations->count() != count($val)) {
                            $fail('All designation wise pricing must be present.');
                        } else {
                            $designations = array_map(function ($item) {
                                return $item['designation_id'];
                            }, $val);

                            if (count($designations) != count(array_unique($designations))) {
                                $fail('Duplicate designations detected.');
                            }
                        }
                    } catch (\Throwable $th) {
                        $fail('Invalid payload, some fields are missing.');
                    }
                }],
            'designation_gradings.*.amount' => 'required|numeric'
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
