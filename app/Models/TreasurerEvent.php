<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreasurerEvent extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    public function treasurer()
    {
        return $this->belongsTo(Treasurer::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
