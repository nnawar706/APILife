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

    public function getLoanSummary($user_id)
    {
        return $this->model
            ->where('user_id', $user_id)
            ->where('user_loans.status', 1)
            ->selectRaw(
                'selected_user_id,
                SUM(CASE WHEN type = 1 THEN amount ELSE 0 END) AS total_debited_amount,
                SUM(CASE WHEN type = 2 THEN amount ELSE 0 END) AS total_credited_amount'
            )
            ->with('selectedUser')
            ->groupBy('selected_user_id')
            ->get();
    }
}
