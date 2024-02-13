<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;

class UserPoint extends Model
{
    use Prunable;

    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at'];

    public function prunable()
    {
        // delete models on 1st day of every month that are 2 months old
        return static::whereMonth('created_at', Carbon::now('Asia/Dhaka')->subMonths(2)->format('n'));
    }

    public function scopeCurrent(Builder $q)
    {
        // filter models which have been created on current month
        $q->whereMonth('created_at', Carbon::now('Asia/Dhaka')->format('n'));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
