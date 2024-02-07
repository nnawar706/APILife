<?php

namespace App\Observers;

use App\Jobs\NotifyEventParticipants;
use App\Jobs\NotifyUsers;
use App\Models\EventRating;
use Illuminate\Support\Facades\Cache;

class EventObserver
{
    /**
     * Handle the Event "creating" event.
     */
    public function creating($model): void
    {
        $model->event_status_id  = 1;
        $model->added_by_user_id = auth()->user()->id;
    }

    public function created($model): void
    {
        EventRating::create([
            'event_id' => $model->id,
        ]);

        dispatch(new NotifyUsers(
            null,
            true,
            'pages/accounts/notification',
            auth()->user()->name . ' created a new extravaganza that you might want to attend.',
            auth()->user()
        ));
    }

    /**
     * Handle the Event "updated" event.
     */
    public function updated($model): void
    {
        Cache::forget('event_expenses'.$model->id);

        if ($model->event_status_id == 2)
        {
            $message = auth()->user()->name . ' recently locked ' . $model->title . ' and is requesting you to approve it.';

            dispatch(new NotifyEventParticipants(
                $model,
                auth()->user(),
                'pages/pending-vaganza',
                $message,
                false,
                false
            ));
        }

        if ($model->event_status_id == 3 || $model->event_status_id == 4)
        {
            $message = $model->event_status_id == 3 ? $model->title . ' has been approved by all participants.'
                : $model->title . ' has been completed.';

            dispatch(new NotifyEventParticipants(
                $model,
                null,
                'pages/update-vaganza/' . $model->id,
                $message,
                false,
                false
            ));
        }
    }

    /**
     * Handle the Event "deleted" event.
     */
    public function deleted($model): void
    {
        Cache::forget('event_info'.$model->id);
    }
}
