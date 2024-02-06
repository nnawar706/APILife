<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('app:send-push-notification')->everyMinute();
        $schedule->command('app:remove-user-stories')->everyMinute();
        $schedule->command('app:wish-happy-birthday')->dailyAt('0:01')->timezone('Asia/Dhaka');
        $schedule->command('app:assign-user-point')->dailyAt('0:01')->timezone('Asia/Dhaka');
        $schedule->command('app:send-pet-care-reminder')->dailyAt('10:00')->timezone('Asia/Dhaka');
        $schedule->command('app:remind-extravaganza-payment')->dailyAt('11:00')->timezone('Asia/Dhaka');
        $schedule->command('app:remind-event-inventory')->dailyAt('22:00')->timezone('Asia/Dhaka');
        $schedule->command('app:notify-pending-loan-payable')->weekly();
        $schedule->command('app:remind-user-budget-exceed')->weekly();
        $schedule->command('app:send-random-notification')->monthly();
        $schedule->command('app:assign-user-badge')->monthly();
        $schedule->command('model:prune')->monthly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
