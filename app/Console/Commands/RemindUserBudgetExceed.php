<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\UserNotification;
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
        $users = User::status()->get();

        // fetch sum of incomes, expenses and min. saving
        foreach ($users as $user)
        {
            $income = $user->budgetIncomes()->sum('amount');
            $expense = $user->budgetExpenses()->sum('amount');
            $budget  = $user->budget()->first();

            if ($budget)
            {
                // when remaining saving is lower than min. saving amount, alert users
                if (($income - $expense) < $budget->target_saving)
                {
                    $user->notify(new UserNotification(
                        'pages/accounts/pocket-devil',
                        'Gentle Reminder: Your expense has exceeded your target saving amount.',
                        'Life++',
                        null
                    ));
                }

                // when remaining saving is about to cross min. saving amount, alert users
                else if (($income - $expense) <= ($budget->target_saving + 500))
                {
                    $user->notify(new UserNotification(
                        'pages/accounts/pocket-devil',
                        'Gentle Reminder: Your expense will exceed your target saving amount soon.',
                        'Life++',
                        null
                    ));
                }
            }
        }
    }
}
