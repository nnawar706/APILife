<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\UserNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RemindUserBudgetExceed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:remind-user-budget-exceed';

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
        // current month
        $curMonth = Carbon::now('Asia/Dhaka')->format('n');

        $users = User::status()->get();

        // fetch sum of incomes, expenses and min. saving
        foreach ($users as $user)
        {
            $expense = $user->budgetExpenses()->whereMonth('created_at', $curMonth)->sum('amount');
            $budget  = $user->budget()->first();

            if ($budget)
            {
                // when remaining saving is lower than min. saving amount, alert users
                if ($expense >= $budget->target_saving)
                {
                    $user->notify(new UserNotification(
                        'pages/accounts/pocket-devil',
                        'Gentle Reminder: Your current month expense has exceeded the allocated amount.',
                        null,
                        'Life++',
                        null
                    ));
                }

                // when remaining saving is about to cross min. saving amount, alert users
                else if (round(100 - ($expense*100/$budget->target_saving), 2) < 35)
                {
                    $user->notify(new UserNotification(
                        'pages/accounts/pocket-devil',
                        'Gentle Reminder: Your expense will exceed your target saving amount soon.',
                        null,
                        'Life++',
                        null
                    ));
                }
            }
        }
    }
}
