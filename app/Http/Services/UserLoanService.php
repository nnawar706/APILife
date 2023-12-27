<?php

namespace App\Http\Services;

use App\Models\UserLoan;
use Carbon\Carbon;
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
            'type'             => $request->type, // 1: debit (incoming), 2: credit (outgoing)
        ]);
    }

    public function getAll($includeAll)
    {
        return $this->model->with('user','selectedUser')
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
        if ($loan->status === 1)
        {
            return 'Unable to update status once the loan is accepted.';
        }

        $loan->update(['status' => $status]);

        return null;
    }

    public function getLoanSummary($user_id)
    {
        $last_week  = Carbon::now('Asia/Dhaka')->subWeek(1);
        $last_month = Carbon::now('Asia/Dhaka')->subMonth(1);
        $last_year  = Carbon::now('Asia/Dhaka')->subYear(1);

        $summary = $this->model->clone()
            ->where('user_id', $user_id)
            ->orWhere('selected_user_id', $user_id)
            ->where('user_loans.status', 1)
            ->selectRaw(
                'user_id,
                selected_user_id,
                SUM(CASE WHEN type = 1 THEN amount ELSE 0 END) AS total_debited_amount,
                SUM(CASE WHEN type = 2 THEN amount ELSE 0 END) AS total_credited_amount'
            )
            ->with('selectedUser')
            ->groupBy('user_id','selected_user_id')
            ->get();

//        $weekly = $this->model->clone()
//            ->where('user_id', $user_id)
//            ->where('user_loans.status', 1)
//            ->whereBetween('created_at', [$last_week, Carbon::now('Asia/Dhaka')])
//            ->selectRaw(
//                'SUM(CASE WHEN type = 1 THEN amount ELSE 0 END) AS total_debited_amount,
//                SUM(CASE WHEN type = 2 THEN amount ELSE 0 END) AS total_credited_amount,
//                COUNT(id) as total_confirmed_transaction'
//            )->first();
//
//        $monthly = $this->model->clone()
//            ->where('user_id', $user_id)
//            ->where('user_loans.status', 1)
//            ->whereBetween('created_at', [$last_month, Carbon::now('Asia/Dhaka')])
//            ->selectRaw(
//                'SUM(CASE WHEN type = 1 THEN amount ELSE 0 END) AS total_debited_amount,
//                SUM(CASE WHEN type = 2 THEN amount ELSE 0 END) AS total_credited_amount,
//                COUNT(id) as total_confirmed_transaction'
//            )->first();
//
//        $yearly = $this->model->clone()
//            ->where('user_id', $user_id)
//            ->where('user_loans.status', 1)
//            ->whereBetween('created_at', [$last_year, Carbon::now('Asia/Dhaka')])
//            ->selectRaw(
//                'SUM(CASE WHEN type = 1 THEN amount ELSE 0 END) AS total_debited_amount,
//                SUM(CASE WHEN type = 2 THEN amount ELSE 0 END) AS total_credited_amount,
//                COUNT(id) as total_confirmed_transaction'
//            )->first();

        return array(
            'summary'         => $summary,
//            'additional_data' => array(
//                'weekly'  => $weekly,
//                'monthly' => $monthly,
//                'yearly'  => $yearly
//            )
        );
    }
}
