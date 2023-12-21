<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $model): void
    {
        Cache::forget('users');
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $model): void
    {
        Cache::forget('users');
        Cache::forget('user'.$model->id);
        Cache::forget('auth_user'.$model->id);
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $model): void
    {
        Cache::forget('users');
        Cache::forget('user'.$model->id);
        Cache::forget('auth_user'.$model->id);

        deleteFile($model->photo_url);
    }
}
