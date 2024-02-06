<?php

namespace App\Http\Controllers;

use App\Http\Services\SystemService;
use App\Models\User;
use App\Notifications\UserNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

    public function budgetSummary()
    {
        $data = $this->service->getAuthBudgetSummary();

        return response()->json([
            'status' => true,
            'data'   => $data
        ], Response::HTTP_OK);
    }

    public function notifyRandomly(Request $request)
    {
        $users = User::whereIn('id', $request->users)->get();

        foreach ($users as $user)
        {
            $msg = 'Hey ' . $user->name . ' 👋 ' . $request->message;

            $user->notify(new UserNotification(
                '',
                $msg,
                null,
                'Life++',
                null
            ));
        }
    }

    public function test(Request $request)
    {
//        return response()->json([
//            'threshold' => Cache::get('threshold')
//        ]);

        $startDate = Carbon::now()->startOfMonth()->subMonthsNoOverflow()->format('Y-m-d H:i:s');
        $endDate   = Carbon::now()->subMonthsNoOverflow()->endOfMonth()->format('Y-m-d H:i:s');

        return response()->json([
            'start' => $startDate,
            'end'   => $endDate
        ]);
    }
}
