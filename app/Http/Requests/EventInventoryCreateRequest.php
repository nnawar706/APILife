<?php

namespace App\Http\Requests;

use App\Rules\EventInventoryParticipantValidationRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class EventInventoryCreateRequest extends FormRequest
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
            'inventory_category_id' => 'required|exists:inventory_categories,id',
            'title'                 => 'required|string|max:200',
            'quantity'              => 'required|integer|min:1',
            'notes'                 => 'nullable|string|max:300',
            'user_id'                 => ['required','integer', new EventInventoryParticipantValidationRule()],
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
