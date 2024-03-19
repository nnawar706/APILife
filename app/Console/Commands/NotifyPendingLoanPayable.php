<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserLoan;
use Illuminate\Console\Command;
use App\Notifications\UserNotification;

class NotifyPendingLoanPayable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notify-pending-loan-payable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // fetch active users
        $users          = User::status()->get();
        // accepted loan objects
        $transactions   = UserLoan::accepted();

        foreach ($users as $item)
        {
            // total amount that user got
            // total debited amount of that user -> (user & debited) + (selected user & credited)
            $debited = $transactions->clone()->where('user_id', $item->id)->debited()->sum('amount')
                +
                $transactions->clone()->where('selected_user_id', $item->id)->credited()->sum('amount');

            // total amount that user spent
            // total credited amount of that user -> (user & credited) + (selected user & debited)
            $credited= $transactions->clone()->where('selected_user_id', $item->id)->debited()->sum('amount')
                +
                $transactions->clone()->where('user_id', $item->id)->credited()->sum('amount');

            // difference between credited and debited amount
            $adjustment = $credited - $debited;

            // if difference is less than zero, meaning that user has not returned money
            if ($adjustment < 0)
            {
                $item->notify(new UserNotification(
                    'pages/financial-assistance/transaction-log',
                    'Gentle Reminder: Your remaining loan payable is ' . abs($adjustment) . ' Taka.',
                    null,
                    'Life++',
                    null
                ));
            }
        }
    }
}
