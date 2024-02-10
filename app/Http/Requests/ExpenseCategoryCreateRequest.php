<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class ExpenseCategoryCreateRequest extends FormRequest
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
            // name must be a string of maximum length 100
            // name must be unique in expense_categories model
            // if id is present in the route, meaning it is the update route
            // when id is present check uniqueness among expense_categories models except where id is route's id
            'name' => 'required|string|max:100|unique:expense_categories,name,'.$this->route('id'),
            // icon must be an image of type either jpeg, png or jpg
            // icon image must be square and maximum of 2MB
            'icon' => ['image', 'mimes:jpeg,png,jpg','max:2048']
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
