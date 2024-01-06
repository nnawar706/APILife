<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserLoan;
use App\Notifications\UserNotification;
use Illuminate\Console\Command;

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
        $users          = User::status()->get();
        $transactions   = UserLoan::accepted();

        foreach ($users as $item)
        {
            $debited = $transactions->clone()->where('user_id', $item->id)->debited()->sum('amount')
                +
                $transactions->clone()->where('selected_user_id', $item->id)->credited()->sum('amount');

            $credited= $transactions->clone()->where('selected_user_id', $item->id)->debited()->sum('amount')
                +
                $transactions->clone()->where('user_id', $item->id)->credited()->sum('amount');

            $adjustment = $credited - $debited;

            if ($adjustment < 0)
            {
                $item->notify(new UserNotification(
                    'pages/financial-assistance/transaction-log',
                    'Gentle Reminder: Your remaining loan payable is ' . abs($adjustment) . ' Taka.',
                    'Life++',
                    null
                ));
            }
        }
    }
}
