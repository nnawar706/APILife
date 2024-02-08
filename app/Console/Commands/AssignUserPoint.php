<?php

namespace App\Console\Commands;

use App\Enums\UserPointWeight;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\ExpenseBearer;
use App\Models\ExpensePayer;
use App\Models\User;
use App\Models\UserLoan;
use App\Models\UserPoint;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class AssignUserPoint extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:assign-user-point';

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
        $points = new UserPoint();
        // calculating total user point available in database
        $totalPoint = $points->clone()->count();

        $end = Carbon::now('Asia/Dhaka');
        // define start date from the time when the last point data was calculated
        $start = $totalPoint == 0 ? Carbon::now('Asia/Dhaka')->subCentury()->format('Y-m-d H:i') :
            Carbon::parse($points->clone()->latest()->first()->created_at)->format('Y-m-d H:i');

        // loan data that have type "lend" and status accepted between the specified time interval
        $loanLend = UserLoan::lend()->accepted()
            ->whereBetween('updated_at', [$start, $end]);

        // loan data that have type "returned" and status accepted between the specified time interval
        $loanReturned = UserLoan::returned()->accepted()
            ->whereBetween('updated_at', [$start, $end]);

        // event data that have been completed between specified time interval
        $events = Event::where('event_status_id', '=', 4)
            ->whereBetween('updated_at', [$start, $end]);

        // participant data of events that have been completed between specified time interval
        $participantsRated = EventParticipant::whereBetween('rated_at', [$start, $end])
            ->where('rated', '=', true);

        // expense bearer data of events that have been completed between specified time interval
        $bearers = ExpenseBearer::whereHas('expense.event', function ($q) use ($start, $end) {
            return $q->where('event_status_id', 4)
                ->whereBetween('updated_at', [$start, $end]);
        });

        // expense payer data of events that have been completed between specified time interval
        $payers = ExpensePayer::whereHas('expense.event', function ($q) use ($start, $end) {
            return $q->where('event_status_id', 4)
                ->whereBetween('updated_at', [$start, $end]);
        });

        // fetch active users
        $users = User::status()->get();

        DB::beginTransaction();

        try {
            foreach ($users as $user)
            {
                $weight = 0;

                // login count
                $loginCount = $user->accessLogs()->whereBetween('logged_in_at', [$start, $end])->count();

                // seen story count
//                $storySeenCount = $user->seenStories()->whereBetween('created_at', [$start, $end])->count();
//
//                $weight += $storySeenCount * UserPointWeight::getValue(UserPointWeight::POINT_1);

                // added extravaganza image count
                $addedImageCount = $user->addedImages()->whereBetween('created_at', [$start, $end])->count();

                // calculate points if current streak is greater than 7
                if ($user->current_streak > 7)
                {
                    // added story image count
                    $addedStoryCount = $user->stories()->withTrashed()->whereBetween('created_at', [$start, $end])->count();

                    $multiply = $user->current_streak - 7;

                    $weight += $addedStoryCount * $multiply;
                }

                $weight += $loginCount * UserPointWeight::getValue(UserPointWeight::POINT_1);

                $weight += $addedImageCount * UserPointWeight::getValue(UserPointWeight::POINT_5);

                // created event count
                $eventCreated = $events->clone()->where('added_by_user_id', $user->id)->count();

                $weight += $eventCreated * UserPointWeight::getValue(UserPointWeight::POINT_10);

                // attended event count
                $eventAttended = $events->clone()->whereHas('addParticipants', function ($q) use ($user) {
                    return $q->where('user_id', $user->id);
                })->count();

                $weight += $eventAttended * UserPointWeight::getValue(UserPointWeight::POINT_20);

                // lead event count
                $eventLead = $events->clone()->where('lead_user_id', $user->id)->count();

                $weight += $eventLead * UserPointWeight::getValue(UserPointWeight::POINT_40);

                // treasured event count
                $eventTreasured = $events->clone()->whereHas('treasurer.treasurer', function ($q) use ($user) {
                    return $q->where('user_id', $user->id);
                })->count();

                $weight += $eventTreasured * UserPointWeight::getValue(UserPointWeight::POINT_35);

                // expense paid
                $expensePayers = $payers->clone()->where('user_id', $user->id)->sum('amount');

                if ($expensePayers != 0) {
                    $weight += $expensePayers > 1500 ? UserPointWeight::getValue(UserPointWeight::POINT_17) :
                        (
                        ($expensePayers > 500 && $expensePayers < 1500) ? UserPointWeight::getValue(UserPointWeight::POINT_13) :
                            UserPointWeight::getValue(UserPointWeight::POINT_9)
                        );
                }

                // expense bear
                $expenseBears = $bearers->where('is_sponsored', '=', 0)
                    ->where('user_id', $user->id)->sum('amount');

                if ($expenseBears != 0) {
                    $weight += $expenseBears > 1500 ? UserPointWeight::getValue(UserPointWeight::POINT_23) :
                        (
                        ($expenseBears > 500 && $expenseBears < 1500) ? UserPointWeight::getValue(UserPointWeight::POINT_15) :
                            UserPointWeight::getValue(UserPointWeight::POINT_12)
                        );
                }

                // event rating
                $ratedEvents = $participantsRated->clone()->where('user_id', $user->id)->count();

                $weight += $ratedEvents;

                // loans lend
                $lendLoanSum = $loanLend->clone()->where('user_id', $user->id)->credited()->sum('amount')
                    + $loanLend->clone()->where('selected_user_id', $user->id)->debited()->sum('amount');

                if ($lendLoanSum > 100) {
                    $weight += $lendLoanSum > 5000 ? UserPointWeight::getValue(UserPointWeight::POINT_20) :
                        (
                        ($lendLoanSum < 5000 && $lendLoanSum > 1500) ? UserPointWeight::getValue(UserPointWeight::POINT_15) :
                            (
                            ($lendLoanSum > 500 && $lendLoanSum < 1500) ? UserPointWeight::getValue(UserPointWeight::POINT_10) :
                                UserPointWeight::getValue(UserPointWeight::POINT_5)
                            )
                        );
                }

                // loans returned
                $loanReturnedSum = $loanReturned->clone()->where('user_id', $user->id)->credited()->count()
                    + $loanReturned->clone()->where('selected_user_id', $user->id)->debited()->count();

                if ($loanReturnedSum != 0)
                {
                    $weight += UserPointWeight::getValue(UserPointWeight::POINT_10) * $loanReturnedSum;
                }

                // sponsors
                $sponsorSum = $bearers->clone()->where('is_sponsored', '=', 1)
                    ->where('user_id', $user->id)->sum('amount');

                if ($sponsorSum != 0) {
                    $weight += $sponsorSum > 1500 ? UserPointWeight::getValue(UserPointWeight::POINT_32) :
                        (
                        ($sponsorSum > 1000 && $sponsorSum < 1500) ? UserPointWeight::getValue(UserPointWeight::POINT_24) :
                            (
                            ($sponsorSum > 500 && $sponsorSum < 1000) ? UserPointWeight::getValue(UserPointWeight::POINT_20) :
                                (
                                ($sponsorSum > 200 && $sponsorSum < 500) ? UserPointWeight::getValue(UserPointWeight::POINT_16) :
                                    UserPointWeight::getValue(UserPointWeight::POINT_12)
                                )
                            )
                        );
                }

                // calculate points and save if weight is not zero
                if ($weight != 0)
                {
                    $points->clone()->create([
                        'user_id' => $user->id,
                        'point'   => $weight
                    ]);
                }
            }

            DB::commit();
        } catch (QueryException $ex)
        {
            DB::rollback();
        }
    }
}
