<?php

namespace App\Models;

use App\Notifications\UserNotification;
use Illuminate\Database\Eloquent\Model;

class EventInventoryParticipant extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    public function eventInventory()
    {
        return $this->belongsTo(EventInventory::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            if ($model->user_id != auth()->user()->id)
            {
                $model->user->notify(new UserNotification(
                    'pages/update-vaganza/' . $model->eventInventory->event_id,
                    auth()->user()->name . ' added you to an inventory of ' . $model->eventInventory->event->title . '.',
                    auth()->user()->name,
                    auth()->user()->photo_url
                ));
            }
        });

        static::deleted(function ($model) {
            if ($model->user_id != auth()->user()->id)
            {
                $model->user->notify(new UserNotification(
                    'pages/update-vaganza/' . $model->eventInventory->event_id,
                    auth()->user()->name . ' removed an inventory from ' . $model->eventInventory->event->title . '.',
                    auth()->user()->name,
                    auth()->user()->photo_url
                ));
            }
        });
    }
}
