<?php

namespace App\Http\Controllers;

use App\Enums\BadgeWeight;
use App\Models\Event;
use App\Models\User;
use App\Models\UserBadge;
use App\Notifications\UserNotification;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
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
        $monthly_user_badges = DB::table('user_badges')->selectRaw(
            'user_id, MONTH(created_at) as month_id'
        )->groupBy('month_id','user_id')->get();

        return response()->json([
            'status' => true,
            'data'   => $monthly_user_badges
        ]);
    }
}
