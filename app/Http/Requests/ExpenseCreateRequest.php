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
                                            // fetch active category with provided id
                                            $cat = ExpenseCategory::status()->find($val);

                                            // if not found, show error
                                            if (!$cat)
                                            {
                                                $fail('No active expense category found.');
                                            }
                                        }],
            'event_id'            => ['required',
                                        function($attr, $val, $fail) {
                                            // fetch event with provided id
                                            $event = Event::find($val);

                                            // if not found, show error
                                            if (!$event)
                                            {
                                                $fail('Invalid extravaganza detected.');
                                            }
                                            // check this only when id is not present in the route, meaning it is expense create route
                                            // if event is not ongoing, show error
                                            else if ($event->event_status_id != 1 && !$this->route('id'))
                                            {
                                                $fail('Unable to add expenses to extravaganzas that are not ongoing.');
                                            }
                                        }],
            // title must be a string of maximum length 150
            'title'               => 'required|string|max:150',
            // unit cost must be a number with minimum value of 1
            'unit_cost'           => 'required|numeric|min:1',
            // quantity must be a number with minimum value of 1
            'quantity'            => 'required|integer|min:1',
            // remarks must be a string of maximum length 150
            'remarks'             => 'nullable|string|max:300',
            // bearers must be an array of minimum length 1
            'bearers'             => ['required','array','min:1', new ExpenseBearerValidationRule()],
            // if payers present, it must be an array of minimum length 1
            'payers'              => ['sometimes','array','min:1', new ExpensePayerValidationRule()],
            // each payer object has amount of minimum value 1
            'payers.*.amount'     => 'required|numeric|min:1'
        ];
    }

    public function messages()
    {
        return [
            'expense_category_id.required' => 'Expense category field is required.',
            'payers.*.amount.required'     => 'All the user payment amount is required.',
            'payers.*.amount.numeric'      => 'All the user payment amount ust be numeric.',
            'payers.*.amount.min'          => 'User payment amount must be at least 1.'
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
