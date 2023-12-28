<?php

namespace App\Models;

use App\Jobs\NotifyEventParticipants;
use App\Jobs\NotifyUsers;
use App\Notifications\UserNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class EventParticipant extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = [
        'approval_status'       => 'boolean'
    ];

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

        static::created(function ($model) {
            Cache::forget('events');
            Cache::forget('event_info'.$model->event_id);
        });

        static::updated(function ($model) {
            if ($model->approval_status &&
                $model->event->participants()->where('approval_status', false)->doesntExist())
            {
                $model->event->event_status_id = 3;
                $model->event->save();
            }
        });

        static::deleted(function ($model) {
            Cache::forget('events');
            Cache::forget('event_info'.$model->event_id);

            dispatch(new NotifyEventParticipants(
                $model->event,
                auth()->user(),
                'pages/extra-vaganza',
                auth()->user()->name . ' has removed ' . $model->user->name . ' from ' . $model->event->title,
                false
            ));

            $model->user->notify(new UserNotification(
                'pages/extra-vaganza',
                auth()->user()->name . ' has removed you from ' . $model->event->title,
                auth()->user()->name,
                auth()->user()->photo_url
            ));
        });
    }
}
