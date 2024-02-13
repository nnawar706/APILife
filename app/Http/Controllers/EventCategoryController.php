<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use App\Http\Services\EventCategoryService;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\EventCategoryCreateRequest;

class EventCategoryController extends Controller
{
    private $service;

    public function __construct(EventCategoryService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        // cache the category list until anything gets updated
        $data = Cache::remember('event_categories', 24*60*60*60, function () {
            return $this->service->getAll();
        });

        // if no data found, status = 204, else 200
        return response()->json([
            'status' => true,
            'data'   => $data
        ], count($data) === 0 ? Response::HTTP_NO_CONTENT : Response::HTTP_OK);
    }

    public function create(EventCategoryCreateRequest $request)
    {
        // check icon availability here because request validation file is shared
        // icon is required while creating
        if (!$request->file('icon'))
        {
            return response()->json([
                'status' => false,
                'error'  => 'Icon field is required.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $this->service->storeNewCategory($request);

        return response()->json([
            'status' => true
        ], Response::HTTP_CREATED);
    }

    public function update(EventCategoryCreateRequest $request, $id)
    {
        // status = true, when category has been modified, else false
        $status = $this->service->updateInfo($request, $id);

        // status = 304, if not modified, else 200
        return response()->json([
            'status' => $status,
        ], $status ? Response::HTTP_OK : Response::HTTP_NOT_MODIFIED);
    }

    public function updateStatus($id)
    {
        $this->service->updateCategoryStatus($id);

        return response()->json([
            'status' => true,
        ], Response::HTTP_OK);
    }

    public function delete($id)
    {
        // it will return false if categories cannot be deleted (events exist)
        if ($this->service->removeCategory($id))
        {
            return response()->json([
                'status' => true,
            ], Response::HTTP_OK);
        }

        return response()->json([
            'status' => false,
            'error'  => 'Unable to delete event category since it contains data.'
        ], Response::HTTP_BAD_REQUEST);
    }
}
