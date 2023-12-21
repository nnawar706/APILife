<?php

namespace App\Http\Controllers;

use App\Models\EventStatus;
use Illuminate\Support\Facades\Cache;

class EventStatusController extends Controller
{
    private $model;

    public function __construct(EventStatus $model)
    {
        $this->model = $model;
    }

    public function index()
    {
        $data = Cache::rememberForever('event_statuses', function () {
            return $this->model->orderBy('id')->get();
        });

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }
}
