<?php

namespace App\Observers;

use App\Jobs\NotifyUsers;
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
        dispatch(new NotifyUsers(
            [$model->selected_user_id],
            false,
            '',
            auth()->user()->name . ' has initialized a loan for you.'));
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

        dispatch(new NotifyUsers(
            [$model->user_id],
            false,
            '',
            'A loan status has been updated.'));
    }

    /**
     * Handle the UserLoan "deleted" event.
     */
    public function deleted($model): void
    {

    }
}
