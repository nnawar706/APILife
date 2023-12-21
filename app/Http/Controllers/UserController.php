<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserCreateRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Services\UserService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    private $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $data = Cache::remember('users', 24*60*60*60, function () {
            return $this->service->getAll();
        });

        return response()->json([
            'status' => true,
            'data'   => $data
        ], count($data) === 0 ? Response::HTTP_NO_CONTENT : Response::HTTP_OK);
    }

    public function read($id)
    {
        $data = Cache::remember('user'.$id, 24*60*60*60, function () use ($id) {
            return $this->service->getUserData($id);
        });

        return response()->json([
            'status' => true,
            'data'   => $data
        ], $data ? Response::HTTP_OK : Response::HTTP_NO_CONTENT);
    }

    public function create(UserCreateRequest $request)
    {
        $this->service->storeNewUser($request);

        return response()->json([
            'status' => true
        ], Response::HTTP_CREATED);
    }

    public function update(UserUpdateRequest $request)
    {
        $status = $this->service->updateInfo($request);

        return response()->json([
            'status' => $status
        ], $status ? Response::HTTP_OK : Response::HTTP_NOT_MODIFIED);
    }

    public function updateStatus($id)
    {
        if ($id == auth()->user()->id)
        {
            return response()->json([
                'status' => false,
                'error'  => 'Unable to deactivate user account.'
            ], Response::HTTP_FORBIDDEN);
        }

        $this->service->updateUserStatus($id);

        return response()->json([
            'status' => true,
        ], Response::HTTP_OK);
    }

    public function delete($id)
    {
        if ($this->service->removeUser($id))
        {
            return response()->json(['status' => true], Response::HTTP_OK);
        }

        return response()->json([
            'status' => false,
            'error'  => 'Unable to delete user since it contains data.'
        ], Response::HTTP_BAD_REQUEST);
    }
}
