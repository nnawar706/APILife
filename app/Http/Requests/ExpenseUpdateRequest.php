<?php

namespace App\Http\Requests;

use App\Models\Event;
use App\Rules\ExpenseBearerValidationRule;
use App\Rules\ExpensePayerValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class ExpenseUpdateRequest extends FormRequest
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
            'expense_category_id' => 'required|exists:expense_categories,id',
            'event_id'            => ['required','exists:events,id',
                function($attr, $val, $fail) {
                    $event = Event::find($val);

                    if (!$event)
                    {
                        $fail('Invalid event detected.');
                    }
                    else if ($event->event_status_id != 1)
                    {
                        $fail('Unable to add expenses to locked events.');
                    }
                }],
            'title'               => 'required|string|max:150',
            'unit_cost'           => 'required|numeric|min:1',
            'quantity'            => 'required|integer|min:1',
            'remarks'             => 'nullable|string|max:300',
            'paid_at'             => 'nullable|date_format:Y-m-d H:i|before_or_equal:today',
            'bearers'             => ['sometimes','array','min:1', new ExpenseBearerValidationRule()],
            'payers'              => ['sometimes','array','min:1', new ExpensePayerValidationRule()],
            'payers.*.amount'     => 'required|numeric|min:10'
        ];
    }

    public function messages()
    {
        return [
            'payers.*.amount.required' => 'All the user payment amount is required.',
            'payers.*.amount.numeric'  => 'All the user payment amount ust be numeric.',
            'payers.*.amount.min'      => 'User payment amount must be at least 10.'
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
