<?php

namespace App\Rules;

use Closure;
use App\Models\Event;
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
                    $fail('Invalid extravaganza selected.');
                }

                // error if any bearer user is not present in the participant list
                else if (($event->addParticipants()
                        ->whereIn('user_id', $userIds)->count() != count($userIds))) {
                    $fail('Some users do not belong to the participant list.');
                }
                else {
                    // total expense bearer amount
                    $totalAmount = array_reduce($value, function ($carry, $item) {
                        return $carry + $item['amount'];
                    }, 0);

                    // unit cost * quantity
                    $total_amount = round(request()->input('unit_cost') * request()->input('quantity'), 2);

                    $adjustment = abs($total_amount - $totalAmount);

                    // error if total cost does not match
                    if ($adjustment > 0.5) {
                        $fail('Total expense data amount does not match with total bearer amount.');
                    }
                }
            }
        } catch (\Throwable $th)
        {
            $fail('Invalid payload, some fields are missing.');
        }
    }
}
