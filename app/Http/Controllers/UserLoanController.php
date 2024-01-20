<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserLoanCreateRequest;
use App\Http\Requests\UserLoanStatusUpdateRequest;
use App\Http\Services\UserLoanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class UserLoanController extends Controller
{
    private $service;

    public function __construct(UserLoanService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $data = Cache::remember('user_loan'.auth()->user()->id, 24*60*60*60, function () {
            return $this->service->getAll();
        });

        return response()->json([
            'status' => true,
            'data'   => $data
        ], count($data) === 0 ? Response::HTTP_NO_CONTENT : Response::HTTP_OK);
    }

    public function create(UserLoanCreateRequest $request)
    {
        $this->service->storeNewLoan($request);

        return response()->json(['status' => true], Response::HTTP_CREATED);
    }

    public function updateStatus(UserLoanStatusUpdateRequest $request, $id)
    {
        $response = $this->service->changeStatus($request, $id);

        if(!$response)
        {
            return response()->json(['status' => true], Response::HTTP_OK);
        }

        return response()->json([
            'status' => false,
            'error'  => $response
        ], Response::HTTP_FORBIDDEN);
    }

    public function summary(Request $request)
    {
        if ($request->has('user_id') && auth()->user()->id != 1)
        {
            return response()->json([
                'status' => false,
                'error'  => 'You are not allowed to fetch the data.'
            ], Response::HTTP_FORBIDDEN);
        }

        $user_id = $request->user_id ?? auth()->user()->id;

        $data = Cache::remember('user_loans_summary'.$user_id, 24*60*60*3, function () use ($user_id) {
            return $this->service->getLoanSummary($user_id);
        });

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    public function delete($id)
    {
        $response = $this->service->remove($id);

        if (!$response)
        {
            return response()->json(['status' => true], Response::HTTP_OK);
        }
        return response()->json([
            'status' => true,
            'error'  => $response
        ], Response::HTTP_FORBIDDEN);
    }
}
