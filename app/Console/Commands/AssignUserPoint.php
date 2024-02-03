<?php

namespace App\Console\Commands;

use App\Enums\BadgeWeight;
use App\Models\Event;
use App\Models\ExpenseBearer;
use App\Models\ExpensePayer;
use App\Models\User;
use App\Models\UserBadge;
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
        $loan = UserLoan::lend()->accepted()
            ->whereBetween('updated_at', [$start, $end]);

        // event data that have been completed between specified time interval
        $events = Event::where('event_status_id', '=', 4)
            ->whereBetween('updated_at', [$start, $end]);

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

                // added extravaganza image count
//                $addedImageCount = $user->addedImages()->whereBetween('created_at', [$start, $end])->count();

                // added story image count
//                $addedStoryCount = $user->stories()->withTrashed()->whereBetween('created_at', [$start, $end])->count();

//                $multiply = $user->current_streak == 0 ? 1 : $user->current_streak;

                $weight += $loginCount * BadgeWeight::getValue(BadgeWeight::USER_LOGIN_COUNT);

//                $weight += $addedImageCount * BadgeWeight::getValue(BadgeWeight::USER_LOGIN_COUNT);
//
//                $weight += $storySeenCount * BadgeWeight::getValue(BadgeWeight::USER_STORY_VIEW_COUNT);
//
//                $weight += $addedStoryCount * $multiply;

                // created event count
                $eventCreated = $events->clone()->where('added_by_user_id', $user->id)->count();

                $weight += $eventCreated * BadgeWeight::getValue(BadgeWeight::EVENTS_CREATED);

                // attended event count
                $eventAttended = $events->clone()->whereHas('addParticipants', function ($q) use ($user) {
                    return $q->where('user_id', $user->id);
                })->count();

                $weight += $eventAttended * BadgeWeight::getValue(BadgeWeight::EVENTS_ATTENDED);

                // lead event count
                $eventLead = $events->clone()->where('lead_user_id', $user->id)->count();

                $weight += $eventLead * BadgeWeight::getValue(BadgeWeight::EVENTS_LED);

                // treasured event count
                $eventTreasured = $events->clone()->whereHas('treasurer.treasurer', function ($q) use ($user) {
                    return $q->where('user_id', $user->id);
                })->count();

                $weight += $eventTreasured * BadgeWeight::getValue(BadgeWeight::EVENTS_TREASURED);

                // expense paid
                $expensePayers = $payers->clone()->where('user_id', $user->id)->sum('amount');

                if ($expensePayers != 0) {
                    $weight += $expensePayers > 1500 ? BadgeWeight::getValue(BadgeWeight::EXPENSE_PAID_ABOVE_1500) :
                        (
                        ($expensePayers > 500 && $expensePayers < 1500) ? BadgeWeight::getValue(BadgeWeight::EXPENSE_PAID_500_TO_1500) :
                            BadgeWeight::getValue(BadgeWeight::EXPENSES_PAID_BELOW_500)
                        );
                }

                // expense bear
                $expenseBears = $bearers->where('is_sponsored', '=', 0)
                    ->where('user_id', $user->id)->sum('amount');

                if ($expenseBears != 0) {
                    $weight += $expenseBears > 1500 ? BadgeWeight::getValue(BadgeWeight::EXPENSE_BEAR_ABOVE_1500) :
                        (
                        ($expenseBears > 500 && $expenseBears < 1500) ? BadgeWeight::getValue(BadgeWeight::EXPENSE_BEAR_500_TO_1500) :
                            BadgeWeight::getValue(BadgeWeight::EXPENSES_BEAR_BELOW_500)
                        );
                }

                // loans
                $lendLoanSum = $loan->clone()->where('user_id', $user->id)->credited()->sum('amount')
                    + $loan->clone()->where('selected_user_id', $user->id)->debited()->sum('amount');

                if ($lendLoanSum != 0) {
                    $weight += $lendLoanSum > 5000 ? BadgeWeight::getValue(BadgeWeight::LOAN_ABOVE_5000) :
                        (
                        ($lendLoanSum < 5000 && $lendLoanSum > 1500) ? BadgeWeight::getValue(BadgeWeight::LOAN_ABOVE_1500) :
                            (
                            ($lendLoanSum > 500 && $lendLoanSum < 1500) ? BadgeWeight::getValue(BadgeWeight::LOAN_500_TO_1500) :
                                BadgeWeight::getValue(BadgeWeight::LOAN_BELOW_500)
                            )
                        );
                }

                // sponsors
                $sponsorSum = $bearers->clone()->where('is_sponsored', 1)->where('user_id', $user->id)->sum('amount');

                if ($sponsorSum != 0) {
                    $weight += $sponsorSum > 1500 ? BadgeWeight::getValue(BadgeWeight::SPONSOR_ABOVE_1500) :
                        (
                        ($sponsorSum > 1000 && $sponsorSum < 1500) ? BadgeWeight::getValue(BadgeWeight::SPONSOR_1000_TO_1500) :
                            (
                            ($sponsorSum > 500 && $sponsorSum < 1000) ? BadgeWeight::getValue(BadgeWeight::SPONSOR_500_TO_1000) :
                                (
                                ($sponsorSum > 200 && $sponsorSum < 500) ? BadgeWeight::getValue(BadgeWeight::SPONSOR_200_TO_500) :
                                    BadgeWeight::getValue(BadgeWeight::SPONSOR_BELOW_200)
                                )
                            )
                        );
                }

                // calculate points and save if weight is not zero
                if ($weight != 0)
                {
                    $points->clone()->create([
                        'user_id' => $user->id,
                        'point' => $weight
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
