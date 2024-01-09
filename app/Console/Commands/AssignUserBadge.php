<?php

namespace App\Console\Commands;

use App\Enums\BadgeWeight;
use App\Models\Event;
use App\Models\ExpenseBearer;
use App\Models\ExpensePayer;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserLoan;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $start = Carbon::now('Asia/Dhaka')->subMonths(1);
        $end   = Carbon::now('Asia/Dhaka');

        $badge = new UserBadge();

        // calculate badge weights if no user badge object exists for current month
        if ($badge->clone()
            ->whereMonth('created_at', Carbon::now()->format('n'))
            ->doesntExist())
        {
            // fetch users that are active
            $users = User::status()->get();

            $data = [];

            // for each user calculate sum of points between one month interval
            foreach ($users as $key => $user)
            {
                $data[$key]['user_id'] = $user->id;
                $data[$key]['weight'] = intval($user->points()->whereBetween('created_at', [$start, $end])->sum('point'));
            }

            // retrieve the weights in an array
            $weights = array_map(function ($item) {
                return $item['weight'];
            }, $data);

            // get thresholds based on the fetched user weights
            $thresholds = getThresholds(max($weights), min($weights));

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

                DB::commit();
            } catch (QueryException $ex) {
                DB::rollback();
            }
        }
    }
}
