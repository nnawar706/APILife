<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventStatus extends Model
{
    public $timestamps = false;

    public function events()
    {
        return $this->hasMany(Event::class, 'event_status_id');
    }
}
