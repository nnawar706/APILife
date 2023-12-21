<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventDesignationGrading extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }
}
