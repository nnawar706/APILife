<?php

namespace App\Http\Services;

use App\Models\User;
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
            'loan_type'        => $request->loan_type
        ]);
    }

    public function getAll()
    {
        return $this->model->with('user','selectedUser')
            ->where('user_id', auth()->user()->id)
            ->orWhere('selected_user_id', auth()->user()->id)
            ->latest()->get();
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
                $summary[$index]['user'] = $user;
                $summary[$index]['total_debited_amount']  = $total_debited_amount;
                $summary[$index]['total_credited_amount'] = $total_credited_amount;

                $index++;
            }
        }

        $loan_models = $this->model->clone()
            ->accepted();

        $weekly['total_debited_amount'] = $loan_models->clone()
                                    ->whereBetween('created_at', [$last_week, Carbon::now('Asia/Dhaka')])
                                    ->where('user_id', $user_id)->debited()->sum('amount') +
                                    $loan_models->clone()
                                    ->whereBetween('created_at', [$last_week, Carbon::now('Asia/Dhaka')])
                                    ->where('selected_user_id', $user_id)->credited()->sum('amount');

        $weekly['total_credited_amount'] = $loan_models->clone()
                                    ->whereBetween('created_at', [$last_week, Carbon::now('Asia/Dhaka')])
                                    ->where('user_id', $user_id)->credited()->sum('amount') +
                                    $loan_models->clone()
                                    ->whereBetween('created_at', [$last_week, Carbon::now('Asia/Dhaka')])
                                    ->where('selected_user_id', $user_id)->debited()->sum('amount');

        $weekly['total_confirmed_transaction'] =
            $loan_models->clone()->whereBetween('created_at', [$last_week, Carbon::now('Asia/Dhaka')])->where('user_id', $user_id)->count()
            +
            $loan_models->clone()->whereBetween('created_at', [$last_week, Carbon::now('Asia/Dhaka')])->where('selected_user_id', $user_id)->count();


        $monthly['total_debited_amount'] = $loan_models->clone()
                                    ->whereBetween('created_at', [$last_month, Carbon::now('Asia/Dhaka')])
                                    ->where('user_id', $user_id)->debited()->sum('amount') +
                                    $loan_models->clone()
                                    ->whereBetween('created_at', [$last_month, Carbon::now('Asia/Dhaka')])
                                    ->where('selected_user_id', $user_id)->credited()->sum('amount');

        $monthly['total_credited_amount'] = $loan_models->clone()
                                    ->whereBetween('created_at', [$last_month, Carbon::now('Asia/Dhaka')])
                                    ->where('user_id', $user_id)->credited()->sum('amount') +
                                    $loan_models->clone()
                                    ->whereBetween('created_at', [$last_month, Carbon::now('Asia/Dhaka')])
                                    ->where('selected_user_id', $user_id)->debited()->sum('amount');

        $monthly['total_confirmed_transaction'] =
            $loan_models->clone()->whereBetween('created_at', [$last_month, Carbon::now('Asia/Dhaka')])->where('user_id', $user_id)->count()
            +
            $loan_models->clone()->whereBetween('created_at', [$last_month, Carbon::now('Asia/Dhaka')])->where('selected_user_id', $user_id)->count();


        $yearly['total_debited_amount'] = $loan_models->clone()
                                    ->whereBetween('created_at', [$last_year, Carbon::now('Asia/Dhaka')])
                                    ->where('user_id', $user_id)->debited()->sum('amount') +
                                    $loan_models->clone()
                                    ->whereBetween('created_at', [$last_year, Carbon::now('Asia/Dhaka')])
                                    ->where('selected_user_id', $user_id)->credited()->sum('amount');

        $yearly['total_credited_amount'] = $loan_models->clone()
                                    ->whereBetween('created_at', [$last_year, Carbon::now('Asia/Dhaka')])
                                    ->where('user_id', $user_id)->credited()->sum('amount') +
                                    $loan_models->clone()
                                    ->whereBetween('created_at', [$last_year, Carbon::now('Asia/Dhaka')])
                                    ->where('selected_user_id', $user_id)->debited()->sum('amount');

        $yearly['total_confirmed_transaction'] =
            $loan_models->clone()->whereBetween('created_at', [$last_year, Carbon::now('Asia/Dhaka')])->where('user_id', $user_id)->count()
            +
            $loan_models->clone()->whereBetween('created_at', [$last_year, Carbon::now('Asia/Dhaka')])->where('selected_user_id', $user_id)->count()
        ;

        return array(
            'total_transaction_count' => $loan_models->clone()->where('user_id', $user_id)->count() +
                                            $loan_models->clone()->where('selected_user_id', $user_id)->count(),
            'summary'         => $summary,
            'additional_data' => array(
                'weekly'  => $weekly,
                'monthly' => $monthly,
                'yearly'  => $yearly
            )
        );
    }
}
