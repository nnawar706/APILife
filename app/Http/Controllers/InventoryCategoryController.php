<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryCategoryCreateRequest;
use App\Http\Services\InventoryCategoryService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class InventoryCategoryController extends Controller
{
    private $service;

    public function __construct(InventoryCategoryService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $data = Cache::remember('inventory_categories', 24*60*60*60, function () {
            return $this->service->getAll();
        });

        return response()->json([
            'status' => true,
            'data'   => $data
        ], count($data) === 0 ? Response::HTTP_NO_CONTENT : Response::HTTP_OK);
    }

    public function create(InventoryCategoryCreateRequest $request)
    {
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

    public function update(InventoryCategoryCreateRequest $request, $id)
    {
        $status = $this->service->updateInfo($request, $id);

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
        if ($this->service->removeCategory($id))
        {
            return response()->json([
                'status' => true,
            ], Response::HTTP_OK);
        }

        return response()->json([
            'status' => false,
            'error'  => 'Unable to delete inventory category since it contains data.'
        ], Response::HTTP_BAD_REQUEST);
    }
}
