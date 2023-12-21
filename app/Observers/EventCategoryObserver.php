<?php

namespace App\Observers;

use App\Models\EventCategory;
use Illuminate\Support\Facades\Cache;

class EventCategoryObserver
{
    /**
     * Handle the EventCategory "created" event.
     */
    public function created(EventCategory $model): void
    {
        Cache::forget('event_categories');
    }

    /**
     * Handle the EventCategory "updated" event.
     */
    public function updated(EventCategory $model): void
    {
        Cache::forget('event_categories');
    }

    /**
     * Handle the EventCategory "deleted" event.
     */
    public function deleted(EventCategory $model): void
    {
        Cache::forget('event_categories');
        deleteFile($model->icon_url);
    }
}
