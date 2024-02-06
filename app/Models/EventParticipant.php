<?php

namespace App\Models;

use App\Jobs\NotifyEventParticipants;
use App\Notifications\UserNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EventParticipant extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['rated_at'];

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
                false,
                false
            ));

            // remove inventories associated with that event where this participant was selected
            EventInventory::where('event_id', $model->event_id)
                ->where('assigned_to', $model->user_id)->delete();

            // notify if this participant and current logged-in user are not same
            if (auth()->user()->id != $model->user_id)
            {
                $model->user->notify(new UserNotification(
                    'pages/extra-vaganza',
                    auth()->user()->name . ' removed you from ' . $model->event->title,
                    auth()->user()->id,
                    auth()->user()->name,
                    auth()->user()->photo_url
                ));
            }
        });
    }
}
