<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseCreateRequest;
use App\Http\Services\ExpenseService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ExpenseController extends Controller
{
    private $service;

    public function __construct(ExpenseService $service)
    {
        $this->service = $service;
    }

    public function read($id)
    {
        $data = Cache::remember('expense_info'.$id, 24*60*60*60, function () use ($id) {
            return $this->service->getExpenseInfo($id);
        });

        return response()->json([
            'status' => true,
            'data'   => $data
        ], is_null($data) ? Response::HTTP_NO_CONTENT : Response::HTTP_OK);
    }

    public function create(ExpenseCreateRequest $request)
    {
        $response = $this->service->storeNewExpense($request);

        if (!$response)
        {
            return response()->json(['status' => true], Response::HTTP_CREATED);
        }

        return response()->json([
            'status' => false,
            'error'  => $response
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function update(ExpenseCreateRequest $request, $id)
    {
        $response = $this->service->updateInfo($request, $id);

        if (is_null($response))
        {
            Cache::forget('expense_info'.$id);

            return response()->json(['status' => true], Response::HTTP_OK);
        }

        return response()->json([
            'status' => false,
            'error'  => $response
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function delete($id)
    {
        if ($this->service->removeExpense($id))
        {
            return response()->json(['status' => true], Response::HTTP_OK);
        }

        return response()->json([
            'status' => false,
            'error'  => 'Unable to delete data when expense payers exist.'
        ], Response::HTTP_BAD_REQUEST);
    }
}
