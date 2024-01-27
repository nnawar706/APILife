<?php

namespace App\Rules;

use App\Models\EventParticipant;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EventInventoryParticipantValidationRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $participantCount = EventParticipant::where('event_id', request()->route('id'))
                ->whereIn('user_id', $value)->count();

            if ($participantCount != count($value))
            {
                $fail('Some of the selected users do not belong to the extravaganza participant list.');
            }
        } catch (\Throwable $th)
        {
            $fail('Invalid payload, some fields are missing.');
        }
    }
}
