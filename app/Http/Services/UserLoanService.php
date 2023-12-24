<?php

namespace App\Http\Services;

use App\Models\UserLoan;
use Illuminate\Http\Request;

class UserLoanService
{
    private $model;

    public function __construct(UserLoan $model)
    {
        $this->model = $model;
    }

    public function storeNewLoan(Request $request): void
    {
        $this->model->create([
            'selected_user_id' => $request->selected_user_id,
            'amount'           => $request->amount,
            'type'             => $request->type,
        ]);
    }

    public function getAll($includeAll)
    {
        return $this->model->with('selectedUser')
            ->when($includeAll, function ($q) {
                return $q->with('user');
            })
            ->when(!$includeAll, function ($q) {
                return $q->where('user_id', auth()->user()->id)
                    ->orWhere('selected_user_id', auth()->user()->id);
            })
            ->latest()->get();
    }

    public function removeLoan($id)
    {
        $loan = $this->model->findOrFail($id);

        if ($loan->user_id != auth()->user()->id)
        {
            return 'Unable to delete loans that were not created by you.';
        }
        if ($loan->status === 3)
        {
            return 'Unable to delete loan once it is paid.';
        }

        $loan->delete();

        return null;
    }

    public function changeStatus($status, $id)
    {
        $loan = $this->model->findOrFail($id);

        if ($loan->selected_user_id != auth()->user()->id)
        {
            return 'Unable to change loan status that were not created for you.';
        }
        if ($loan->status === 3)
        {
            return 'Unable to update status once the loan is paid.';
        }

        $loan->update(['status' => $status]);

        return null;
    }
}
