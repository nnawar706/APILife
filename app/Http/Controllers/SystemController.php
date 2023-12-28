<?php

namespace App\Http\Controllers;

use App\Enums\BadgeWeight;
use App\Models\Event;
use App\Models\EventStatus;
use App\Models\User;
use App\Models\UserBadge;
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
            ->where('created_at', [$start_date, $end_date])->get();

        $total_users = User::status()->count();
        $active_users = User::status()->whereHas('events', function ($q) use ($start_date, $end_date) {
            return $q->whereNotIn('event_status_id', [1,3])
                ->whereBetween('created_at', [$start_date, $end_date]);
        })->count();

        return array(
            'total_users'    => $total_users,
            'active_users'   => $active_users,
            'event_lifetime' => $event_count_lifetime,
            'event_30days'   => $event_count_30days
        );
    }
}
