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
            // check if selected users belong to the participant list
            $participant = EventParticipant::where('event_id', request()->route('id'))
                ->where('user_id', $value)->first();

            // if not, throw validation error
            if (!$participant)
            {
                $fail('Selected user does not belong to the extravaganza participant list.');
            }
        } catch (\Throwable $th)
        {
            $fail('Invalid payload, some fields are missing.');
        }
    }
}
