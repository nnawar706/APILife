<?php

namespace App\Observers;

use App\Jobs\NotifyUsers;
use App\Notifications\UserNotification;
use Illuminate\Support\Facades\Cache;

class UserLoanObserver
{
    /**
     * Handle the UserLoan "creating" event.
     */
    public function creating($model): void
    {
        $model->code     = 'LN#' . rand(100000, 999999);
        $model->user_id  = auth()->user()->id;
    }

    /**
     * Handle the UserLoan "created" event.
     */
    public function created($model): void
    {
//        dispatch(new NotifyUsers(
//            [$model->selected_user_id],
//            false,
//            'pages/expense-calculator/loans',
//            auth()->user()->name . ' has initialized a loan for you.'));

        $model->selectedUser->notify(new UserNotification(
            'pages/expense-calculator/loans',
            auth()->user()->name . ' has initialized a loan for you.',
            auth()->user()->name,
            auth()->user()->photo_url
        ));
    }

    /**
     * Handle the UserLoan "updated" event.
     */
    public function updated($model): void
    {
        if ($model->status == 1)
        {
            Cache::forget('user_loans_summary' . $model->user_id);
        }

        $model->user->notify(new UserNotification(
            'pages/expense-calculator/loans',
            auth()->user()->name . ' has updated a loan status.',
            auth()->user()->name,
            auth()->user()->photo_url
        ));
    }
}
