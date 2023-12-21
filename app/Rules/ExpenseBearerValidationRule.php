<?php

namespace App\Rules;

use App\Models\Event;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ExpenseBearerValidationRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            // fetch an array of users
            $userIds = array_map(function ($item) {
                return $item['user_id'];
            }, $value);

            // error if multiple entry for one user exists
            if (count($userIds) !== count(array_unique($userIds))) {
                $fail('Multiple expense data detected for one user.');
            }
            else {
                $event = Event::find(request()->input('event_id'));

                if (!$event) {
                    $fail('Invalid event selected.');
                }

                // error if any bearer user or paid by user is not present in the participant list
                else if (($event->participants()->whereIn('users.id', $userIds)->count() != count($userIds))) {
                    $fail('Some users do not belong to the participant list.');
                }
                else {
                    // total expense bearer amount
                    $totalAmount = array_reduce($value, function ($carry, $item) {
                        return $carry + $item['amount'];
                    }, 0);

                    // error if total cost does not match
                    if ($totalAmount != request()->input('unit_cost') * request()->input('quantity')) {
                        $fail('Total expense data amount does not match with total spent amount.');
                    }
                }
            }
        } catch (\Throwable $th)
        {
            $fail('Invalid payload, some fields are missing.');
        }
    }
}
