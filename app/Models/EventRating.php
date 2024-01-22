<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventRating extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['rating', 'rated_by'];

    public $timestamps = false;

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
