<?php

namespace App\Models;

use App\Jobs\NotifyEventParticipants;
use App\Notifications\UserNotification;
use Illuminate\Database\Eloquent\Model;

class EventGuest extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function boot()
    {
        parent::boot();

        static::deleted(function ($model) {
            dispatch(new NotifyEventParticipants(
                $model->event,
                auth()->user(),
                'pages/extra-vaganza',
                auth()->user()->name . ' removed ' . $model->user->name . ' from ' . $model->event->title . ' guest list.',
                false
            ));

            if (auth()->user()->id != $model->user_id)
            {
                $model->user->notify(new UserNotification(
                    'pages/extra-vaganza',
                    auth()->user()->name . ' removed you from ' . $model->event->title . ' guest list.',
                    auth()->user()->name,
                    auth()->user()->photo_url
                ));
            }
        });
    }
}
