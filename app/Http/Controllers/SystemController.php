<?php

namespace App\Http\Controllers;

use App\Enums\BadgeWeight;
use App\Http\Services\EventService;
use App\Jobs\NotifyUsers;
use App\Jobs\TreasurerCompletion;
use App\Models\Badge;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventStatus;
use App\Models\ExpenseBearer;
use App\Models\ExpenseCategory;
use App\Models\ExpensePayer;
use App\Models\Notification;
use App\Models\Treasurer;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserLoan;
use App\Models\UserPoint;
use App\Notifications\UserNotification;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\Response;

class SystemController extends Controller
{
    public function activities()
    {
        $data = Activity::with('causer','subject')->latest()->paginate(15);

        return response()->json([
            'status'     => true,
            'total_data' => $data->total(),
            'data'       => $data->items()
        ], $data->isEmpty() ? Response::HTTP_NO_CONTENT : Response::HTTP_OK);
    }

    public function refresh()
    {
        Artisan::call('cache:clear');
        Artisan::call('optimize');
        Artisan::call('optimize:clear');
        Artisan::call('config:clear');

        return response()->json(['status' => true], 205);
    }

    public function dashboardData()
    {
        $data = $this->getDashboardData();

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    private function getDashboardData()
    {
        // variables
        $total_mfs           = 0;
        $dues                = 0;
        $monthly_user_badges = [];
        $user_wise_badge1    = [];
        $user_wise_badge2    = [];
        $current_user_points = [];

        // dates
        $end_date        = Carbon::now('Asia/Dhaka');
        $start_date_15   = Carbon::now('Asia/Dhaka')->subDays(15);
        $start_date      = Carbon::now('Asia/Dhaka')->subMonths(1);
        $start_date_week = Carbon::now('Asia/Dhaka')->subWeeks(1);

        // model variables
        $eventStatus        = EventStatus::orderBy('id');
        $user               = User::status();
        $users              = $user->clone()->with('designation')->get();
        $user_badge         = new UserBadge();
        $event_count_lifetime = $eventStatus->clone()->withCount('events')->get();
        $transactions       = UserLoan::accepted();
        $badges             = Cache::rememberForever('allBadges', function () {
                                    return Badge::orderBy('id')->get();
                                });
        $expense_categories = ExpenseCategory::leftJoin('expenses','expense_categories.id','=','expenses.expense_category_id')
                                ->leftJoin('expense_payers','expenses.id','=','expense_payers.expense_id')
                                ->leftJoin('events','expenses.event_id','=','events.id');


        $monthly_user_badges['month'] = Carbon::parse($end_date)->format('F');
        $monthly_user_badges['user_data'] = $user_badge->clone()
            ->whereMonth('created_at', Carbon::parse($end_date)->format('n'))
            ->orderByDesc('point')
            ->with('badge','user')->get();


        $event_count_30days = $eventStatus->clone()
            ->withCount(['events' => function ($q) use ($start_date, $end_date) {
            return $q->whereBetween('created_at', [$start_date, $end_date]);
        }])->get();

        $total_users = $user->clone()->count();
        $active_users = $user->clone()->whereHas('events', function ($q) use ($start_date_15, $end_date) {
            return $q->whereNotIn('event_status_id', [1,5]) // 1: ongoing, 5: canceled
                ->whereBetween('created_at', [$start_date_15, $end_date]);
        })->count();

        $transaction_count = $transactions->clone()->count();
        $transaction_amount = $transactions->clone()->sum('amount');

        $transaction_count_30days = $transactions->clone()
            ->whereBetween('created_at', [$start_date, $end_date])->count();
        $transaction_amount_30days = $transactions->clone()
            ->whereBetween('created_at', [$start_date, $end_date])->sum('amount');

        foreach ($users as $key => $item)
        {
            $user_wise_badge1[$key]['user'] = $item;

            foreach ($badges as $i => $val)
            {
                $user_wise_badge1[$key]['badges'][$i]['badge'] = $val;
                $user_wise_badge1[$key]['badges'][$i]['count'] = $val->userBadge()->where('user_id', $item->id)->count();
            }

            $current_user_points[$key]['user'] = $item;
            $current_user_points[$key]['earned_points'] = intval($item->points()->sum('point'));

            $debited = $transactions->clone()->where('user_id', $item->id)->debited()->sum('amount')
                +
                $transactions->clone()->where('selected_user_id', $item->id)->credited()->sum('amount');

            $credited= $transactions->clone()->where('selected_user_id', $item->id)->debited()->sum('amount')
                +
                $transactions->clone()->where('user_id', $item->id)->credited()->sum('amount');

            $adjustment = $credited - $debited;

            if ($adjustment < 0)
            {
                $total_mfs++; // when amount received is greater than amount given
            } else {
                $dues += $adjustment;
            }
        }

        foreach ($badges as $key => $badge)
        {
            $user_wise_badge2[$key]['badge'] = $badge;

            foreach ($users as $index => $user)
            {
                $user_wise_badge2[$key]['user_data'][$index]['user'] = $user;
                $user_wise_badge2[$key]['user_data'][$index]['count'] = $user_badge->clone()
                    ->where('user_id', $user->id)
                    ->where('badge_id', $badge->id)
                    ->count();
            }
        }

        usort($current_user_points, function ($a,$b) {
            return $b['earned_points'] - $a['earned_points'];
        });

        $event_categories = EventCategory::withCount(['events' => function ($query) {
            $query->whereIn('event_status_id', [2,3,4]);
        }])->get();

        $expense_categories_lifetime = $expense_categories->clone()
            ->selectRaw('
                COALESCE(SUM(CASE
                    WHEN events.event_status_id = 4 THEN expense_payers.amount
                    ELSE 0 END
                ), 0) AS expense_amount,
                expense_categories.id,expense_categories.name,expense_categories.icon_url
            ')
            ->groupBy('expense_categories.id','expense_categories.name','expense_categories.icon_url')
            ->get();

        $expense_categories_monthly = $expense_categories->clone()
            ->selectRaw('
                COALESCE(SUM(CASE
                    WHEN events.event_status_id = 4 AND
                    expense_payers.created_at BETWEEN "'. $start_date .'" AND "'. $end_date .'" THEN expense_payers.amount
                    ELSE 0 END
                ), 0) AS expense_amount,
                expense_categories.id,expense_categories.name,expense_categories.icon_url
            ')
            ->groupBy('expense_categories.id','expense_categories.name','expense_categories.icon_url')
            ->get();

        $expense_categories_weekly = $expense_categories->clone()
            ->selectRaw('
                COALESCE(SUM(CASE
                    WHEN events.event_status_id = 4 AND
                    expense_payers.created_at BETWEEN "'. $start_date_week .'" AND "'. $end_date .'" THEN expense_payers.amount
                    ELSE 0 END
                ), 0) AS expense_amount,
                expense_categories.id,expense_categories.name,expense_categories.icon_url
            ')
            ->groupBy('expense_categories.id','expense_categories.name','expense_categories.icon_url')
            ->get();

        return array(
            'total_users'                 => $total_users,
            'active_users'                => $active_users,
            'event_lifetime'              => $event_count_lifetime,
            'event_30days'                => $event_count_30days,
            'event_categories'            => $event_categories,
            'total_mfs'                   => $total_mfs,
            'total_dues'                  => $dues,
            'transaction_lifetime_count'  => $transaction_count,
            'transaction_lifetime_amount' => $transaction_amount,
            'transaction_30days_count'    => $transaction_count_30days,
            'transaction_30days_amount'   => $transaction_amount_30days,
            'current_month_badges'        => $monthly_user_badges,
            'user_badges_1'               => $user_wise_badge1,
            'user_badges_2'               => $user_wise_badge2,
            'user_points'                 => $current_user_points,
            'expense_categories_lifetime' => $expense_categories_lifetime,
            'expense_categories_monthly'  => $expense_categories_monthly,
            'expense_categories_weekly'   => $expense_categories_weekly
        );
    }

    public function notifyRandomly(Request $request)
    {
        $users = User::status()->get();

        foreach ($users as $user)
        {
            $msg = 'Hey ' . $user->name . ' 👋 ' . $request->message;

            $user->notify(new UserNotification(
                '',
                $msg,
                'Life++',
                null
            ));
        }
    }

    public function test(){
        $points = new UserPoint();
        // calculating total user point available in database
        $totalPoint = $points->clone()->count();

        $end = Carbon::now('Asia/Dhaka');
        // define start date from the time when the last point data was calculated
        $start = $totalPoint == 0 ? Carbon::now('Asia/Dhaka')->subCenturies(1)->format('Y-m-d H:i') :
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
            foreach ($users as $user) {
                $weight = 0;

                // login count
                $loginCount = $user->accessLogs()->whereBetween('logged_in_at', [$start, $end])->count();

                // added extravaganza image count
                $addedImageCount = $user->addedImages()->whereBetween('created_at', [$start, $end])->count();

                $weight += $loginCount * BadgeWeight::getValue(BadgeWeight::USER_LOGIN_COUNT);

                $weight += $addedImageCount * BadgeWeight::getValue(BadgeWeight::USER_LOGIN_COUNT);

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
