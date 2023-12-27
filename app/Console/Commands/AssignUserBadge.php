<?php

namespace App\Console\Commands;

use App\Enums\BadgeWeight;
use App\Models\User;
use App\Models\UserBadge;
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
        $badge = new UserBadge();

        if ($badge->clone()
            ->whereMonth('created_at', Carbon::now()->format('n'))
            ->doesntExist())
        {
            $data = [];

            $users = User::get();

            foreach ($users as $key => $user) {
                $data[$key]['user_id'] = $user->id;

                $sponsors = $user->sponsors()->sum('amount');

                $attendedEvents = $user->events()->where('event_status_id', 4)->count();

                $ledEvents = $user->leadEvents()->where('event_status_id', 4)->count();

                $treasuredEvents = $user->collectedTreasures()->count();

                $eventExpenses = $user->payments()
                    ->whereHas('expense', function ($q) {
                        return $q->whereHas('event', function ($q) {
                            return $q->where('event_status_id', 4);
                        });
                    })->sum('amount');

                $data[$key]['weight'] = $sponsors * BadgeWeight::getValue(BadgeWeight::SPONSOR) +
                    $attendedEvents * BadgeWeight::getValue(BadgeWeight::ATTENDED_EVENTS) +
                    $treasuredEvents + BadgeWeight::getValue(BadgeWeight::EVENTS_TREASURED) +
                    $ledEvents + BadgeWeight::getValue(BadgeWeight::EVENTS_LED) +
                    $eventExpenses + BadgeWeight::getValue(BadgeWeight::EVENTS_EXPENSES);
            }

            $weights = array_map(function ($item) {
                return $item['weight'];
            }, $data);

            $thresholds = getThresholds(max($weights), min($weights));

            DB::beginTransaction();

            try {
                for ($i = 0; $i < 4; $i++) {
                    $users = array_filter($data, function ($value) use ($i, $thresholds) {
                        if ($i == 0) {
                            return $value['weight'] > 0 && $value['weight'] < $thresholds[$i];
                        } else if ($i == 3) {
                            return $value['weight'] > $thresholds[$i - 1];
                        }
                        return $value['weight'] > $thresholds[$i - 1] && $value['weight'] < $thresholds[$i];
                    });

                    if (count($users) != 0) {
                        foreach ($users as $user) {
                            $badge->clone()->create([
                                'user_id' => $user['user_id'],
                                'badge_id' => $i + 1
                            ]);
                        }
                    }
                }
                DB::commit();
            } catch (QueryException $ex) {
                DB::rollback();

                Log::error('error: ' . $ex->getMessage());
            }
        }
    }
}
