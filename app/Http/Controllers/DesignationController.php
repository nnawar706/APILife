<?php

namespace App\Http\Controllers;

use App\Models\Designation;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class DesignationController extends Controller
{
    private $model;

    public function __construct(Designation $model)
    {
        $this->model = $model;
    }

    public function index()
    {
        $data = Cache::rememberForever('designations', function () {
            return $this->model->latest()->get();
        });

        return response()->json([
            'status' => true,
            'data'   => $data
        ], Response::HTTP_OK);
    }
}
