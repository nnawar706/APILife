<?php

namespace App\Http\Controllers;

use App\Enums\BadgeWeight;
use App\Models\Event;
use App\Models\EventStatus;
use App\Models\TreasurerLiability;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserLoan;
use App\Notifications\UserNotification;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
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
        clearCache();
        $data = Cache::remember('dashboard', 60*60*2, function () {
            return $this->getDashboardData();
        });

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    private function getDashboardData()
    {
        $end_date   = Carbon::now('Asia/Dhaka');
        $start_date = Carbon::now('Asia/Dhaka')->subMonths(1);
        $user_badge = new UserBadge();
        $event      = new Event();
        $user       = User::status();
        $transactions = UserLoan::accepted();

//        $monthly_user_badges = [];
//
//        for ($i=0; $i < 12; $i++)
//        {
//            $date      = Carbon::now()->subMonths($i);
//
//            $monthly_user_badges[$i]['month'] = Carbon::parse($date)->format('M');
//            $monthly_user_badges[$i]['user_data'] = $user_badge->clone()
//                ->whereMonth('created_at', Carbon::parse($date)->format('n'))
//                ->with('badge','user')->get();
//        }
//
//        return array(
//            'completed_events' => $event->clone()->where('event_status_id', '=', 4)->count(),
//            'ongoing_events'   => $event->clone()->where('event_status_id', '=', 1)->count(),
//            'user_badges'      => $monthly_user_badges
//        );

        $event_count_lifetime = EventStatus::orderBy('id')->withCount('events')->get();

        $event_count_30days   = $event
            ->leftJoin('event_statuses', 'event_statuses.id','=','events.event_status_id')
            ->selectRaw('event_status_id as id,event_statuses.name as name,count(events.id) as events_count')
            ->groupBy('event_status_id','name')
            ->whereBetween('created_at', [$start_date, $end_date])->get();

        $total_users = $user->clone()->count();
        $active_users = $user->clone()->whereHas('events', function ($q) use ($start_date, $end_date) {
            return $q->whereNotIn('event_status_id', [1,3])
                ->whereBetween('created_at', [$start_date, $end_date]);
        })->count();

        $total_mfs = $user->clone()->whereHas('userPayables', function ($q) {
            return $q->where('amount','>',0)->where('status',0);
        })->count();

        $dues = TreasurerLiability::where('amount','>',0)->where('status',0)->sum('amount');

        $transaction_count = $transactions->clone()->count();
        $transaction_amount = $transactions->clone()->sum('amount');
        return array(
            'total_users'    => $total_users,
            'active_users'   => $active_users,
            'event_lifetime' => $event_count_lifetime,
            'event_30days'   => $event_count_30days,
            'total_mfs'      => $total_mfs,
            'total_dues'     => $dues,
            'transaction_count' => $transaction_count,
            'transaction_amount' => $transaction_amount
        );
    }
}
