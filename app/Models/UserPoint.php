<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserPoint extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at'];

    public function scopeCurrent(Builder $q)
    {
        $q->whereMonth('created_at', Carbon::now('Asia/Dhaka')->format('n'));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
