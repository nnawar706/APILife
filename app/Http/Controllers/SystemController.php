<?php

namespace App\Http\Controllers;

use App\Enums\BadgeWeight;
use App\Models\Event;
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
        $user_badge = new UserBadge();
        $event = new Event();

        $monthly_user_badges = [];

        for ($i=0; $i < 12; $i++)
        {
            $date      = Carbon::now()->subMonths($i);

            $monthly_user_badges[$i]['month'] = Carbon::parse($date)->format('M');
            $monthly_user_badges[$i]['user_data'] = $user_badge->clone()
                ->whereMonth('created_at', Carbon::parse($date)->format('n'))
                ->with('badge','user')->get();
        }

        return array(
            'completed_events' => $event->clone()->where('event_status_id', '=', 4)->count(),
            'ongoing_events'   => $event->clone()->where('event_status_id', '=', 1)->count(),
            'user_badges'      => $monthly_user_badges
        );
    }

    public function test()
    {
    }
}
