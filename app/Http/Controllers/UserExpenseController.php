<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserExpenseCreateRequest;
use App\Http\Services\UserExpenseService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class UserExpenseController extends Controller
{
    private $service;

    public function __construct(UserExpenseService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $data = Cache::remember('user_expense' . auth()->user()->id, 24*60*60*60, function () {
            return $this->service->getAll();
        });

        return response()->json([
            'status' => true,
            'data'   => $data
        ], count($data) == 0 ? 204 : 200);
    }

    public function create(UserExpenseCreateRequest $request)
    {
        $this->service->storeExpense($request);

        return response()->json([
            'status' => true,
        ], Response::HTTP_CREATED);
    }

    public function update(UserExpenseCreateRequest $request, $id)
    {
        $response = $this->service->updateExpense($request, $id);

        if ($response)
        {
            return response()->json([
                'status' => false,
                'error'  => $response
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'status' => true,
        ], Response::HTTP_OK);
    }

    public function delete($id)
    {
        $response = $this->service->removeExpense($id);

        if ($response)
        {
            return response()->json([
                'status' => false,
                'error'  => $response
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'status' => true,
        ], Response::HTTP_OK);
    }
}
