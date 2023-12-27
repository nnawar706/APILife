<?php

namespace App\Http\Services;

use App\Models\User;
use App\Models\UserLoan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

//        $summary = $this->model->clone()
//            ->where('user_id', $user_id)
//            ->orWhere('selected_user_id', $user_id)
//            ->where('user_loans.status', 1)
//            ->selectRaw(
//                'user_id,
//                selected_user_id,
//                SUM(CASE WHEN type = 1 THEN amount ELSE 0 END) AS total_debited_amount,
//                SUM(CASE WHEN type = 2 THEN amount ELSE 0 END) AS total_credited_amount'
//            )
//            ->with('selectedUser')
//            ->groupBy('user_id','selected_user_id')
//            ->get();

        $users = User::whereNot('id', $user_id)->get();

        $summary = [];

        $index = 0;

        foreach ($users as $user)
        {

            $total_debited_amount = $this->model->clone()->where('user_id', $user->id)->where('selected_user_id', $user_id)
                ->accepted()->credited()->sum('amount') + $this->model->clone()->where('user_id', $user_id)->where('selected_user_id', $user->id)
                    ->accepted()->debited()->sum('amount');

            $total_credited_amount = $this->model->clone()->where('user_id', $user->id)->where('selected_user_id', $user_id)
                    ->accepted()->debited()->sum('amount') + $this->model->clone()->where('user_id', $user_id)->where('selected_user_id', $user->id)
                    ->accepted()->credited()->sum('amount');

            if ($total_debited_amount != 0 || $total_credited_amount != 0) {
                $summary[$index]['user'] = $user->id;
                $summary[$index]['total_debited_amount']  = $total_debited_amount;
                $summary[$index]['total_credited_amount'] = $total_credited_amount;

                $index++;
            }
        }

        $weekly_models = $this->model->clone()
            ->accepted()
            ->whereBetween('created_at', [$last_week, Carbon::now('Asia/Dhaka')]);

        $weekly['total_debited_amount'] = $weekly_models->clone()->where('user_id', $user_id)->debited()->sum('amount') +
                                    $weekly_models->clone()->where('selected_user_id', $user_id)->credited()->sum('amount');

        $weekly['total_credited_amount'] = $weekly_models->clone()->where('user_id', $user_id)->credited()->sum('amount') +
            $weekly_models->clone()->where('selected_user_id', $user_id)->debited()->sum('amount');

        $weekly['total_confirmed_transaction'] = $weekly_models->clone()->where('user_id', $user_id)
                                            ->orWhere('selected_user_id', $user_id)->count();

        $monthly_models = $this->model->clone()
            ->accepted()
            ->whereBetween('created_at', [$last_month, Carbon::now('Asia/Dhaka')]);

        $monthly['total_debited_amount'] = $monthly_models->clone()->where('user_id', $user_id)->debited()->sum('amount') +
            $weekly_models->clone()->where('selected_user_id', $user_id)->credited()->sum('amount');

        $monthly['total_credited_amount'] = $monthly_models->clone()->where('user_id', $user_id)->credited()->sum('amount') +
            $weekly_models->clone()->where('selected_user_id', $user_id)->debited()->sum('amount');

        $monthly['total_confirmed_transaction'] = $monthly_models->clone()->where('user_id', $user_id)
            ->orWhere('selected_user_id', $user_id)->count();

        $yearly_models = $this->model->clone()
            ->accepted()
            ->whereBetween('created_at', [$last_year, Carbon::now('Asia/Dhaka')]);

        $yearly['total_debited_amount'] = $yearly_models->clone()->where('user_id', $user_id)->debited()->sum('amount') +
            $weekly_models->clone()->where('selected_user_id', $user_id)->credited()->sum('amount');

        $yearly['total_credited_amount'] = $yearly_models->clone()->where('user_id', $user_id)->credited()->sum('amount') +
            $weekly_models->clone()->where('selected_user_id', $user_id)->debited()->sum('amount');

        $yearly['total_confirmed_transaction'] = $yearly_models->clone()->where('user_id', $user_id)
            ->orWhere('selected_user_id', $user_id)->count();


        return array(
            'summary'         => $summary,
            'additional_data' => array(
                'weekly'  => $weekly,
                'monthly' => $monthly,
                'yearly'  => $yearly
            )
        );
    }
}
