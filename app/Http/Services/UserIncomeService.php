<?php

namespace App\Http\Services;

use App\Models\UserIncome;
use Illuminate\Http\Request;

class UserIncomeService
{
    private $model;

    public function __construct(UserIncome $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        return $this->model->where('user_id', auth()->user()->id)->orderByDesc('id')->get();
    }

    public function storeIncome(Request $request): void
    {
        $this->model->create([
            'user_id' => auth()->user()->id,
            'title'   => $request->title,
            'amount'  => $request->amount,
            'notes'   => $request->notes,
            'created_at' => $request->created_at
        ]);
    }

    public function updateIncome(Request $request, $id)
    {
        $income = $this->model->findOrFail($id);

        if ($income->user_id != auth()->user()->id)
        {
            return 'You are not allowed to update this data.';
        }

        $income->update([
            'title'   => $request->title,
            'amount'  => $request->amount,
            'notes'   => $request->notes,
            'created_at' => $request->created_at
        ]);

        return null;
    }

    public function removeIncome($id)
    {
        $income = $this->model->findOrFail($id);

        if ($income->user_id != auth()->user()->id)
        {
            return 'You are not allowed to update this data.';
        }

        $income->delete();

        return null;
    }

}
