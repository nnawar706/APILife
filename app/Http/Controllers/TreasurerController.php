<?php

namespace App\Http\Controllers;

use App\Http\Requests\TreasurerCreateRequest;
use App\Http\Services\TreasurerService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TreasurerController extends Controller
{
    private $service;

    public function __construct(TreasurerService $service)
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

    public function create(TreasurerCreateRequest $request)
    {
        $response = $this->service->storeNewTreasurer($request);

        if (!$response)
        {
            return response()->json(['status' => true], Response::HTTP_CREATED);
        }

        return response()->json([
            'status' => false,
            'error'  => $response
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
