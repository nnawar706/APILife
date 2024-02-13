<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Notifications\UserNotification;
use Illuminate\Database\QueryException;

class AssignUserBadge extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:assign-user-badge';

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
        $curTime = Carbon::now('Asia/Dhaka');

        $start = $curTime->clone()->startOfMonth()->subMonthsNoOverflow()->format('Y-m-d H:i:s');
        $end   = $curTime->clone()->subMonthsNoOverflow()->endOfMonth()->format('Y-m-d H:i:s');

        $curMonth = $curTime->format('n');
        $prevMonth = $curTime->clone()->startOfMonth()->subMonthsNoOverflow()->format('n');

        $badge = new UserBadge();

        // calculate badge weights if no user badge object exists for current month
        if ($badge->clone()
            ->whereMonth('created_at', $curMonth)
            ->doesntExist())
        {
            // fetch users that are active
            $users = User::status()->get();

            $data = [];

            // for each user calculate sum of points between one-month interval
            foreach ($users as $key => $user)
            {
                $data[$key]['user_id'] = $user->id;
                $pocketDevil           = 0;
                $petCareDays           = $user->petCares()->whereBetween('created_at', [$start, $end])->count() * 2;
                $weight                = intval($user->points()->whereBetween('created_at', [$start, $end])->sum('point'));

                if ($petCareDays != 0) {
                    $from = $curMonth % 2 == 0 ? 'Mickie' : 'Minnie';

                    // notify user about bonus points
                    $user->notify(new UserNotification(
                        'pages/accounts/notification',
                        'Hey '. ' 👋 ' . "it's " . $from . "! You've got " . $petCareDays . ' bonus points for taking care of us. 💝',
                        null,
                        'Life++',
                        null
                    ));
                }

                $expenses = $user->budgetExpenses()->whereMonth('created_at', $prevMonth)->sum('amount');
                $initialBudget = $user->budget()->first();

                $target = $initialBudget ? $initialBudget->target_saving : 0;

                if ($target - $expenses > 500)
                {
                    $pocketDevil = 2;
                    $user->notify(new UserNotification(
                        'pages/accounts/notification',
                        'Hey ' . ' 👋 ' . "it's your pocket devil speaking 👻 here's your bonus 2 points for saving your budget a bit.",
                        null,
                        'Life++',
                        null
                    ));
                }

                // add bonus points for pet care days
                $data[$key]['weight']  = $weight + $petCareDays + $pocketDevil;

                $user->current_streak = 0;
                $user->saveQuietly();

                Cache::forget('user_profile' . $user->id);
            }

            // retrieve the weights in an array
            $weights = array_map(function ($item) {
                return $item['weight'];
            }, $data);

            // get thresholds based on the fetched user weights
            $thresholds = getThresholds(max($weights), min($weights));

            Cache::remember('threshold', 24*60*60*3, function () use ($thresholds) {
                return $thresholds;
            });

            DB::beginTransaction();

            try {
                // assign badges based on eligibility
                for ($i = 0; $i <= 4; $i++) {
                    $users = array_filter($data, function ($value) use ($i, $thresholds) {
                        // while first loop, filter the users who have weights less than first threshold value
                        if ($i == 0) {
                            return $value['weight'] < $thresholds[$i];
                        }
                        // while last loop, filter the users who have weights greater than or equal to second last threshold value
                        else if ($i == 4) {
                            return $value['weight'] >= $thresholds[$i - 1];
                        }
                        // while other loops, fetch users that have weights in between two threshold values
                        return $value['weight'] >= $thresholds[$i - 1] && $value['weight'] < $thresholds[$i];
                    });

                    if (count($users) != 0) {
                        // assign badge along with point to each user
                        foreach ($users as $user) {
                            $badge->clone()->create([
                                'user_id' => $user['user_id'],
                                'badge_id' => $i + 1,
                                'point'    => $user['weight']
                            ]);
                        }
                    }
                }

                Cache::forget('users');
                Cache::forget('userstrue');

                DB::commit();
            } catch (QueryException $ex) {
                DB::rollback();
            }
        }
    }
}
