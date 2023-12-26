<?php

namespace App\Models;

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
        });
    }
}
