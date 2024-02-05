<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserCreateRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    private $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:true'
        ]);

        if ($validator->fails())
        {
            return response()->json([
                'status' => false,
                'error'  => $validator->errors()->first()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = Cache::remember('users'.$request->status, 24*60*60*30, function () use ($request) {
            return $this->service->getAll($request);
        });

        return response()->json([
            'status' => true,
            'data'   => $data
        ], count($data) === 0 ? Response::HTTP_NO_CONTENT : Response::HTTP_OK);
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
