<?php

namespace App\Http\Controllers;

use App\Enums\UserPointWeight;
use App\Http\Services\SystemService;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\ExpenseBearer;
use App\Models\ExpensePayer;
use App\Models\User;
use App\Models\UserLoan;
use App\Models\UserPoint;
use App\Notifications\UserNotification;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;
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
//        $thresholds = getThresholds(1688, 235);
//
//        return response()->json([
//            'threshold' => $thresholds
//        ]);

        $image = $request->file('file');

        $img = Image::make($image);

        // generate a random image name
        $image_name = $request->name . '.' . $image->getClientOriginalExtension();

//        if ($compress)
//        {
            // resize image to 1920x1080 with keeping the aspect ratio same
            $compressedImage = $img->orientate()
                ->resize(1920, 1080, function ($constraint) {
                    $constraint->aspectRatio();
                });

            // save the compressed image to server
            $compressedImage->save(public_path('/images/user_stories/' . $image_name));
//        } else {
//            // save the actual image to server
//            $image->move(public_path($path), $image_name);
//        }
    }
}
