<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class EventImage extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['created_at','updated_at'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->added_by = auth()->user()->id;
        });

        static::deleted(function ($model) {
            deleteFile($model->image_url);
            deleteFile($model->thumbnail_url);

            Cache::forget('event_images'.$model->event_id);

            $curMonth = Carbon::now('Asia/Dhaka')->format('n');

            if (Carbon::parse($model->created_at)->format('n') == $curMonth)
            {
                $lastPoint = UserPoint::latest()->first();

                if ($lastPoint && Carbon::parse($model->created_at)->lt($lastPoint->created_at))
                {
                    $lastUserPoint = UserPoint::whereMonth('created_at', $curMonth)
                        ->where('user_id', $model->added_by)
                        ->where('point', '>=', 5)
                        ->first();

                    if ($lastUserPoint)
                    {
                        $lastUserPoint->point -= 5;
                        $lastUserPoint->saveQuietly();
                    }
                }
            }
        });
    }
}
