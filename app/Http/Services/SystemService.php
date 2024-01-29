<?php

namespace App\Http\Services;

use App\Models\Badge;
use App\Models\EventCategory;
use App\Models\EventStatus;
use App\Models\ExpenseCategory;
use App\Models\ExpensePayer;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserBudget;
use App\Models\UserExpense;
use App\Models\UserIncome;
use App\Models\UserLoan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Activity;

class SystemService
{
    public function getDashboardData()
    {
        // variables
        $total_mfs           = 0;
        $dues                = 0;
        $monthly_user_badges = [];
        $user_wise_badge1    = [];
        $user_wise_badge2    = [];
        $current_user_points = [];
        $chart_data          = [];

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

        for ($i=0;$i<30;$i++)
        {
            $curDate = $end_date->clone()->subDay($i + 1);

            $expenseTotal = $expense_categories->clone()
                ->whereDate('expense_payers.created_at', $curDate)
                ->sum('expense_payers.amount');

            $loanTotal = $transactions->clone()->whereDate('created_at', $curDate)->sum('amount');

            $chart_data[$i]['date']     = $curDate->format('d M, y');
            $chart_data[$i]['expenses'] = round($expenseTotal, 2);
            $chart_data[$i]['loans']    = round($loanTotal, 2);
        }

        usort($current_user_points, function ($a,$b) {
            return $b['earned_points'] - $a['earned_points'];
        });

        $event_categories = EventCategory::withCount(['events' => function ($query) {
            $query->whereNot('event_status_id', 5);
        }])->get();

        $expense_categories_lifetime = $expense_categories->clone()
            ->selectRaw('
                COALESCE(SUM(CASE
                    WHEN events.event_status_id != 5 THEN expense_payers.amount
                    ELSE 0 END
                ), 0) AS expense_amount,
                expense_categories.id,expense_categories.name,expense_categories.icon_url
            ')
            ->groupBy('expense_categories.id','expense_categories.name','expense_categories.icon_url')
            ->get();

        $expense_categories_monthly = $expense_categories->clone()
            ->selectRaw('
                COALESCE(SUM(CASE
                    WHEN events.event_status_id != 5 AND
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
                    WHEN events.event_status_id != 5 AND
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
            'expense_categories_weekly'   => $expense_categories_weekly,
            'expense_chart'               => $chart_data,
        );
    }

    public function refreshSystem(): void
    {
        Artisan::call('cache:clear');
        Artisan::call('optimize');
        Artisan::call('optimize:clear');
        Artisan::call('config:clear');
    }

    public function getActivityLogs()
    {
        return Activity::with('causer','subject')->latest()->paginate(15);
    }

    public function getAuthBudgetSummary()
    {
        $income = new UserIncome();
        $expense = new UserExpense();

        $budgetTarget = UserBudget::where('user_id', auth()->user()->id)->first();

        $end_date = Carbon::now('Asia/Dhaka');
        $start_date_week = $end_date->clone()->subWeeks(1);
        $start_date_month = $end_date->clone()->subMonths(1);

        $totalIncome  = $income->clone()->sum('amount');
        $totalExpense = $expense->clone()->sum('amount');
        $totalSaving  = $totalIncome - $totalExpense;

        $lastWeekIncome  = $income->clone()->whereBetween('created_at', [$start_date_week, $end_date])->sum('amount');
        $lastWeekExpense = $expense->clone()->whereBetween('created_at', [$start_date_week, $end_date])->sum('amount');

        $lastMonthIncome  = $income->clone()->whereBetween('created_at', [$start_date_month, $end_date])->sum('amount');
        $lastMonthExpense = $expense->clone()->whereBetween('created_at', [$start_date_month, $end_date])->sum('amount');

        return array(
            'total_income'   => $totalIncome,
            'total_expense'  => $totalExpense,
            'total_saving'   => $totalSaving,
            'target_saving'  => $budgetTarget ? $budgetTarget->target_saving : null,
            'last_week'      => array(
                'income'  => $lastWeekIncome,
                'expense' => $lastWeekExpense
            ),
            'last_month'     => array(
                'income'  => $lastMonthIncome,
                'expense' => $lastMonthExpense
            ),
        );
    }

}
