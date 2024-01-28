<?php

namespace App\Http\Services;

use App\Models\UserExpense;
use Illuminate\Http\Request;

class UserExpenseService
{
    private $model;

    public function __construct(UserExpense $model)
    {
        $this->model = $model;
    }

    public function storeExpense(Request $request)
    {
        $this->model->create([
            'user_id' => auth()->user()->id,
            'expense_category_id' => $request->expense_category_id,
            'title'   => $request->title,
            'amount'  => $request->amount,
            'notes'   => $request->notes,
            'created_at' => $request->created_at
        ]);
    }

    public function updateExpense(Request $request, $id)
    {
        $income = $this->model->findOrFail($id);

        if ($income->user_id != auth()->user()->id)
        {
            return 'You are not allowed to update this data.';
        }

        $income->update([
            'expense_category_id' => $request->expense_category_id,
            'title'   => $request->title,
            'amount'  => $request->amount,
            'notes'   => $request->notes,
            'created_at' => $request->created_at
        ]);

        return null;
    }

    public function getAll()
    {
        return $this->model->where('user_id', auth()->user()->id)
            ->with('category')->orderByDesc('id')->get();
    }

    public function removeExpense($id)
    {
        $expense = $this->model->findOrFail($id);

        if ($expense->user_id != auth()->user()->id)
        {
            return 'You are not allowed to update this data.';
        }

        $expense->delete();

        return null;
    }

}
