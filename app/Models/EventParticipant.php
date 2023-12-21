<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class EventParticipant extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            Cache::forget('event_info'.$model->event_id);
        });

        static::deleted(function ($model) {
            Cache::forget('event_info'.$model->event_id);
        });
    }
}
