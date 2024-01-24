<?php

namespace App\Http\Controllers;

use App\Enums\BadgeWeight;
use App\Http\Services\EventService;
use App\Http\Services\SystemService;
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
    private $service;

    public function __construct(SystemService $service)
    {
        $this->service = $service;
    }

    public function activities()
    {
        $data = $this->service->getActivityLogs();

        return response()->json([
            'status'     => true,
            'total_data' => $data->total(),
            'data'       => $data->items()
        ], $data->isEmpty() ? Response::HTTP_NO_CONTENT : Response::HTTP_OK);
    }

    public function refresh()
    {
        $this->service->refreshSystem();

        return response()->json(['status' => true], 205);
    }

    public function dashboardData()
    {
        $data = $this->service->getDashboardData();

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
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

    public function test(Request $request) {
        $user = User::find($request->user_id);

        saveImage($request->image, '/images/users/', $user, 'photo_url');
    }
}
