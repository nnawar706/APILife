<?php

namespace App\Http\Controllers;

use Spatie\Activitylog\Models\Activity;

class SystemController extends Controller
{
    public function index()
    {
        $data = Activity::with('causer','subject')->latest()->paginate(15);

        return response()->json([
            'status'     => true,
            'total_data' => $data->total(),
            'data'       => $data->items()
        ]);
    }
}
