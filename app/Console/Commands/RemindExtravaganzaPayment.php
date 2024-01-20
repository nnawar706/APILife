<?php

namespace App\Console\Commands;

use App\Models\Treasurer;
use App\Notifications\UserNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RemindExtravaganzaPayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:remind-extravaganza-payment';

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
        $treasuresExpired = Treasurer::where('completion_status', '=', 0)
            ->whereDate('deadline', '<', Carbon::now('Asia/Dhaka'))
            ->with('liabilities')
            ->get();

        $treasuresToday = Treasurer::where('completion_status', '=', 0)
            ->whereDate('deadline', '=', Carbon::now('Asia/Dhaka'))
            ->with('liabilities')
            ->get();

        if (count($treasuresExpired) != 0)
        {
            foreach ($treasuresExpired as $item)
            {
                $liabilities = $item->liabilities()
                    ->where('status', '=', 0)
                    ->where('amount','>',0)
                    ->get();

                foreach ($liabilities as $value)
                {
                    $value->user->notify(new UserNotification(
                        'pages/payments',
                        'Gentle Reminder: You have ' . $value->amount . ' tk due to pay for a treasure hunt.',
                        'Life++',
                        null
                    ));
                }
            }
        }

        if (count($treasuresToday) != 0)
        {
            foreach ($treasuresToday as $item)
            {
                $liabilities = $item->liabilities()
                    ->where('status', '=', 0)
                    ->where('amount','>',0)
                    ->get();

                foreach ($liabilities as $value)
                {
                    $value->user->notify(new UserNotification(
                        'pages/payments',
                        'Gentle Reminder: Deadline for an extravaganza settlement is expiring today.',
                        'Life++',
                        null
                    ));
                }
            }
        }
    }
}
