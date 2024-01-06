<?php

namespace App\Http\Controllers;

use App\Enums\BadgeWeight;
use App\Models\Badge;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventStatus;
use App\Models\ExpenseBearer;
use App\Models\ExpenseCategory;
use App\Models\ExpensePayer;
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
        $user_wise_badge     = [];
        $current_user_points = [];

        // dates
        $end_date        = Carbon::now('Asia/Dhaka');
        $start_date      = Carbon::now('Asia/Dhaka')->subMonths(1);
        $start_date_week = Carbon::now('Asia/Dhaka')->subWeeks(1);

        // model variables
        $eventStatus        = EventStatus::orderBy('id');
        $user               = User::status();
        $user_badge         = new UserBadge();
        $event_count_lifetime = $eventStatus->clone()->withCount('events')->get();
        $transactions       = UserLoan::accepted();
        $badge              = Badge::orderBy('id')->get();
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
        $active_users = $user->clone()->whereHas('events', function ($q) use ($start_date, $end_date) {
            return $q->whereNotIn('event_status_id', [1,5]) // 1: ongoing, 5: canceled
                ->whereBetween('created_at', [$start_date, $end_date]);
        })->count();

        $transaction_count = $transactions->clone()->count();
        $transaction_amount = $transactions->clone()->sum('amount');

        $transaction_count_30days = $transactions->clone()
            ->whereBetween('created_at', [$start_date, $end_date])->count();
        $transaction_amount_30days = $transactions->clone()
            ->whereBetween('created_at', [$start_date, $end_date])->sum('amount');

        foreach ($user->clone()->get() as $key => $item)
        {
            $user_wise_badge[$key]['user'] = $item;

            foreach ($badge as $i => $val)
            {
                $user_wise_badge[$key]['badges'][$i]['badge'] = $val;
                $user_wise_badge[$key]['badges'][$i]['count'] = $val->userBadge()->where('user_id', $item->id)->count();
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
                $total_mfs++;
            } else {
                $dues += $adjustment;
            }
        }

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
            'user_badges'                 => $user_wise_badge,
            'user_points'                 => $current_user_points,
            'expense_categories_lifetime' => $expense_categories_lifetime,
            'expense_categories_monthly'  => $expense_categories_monthly,
            'expense_categories_weekly'   => $expense_categories_weekly
        );
    }

    public function calculatePoint()
    {
        $start = Carbon::now('Asia/Dhaka')->subMonths(1);
        $end   = Carbon::now('Asia/Dhaka');

        $badge = new UserBadge();

        if ($badge->clone()
            ->whereMonth('created_at', Carbon::now()->format('n'))
            ->doesntExist())
        {
            $users = User::status()->get();

            $data = [];

            foreach ($users as $key => $user)
            {
                $data[$key]['user_id'] = $user->id;
                $data[$key]['weight'] = intval($user->points()->whereBetween('created_at', [$start, $end])->sum('point'));
            }

            $weights = array_map(function ($item) {
                return $item['weight'];
            }, $data);

            $thresholds = getThresholds(max($weights), min($weights));

            DB::beginTransaction();

            try {
                for ($i = 0; $i <= 4; $i++) {
                    $users = array_filter($data, function ($value) use ($i, $thresholds) {
                        if ($i == 0) {
                            return $value['weight'] < $thresholds[$i];
                        } else if ($i == 4) {
                            return $value['weight'] >= $thresholds[$i - 1];
                        }
                        return $value['weight'] >= $thresholds[$i - 1] && $value['weight'] < $thresholds[$i];
                    });

                    if (count($users) != 0) {
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

    public function test()
    {
//        $users = User::status()->get();
//
//        foreach ($users as $item)
//        {
//            $birthdate = $item->birthday . '-' . Carbon::today('Asia/Dhaka')->format('Y');
//
//            if (Carbon::today('Asia/Dhaka')->format('d-m-Y') == Carbon::parse($birthdate)->format('d-m-Y'))
//            {
//                $item->notify(new UserNotification(
//                    '',
//                    'Happy Birthday! 🎉 🎊 May all your dreams turn into reality.',
//                    null,
//                    null
//                ));
//
//                return;
//            }
//
//            if (Carbon::today('Asia/Dhaka')->format('d-m-Y') == Carbon::parse($birthdate)->subWeek(1)->format('d-m-Y'))
//            {
//
//            }
//        }
    }
}
