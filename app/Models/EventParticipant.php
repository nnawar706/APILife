<?php

namespace App\Models;

use App\Jobs\NotifyEventParticipants;
use App\Notifications\UserNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EventParticipant extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = [
        'approval_status' => 'boolean',
        'rated'           => 'boolean'
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

        static::updated(function ($model) {
            if ($model->approval_status &&
                $model->event->participants()->where('approval_status', false)->doesntExist())
            {
                $model->event->event_status_id = 3;
                $model->event->save();
            }
        });

        static::deleted(function ($model) {
            dispatch(new NotifyEventParticipants(
                $model->event,
                auth()->user(),
                'pages/extra-vaganza',
                auth()->user()->name . ' removed ' . $model->user->name . ' from ' . $model->event->title,
                false
            ));

            EventInventoryParticipant::whereHas('eventInventory', function ($q) use ($model) {
                return $q->where('event_id', $model->event_id);
            })->where('user_id', $model->user_id)->delete();

            if (auth()->user()->id != $model->user_id)
            {
                $model->user->notify(new UserNotification(
                    'pages/extra-vaganza',
                    auth()->user()->name . ' removed you from ' . $model->event->title,
                    auth()->user()->name,
                    auth()->user()->photo_url
                ));
            }
        });
    }
}
