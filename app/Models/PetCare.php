<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class PetCare extends Model
{
    use Prunable;

    protected $guarded = ['id'];

    public $timestamps = false;

    public function prunable()
    {
        // delete models on 1st day of every month that are 2 months old
        return static::where('created_at','<', Carbon::now('Asia/Dhaka')->subMonths(2));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = Carbon::now('Asia/Dhaka');
        });
    }
}
