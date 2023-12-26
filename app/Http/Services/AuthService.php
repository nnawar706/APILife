<?php

namespace App\Http\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthService
{

    public function login(Request $request)
    {
        $credentials = $request->only('phone_no', 'password');

        if ($token = $this->guard()->attempt($credentials))
        {
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
        return (new UserService(new User()))->getUserData(auth()->user()->id);
    }

    public function updateUserPassword(Request $request)
    {
        auth()->user()->update([
            'password' => $request->password
        ]);
    }

    public function notificationMarkAsRead(Request $request): void
    {
        auth()->user()->unreadNotifications
            ->when($request->input('id'), function ($q) use ($request) {
                return $q->where('id', $request->input('id'));
            })
            ->markAsRead();
    }

    public function getAuthNotifications()
    {
        return auth()->user()->notifications()->latest()->paginate(50);
    }
}
