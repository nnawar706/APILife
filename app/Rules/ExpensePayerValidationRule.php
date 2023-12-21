<?php

namespace App\Rules;

use App\Models\Event;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ExpensePayerValidationRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $userIds = array_map(function ($item) {
                return $item['user_id'];
            }, $value);

            $event = Event::find(request()->input('event_id'));

            if (count(array_unique($userIds)) !== count($userIds)) {
                $fail('Duplicate user expense detected.');
            } else if (($event->participants()->whereIn('users.id', $userIds)->count() != count($userIds))) {
                $fail('Some users do not belong to the participant list.');
            }
        } catch (\Throwable $th) {
            $fail('Invalid payload, some fields are missing.');
        }
    }
}
