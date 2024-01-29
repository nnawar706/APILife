<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserBudgetCreateTargetRequest;
use App\Models\UserBudget;
use Symfony\Component\HttpFoundation\Response;

class UserBudgetController extends Controller
{
    public function setBudget(UserBudgetCreateTargetRequest $request)
    {
        UserBudget::updateOrCreate([
            'user_id' => auth()->user()->id
        ], [
            'target_saving' => $request->target_saving
        ]);

        return response()->json([
            'status' => true
        ], Response::HTTP_CREATED);
    }
}
