<?php

namespace App\Http\Services;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function login(Request $request)
    {
        $credentials = $request->only('phone_no', 'password');

        // check if token is available for the requested phone no and password
        if ($token = $this->guard()->attempt($credentials))
        {
            // create log entry once a day
            auth()->user()->accessLogs()->firstOrCreate([
                'logged_in_at' => Carbon::today('Asia/Dhaka')->format('Y-m-d'),
            ]);

            // return token
            return $this->respondWithToken($token);
        }

        // if authentication failed, return null
        return null;
    }

    private function guard()
    {
        return Auth::guard();
    }

    private function respondWithToken($token)
    {
        // return token with expiration time
        return array(
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60
        );
    }

    public function refreshUser()
    {
        // refresh current token and provide new
        return $this->respondWithToken($this->guard()->refresh());
    }

    public function getAuthUserProfile()
    {
        // return auth profile with designation and current month badge
        return User::with('designation')
            ->with(['userBadge' => function($q) {
                return $q->with('badge')->whereMonth('created_at', Carbon::now('Asia/Dhaka')->format('n'));
            }])
            ->find(auth()->user()->id);
    }

    public function updateUserPassword(Request $request): void
    {
        // update auth user password quietly so that no update event is triggered
        auth()->user()->updateQuietly([
            'password' => $request->password
        ]);
    }

    public function notificationMarkAsRead(Request $request): void
    {
        // mark auth unread notifications as read
        auth()->user()->unreadNotifications
            // if id is present, read that notification, else read all unread notifications
            ->when($request->input('id'), function ($q) use ($request) {
                return $q->where('id', $request->input('id'));
            })
            ->markAsRead();
    }

    public function getAuthNotifications()
    {
        // updating send status
        auth()->user()->unreadNotifications()->update(['send_status' => 1]);
        // return paginated notifications
        return auth()->user()->notifications()->latest()->paginate(15);
    }
}
