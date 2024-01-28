<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserIncomeCreateRequest;
use App\Http\Services\UserIncomeService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserIncomeController extends Controller
{
    private $service;

    public function __construct(UserIncomeService $service)
    {
        $this->service = $service;
    }

    public function index()
    {}

    public function create(UserIncomeCreateRequest $request)
    {
        $this->service->storeIncome($request);

        return response()->json([
            'status' => true,
        ], Response::HTTP_CREATED);
    }

    public function update(UserIncomeCreateRequest $request, $id)
    {
        $response = $this->service->updateIncome($request, $id);

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
        $response = $this->service->removeIncome($id);

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
