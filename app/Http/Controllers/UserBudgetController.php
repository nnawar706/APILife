<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserBudgetCreateTargetRequest;
use App\Models\UserBudget;
use Symfony\Component\HttpFoundation\Response;

class UserBudgetController extends Controller
{
    public function setBudget(UserBudgetCreateTargetRequest $request)
    {
        $budget = UserBudget::updateOrCreate([
            'user_id' => auth()->user()->id
        ], [
            'target_saving' => $request->target_saving
        ]);

        return response()->json([
            'status' => true
        ], $budget->wasChanged() ? Response::HTTP_CREATED : Response::HTTP_NOT_MODIFIED);
    }
}
