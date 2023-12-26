<?php

namespace App\Rules;

use App\Models\Designation;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class EventDesignationGradingValidationRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $designations = Designation::get();

            if ($designations->count() != count($value)) {
                $fail('All designation wise pricing must be present.');
            } else {
                $designations = array_map(function ($item) {
                    return $item['designation_id'];
                }, $value);

                if (count($designations) != count(array_unique($designations))) {
                    $fail('Duplicate designations detected.');
                }
            }
        } catch (\Throwable $th) {
            $fail('Invalid payload, some fields are missing.');
        }
    }
}
