<?php

namespace App\Http\Requests;

use App\Models\EventInventory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class EventInventoryRemoveParticipantRequest extends FormRequest
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
            'user_id' => ['required','integer',
                            function($attr, $val, $fail) {
                                $inventory = EventInventory::find($this->route('inventory_id'));

                                if (!$inventory)
                                {
                                    $fail('Invalid inventory.');
                                }

                                else if ($inventory->inventoryParticipants()->where('user_id', $val)->doesntExist())
                                {
                                    $fail('Selected user does not belong to the inventory participant list.');
                                }
                            }]
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
