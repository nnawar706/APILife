<?php

namespace App\Observers;

use App\Jobs\NotifyEventParticipants;
use App\Jobs\NotifyUsers;
use App\Models\Event;
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

    /**
     * Handle the Event "created" event.
     */
    public function created($model): void
    {
        dispatch(new NotifyEventParticipants(
            $model,
            auth()->user(),
            'pages/extra-vaganza',
            auth()->user()->name . ' has created a new extravaganza.',
            true
        ));
    }

    /**
     * Handle the Event "updated" event.
     */
    public function updated($model): void
    {
        if ($model->event_status_id == 3 || $model->event_status_id == 4)
        {
            $message = $model->event_status_id == 3 ? $model->title . ' has been approved by all participants.'
                : $model->title . ' has been completed.';

            dispatch(new NotifyEventParticipants(
                $model,
                null,
                'pages/extra-vaganza/' . $model->id,
                $message,
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
