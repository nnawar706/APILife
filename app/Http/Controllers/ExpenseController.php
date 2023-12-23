<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseCreateRequest;
use App\Http\Requests\ExpenseUpdateRequest;
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

    public function update(ExpenseUpdateRequest $request, $id)
    {
        $response = $this->service->updateInfo($request, $id);

        if (is_bool($response))
        {
            return response()->json(['status' => true],
                $response ? Response::HTTP_OK : Response::HTTP_NOT_MODIFIED);
        }

        return response()->json([
            'status' => false,
            'error'  => $response
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function eventExpenseLog($event_id)
    {
        $data = Cache::remember('event_expense_log'.$event_id, 24*60*60*60, function () use ($event_id) {
            return $this->service->getExpenseLog($event_id);
        });

        return response()->json([
            'status' => true,
            'data'   => $data
        ], count($data) === 0 ? Response::HTTP_NO_CONTENT : Response::HTTP_OK);
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
