<?php

namespace App\Observers;

use App\Jobs\NotifyNewEvent;
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
        $model->event_status_id = 1;
    }

    /**
     * Handle the Event "created" event.
     */
    public function created($model): void
    {
        Cache::forget('events');

        dispatch(new NotifyNewEvent($model));
    }

    /**
     * Handle the Event "updated" event.
     */
    public function updated($model): void
    {
        Cache::forget('events');
        Cache::forget('event_info'.$model->id);
    }

    /**
     * Handle the Event "deleted" event.
     */
    public function deleted($model): void
    {
        Cache::forget('events');
        Cache::forget('event_info'.$model->id);
    }
}
