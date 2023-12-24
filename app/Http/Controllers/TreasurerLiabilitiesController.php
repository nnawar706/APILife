<?php

namespace App\Http\Controllers;

use App\Jobs\TreasurerCompletion;
use App\Models\TreasurerLiability;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TreasurerLiabilitiesController extends Controller
{
    private $model;

    public function __construct(TreasurerLiability $model)
    {
        $this->model = $model;
    }

    public function updateStatus($tl_id)
    {
        $liability = $this->model->findOrFail($tl_id);

        if ($liability->treasurer->user_id != auth()->user()->id)
        {
            return response()->json([
                'status' => false,
                'error'  => 'You are not authorized to perform this action.'
            ], Response::HTTP_FORBIDDEN);
        }

        $liability->update(['status' => 1]);

        if ($liability->wasChanged())
        {
            dispatch(new TreasurerCompletion($liability));
        }

        return response()->json(['status' => true],
            $liability->wasChanged() ? Response::HTTP_OK :
                Response::HTTP_NOT_MODIFIED);
    }
}
