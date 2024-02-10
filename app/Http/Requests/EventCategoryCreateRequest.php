<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class EventCategoryCreateRequest extends FormRequest
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
            // name must be unique in event_categories model
            // if id is present in the route, meaning it is the update route
            // when id is present check uniqueness among event_categories models except where id is route's id
            'name' => 'required|string|max:100|unique:event_categories,name,'.$this->route('id'),
            // icon must be an image of type either jpeg, png or jpg
            // icon image must be square and maximum of 2MB
            'icon' => 'image|mimes:jpeg,png,jpg|max:2048|dimensions:ratio=1'
        ];
    }

    public function messages()
    {
        return [
            'name.unique'     => 'Selected extravaganza category name is already taken.',
            'icon.dimensions' => 'Event category icon must be a square image.'
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
