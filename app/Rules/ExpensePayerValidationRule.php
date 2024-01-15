<?php

namespace App\Rules;

use App\Models\Event;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ExpensePayerValidationRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $userIds = array_map(function ($item) {
                return $item['user_id'];
            }, $value);

            $event = Event::find(request()->input('event_id'));

            if (!$event)
            {
                $fail('Invalid event detected.');
            }

            // check if any duplicate user exists
            else if (count(array_unique($userIds)) !== count($userIds)) {
                $fail('Duplicate user expense detected.');
            }

            // check if each payer user exists in the participant list
            else if (($event->addParticipants()
                    ->whereIn('user_id', $userIds)
                    ->participant()->count() != count($userIds))) {
                $fail('Some users do not belong to the participant list.');
            }

            // check if sum of expense amount matches total paid amount
            else {
                $totalPaid = array_sum(array_column($value, 'amount'));
                $totalPayable = request()->input('unit_cost') * request()->input('quantity');

                $adjustment = abs($totalPayable - $totalPaid);

                if ($adjustment > 0.5) {
                    $fail("Sum of expense payer's amount does not match total amount.");
                }
            }
        } catch (\Throwable $th) {
            $fail('Invalid payload, some fields are missing.');
        }
    }
}
