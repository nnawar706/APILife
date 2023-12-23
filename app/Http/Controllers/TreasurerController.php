<?php

namespace App\Http\Controllers;

use App\Http\Requests\TreasurerCreateRequest;
use App\Http\Services\TreasurerService;
use App\Models\Treasurer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TreasurerController extends Controller
{
    private $service;

    public function __construct(TreasurerService $service)
    {
        $this->service = $service;
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

    public function tester()
    {
        $treasurer = Treasurer::find(1);

        $events = $treasurer->events();

        return $events;
    }
}
