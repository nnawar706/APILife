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
        Cache::forget('user_loan'.$model->user_id);
        Cache::forget('user_loan'.$model->selected_user_id);

        $model->selectedUser->notify(new UserNotification(
            'pages/financial-assistance/transaction-log',
            auth()->user()->name . ' has initialized a loan for you. 💸',
            auth()->user()->id,
            auth()->user()->name,
            auth()->user()->photo_url
        ));

        $type = $model->loan_type == 1 ? 'lend' : 'return';

        dispatch(new NotifyUsers(
            null,
            true,
            'pages/accounts/notification',
            'Someone initiated a loan of type ' . $type . '👀',
            null,
        ));
    }

    /**
     * Handle the UserLoan "updated" event.
     */
    public function updated($model): void
    {
        Cache::forget('user_loan'.$model->user_id);
        Cache::forget('user_loan'.$model->selected_user_id);

        Cache::forget('user_loans_summary' . $model->user_id);
        Cache::forget('user_loans_summary' . $model->selected_user_id);

        $model->user->notify(new UserNotification(
            'pages/financial-assistance/transaction-log',
            auth()->user()->name . ' updated a loan status.',
            auth()->user()->id,
            auth()->user()->name,
            auth()->user()->photo_url
        ));
    }

    public function deleted($model): void
    {
        Cache::forget('user_loan'.$model->user_id);
        Cache::forget('user_loan'.$model->selected_user_id);

        Cache::forget('user_loans_summary' . $model->user_id);
        Cache::forget('user_loans_summary' . $model->selected_user_id);

        $model->selectedUser->notify(new UserNotification(
            'pages/financial-assistance/transaction-log',
            auth()->user()->name . ' deleted a loan that was initiated for you.',
            auth()->user()->id,
            auth()->user()->name,
            auth()->user()->photo_url
        ));
    }
}
