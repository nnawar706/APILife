<?php

namespace App\Http\Requests;

use App\Models\Event;
use App\Models\ExpenseCategory;
use App\Rules\ExpenseBearerValidationRule;
use App\Rules\ExpensePayerValidationRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class ExpenseCreateRequest extends FormRequest
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
            'expense_category_id' => ['required',
                                        function($attr, $val, $fail) {
                                            $cat = ExpenseCategory::status()->find($val);

                                            if (!$cat)
                                            {
                                                $fail('No active expense category found.');
                                            }
                                        }],
            'event_id'            => ['required',
                                        function($attr, $val, $fail) {
                                            $event = Event::find($val);

                                            if (!$event)
                                            {
                                                $fail('Invalid extravaganza detected.');
                                            }
                                            else if ($event->event_status_id != 1)
                                            {
                                                $fail('Unable to add expenses to locked extravaganzas.');
                                            }
                                        }],
            'title'               => 'required|string|max:150',
            'unit_cost'           => 'required|numeric|min:1',
            'quantity'            => 'required|integer|min:1',
            'remarks'             => 'nullable|string|max:300',
            'bearers'             => ['required','array','min:1', new ExpenseBearerValidationRule()],
            'payers'              => ['sometimes','array','min:1', new ExpensePayerValidationRule()],
            'payers.*.amount'     => 'required|numeric|min:10'
        ];
    }

    public function messages()
    {
        return [
            'expense_category_id.required' => 'Expense category field is required.',
            'payers.*.amount.required'     => 'All the user payment amount is required.',
            'payers.*.amount.numeric'      => 'All the user payment amount ust be numeric.',
            'payers.*.amount.min'          => 'User payment amount must be at least 10.'
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
