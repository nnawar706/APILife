<?php

namespace App\Http\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function login(Request $request)
    {
        $credentials = $request->only('phone_no', 'password');

        // if token is available for the requested phone no and password, create log entry and return token
        if ($token = $this->guard()->attempt($credentials))
        {
            auth()->user()->accessLogs()->firstOrCreate([
                'logged_in_at' => Carbon::today('Asia/Dhaka')->format('Y-m-d'),
            ]);

            return $this->respondWithToken($token);
        }

        return null;
    }

    private function guard()
    {
        return Auth::guard();
    }

    private function respondWithToken($token)
    {
        return array(
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60
        );
    }

    public function refreshUser()
    {
        return $this->respondWithToken($this->guard()->refresh());
    }

    public function getAuthUserProfile()
    {
        return User::with('designation')
            ->find(auth()->user()->id);
    }

    public function updateUserPassword(Request $request): void
    {
        // update auth user password
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
        // return paginated notifications after updating send status
        auth()->user()->unreadNotifications()->update(['send_status' => 1]);

        return auth()->user()->notifications()->latest()->paginate(15);
    }
}
