<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\UserPasswordUpdateRequest;
use App\Http\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

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
                'data' => $response
            ], Response::HTTP_OK);
        }

        return response()->json([
            'status' => false,
            'error' => 'No authorized account found with given credentials.'
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function authProfile()
    {
        $data = Cache::remember('auth_user'.auth()->user()->id, 24*60*60, function () {
            return $this->service->getAuthUserProfile();
        });

        return response()->json([
            'status' => true,
            'data' => $data
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
}
