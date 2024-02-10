<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class EventAddImagesRequest extends FormRequest
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
            // images must be an array of minimum length 1
            'images'   => 'required|array|min:1',
            // all images must be of type either jpg, png or jpeg
            // file size limit is 10MB
            'images.*' => 'required|image|mimes:jpg,png,jpeg|max:10240'
        ];
    }

    public function messages()
    {
        return [
            'images.*.mimes' => 'Unprocessable image mime detected.',
            'images.*.max'   => 'Select images of size below 10MB.'
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
