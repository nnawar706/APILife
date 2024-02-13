<?php

namespace App\Http\Controllers;

use App\Models\UserStory;
use Illuminate\Http\Request;
use App\Http\Services\AuthService;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Cache;
use App\Http\Services\UserStoryService;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\UserPasswordUpdateRequest;

class AuthController extends Controller
{
    private $service;

    public function __construct(AuthService $service)
    {
        $this->service = $service;
    }

    public function login(LoginRequest $request)
    {
        $response = $this->service->login($request);

        if ($response)
        {
            return response()->json([
                'status' => true,
                'data'   => $response
            ], Response::HTTP_OK);
        }

        return response()->json([
            'status' => false,
            'error'  => 'No authorized account found with given credentials.'
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function authenticatePusher()
    {
        try {
            $beamsClient = getBeamsClient();

            $beamsToken = $beamsClient->generateToken(strval(auth()->user()->id));

            return response()->json($beamsToken);
        }
        catch (\Throwable $th)
        {
            return response()->json([
                'status' => false,
                'error'  => $th->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function refreshUser()
    {
        $data = $this->service->refreshUser();

        return response()->json([
            'status' => true,
            'data'   => $data
        ], Response::HTTP_OK);
    }

    public function authProfile()
    {
        $data = Cache::remember('user_profile'.auth()->user()->id, 24*60*60*60, function () {
            return $this->service->getAuthUserProfile();
        });

        return response()->json([
            'status' => true,
            'data'   => $data,
        ], Response::HTTP_OK);
    }

    public function changePassword(UserPasswordUpdateRequest $request)
    {
        $this->service->updateUserPassword($request);

        return response()->json(['status' => true], Response::HTTP_OK);
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['status' => true], Response::HTTP_OK);
    }

    public function getNotifications()
    {
        $data = $this->service->getAuthNotifications();

        return response()->json([
            'status'     => true,
            'total_data' => $data->total(),
            'data'       => $data->items()
        ], $data->isEmpty() ? Response::HTTP_NO_CONTENT : Response::HTTP_OK);
    }

    public function readNotifications(Request $request)
    {
        $this->service->notificationMarkAsRead($request);

        return response()->json(['status' => true], Response::HTTP_OK);
    }

    public function getNotificationCount()
    {
        $notificationCount = auth()->user()->unreadNotifications->count();

        $storyCount = (new UserStoryService(new UserStory()))->getAuthUnseenStoryCount();

        return response()->json([
            'status' => true,
            'data'   => array(
                'unread_notification_count' => $notificationCount,
                'unseen_story_count'        => $storyCount
            )
        ], Response::HTTP_OK);
    }
}
