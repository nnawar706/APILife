<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserLoanCreateRequest;
use App\Http\Services\UserLoanService;
use Illuminate\Http\Request;
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
        $data = $this->service->getAll(auth()->user()->id === 1);

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

    public function delete($id)
    {
        $response = $this->service->removeLoan($id);

        if(!$response)
        {
            return response()->json(['status' => true], Response::HTTP_OK);
        }

        return response()->json([
            'status' => false,
            'error'  => $response
        ], Response::HTTP_FORBIDDEN);
    }

    public function updateStatus(Request $request, $id)
    {
        $validate = \Validator::make($request->all(), [
            'status' => 'required|in:1,2'
        ],[
            'status.in' => 'Invalid status detected.'
        ]);

        if ($validate->fails())
        {
            return response()->json([
                'status' => false,
                'error'  => $validate->errors()->first()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $response = $this->service->changeStatus($request->status, $id);

        if(!$response)
        {
            return response()->json(['status' => true], Response::HTTP_OK);
        }

        return response()->json([
            'status' => false,
            'error'  => $response
        ], Response::HTTP_FORBIDDEN);
    }
}
