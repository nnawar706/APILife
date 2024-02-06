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
            // while new image is being uploaded, add auth user as model's added by user
            $model->added_by = auth()->user()->id;
        });

        static::deleted(function ($model) {
            // delete original image file
            deleteFile($model->image_url);

            // delete thumbnail image file
            deleteFile($model->thumbnail_url);

            // forget cache for image list of that event
            Cache::forget('event_images'.$model->event_id);

            // current month
            $curMonth = Carbon::now('Asia/Dhaka')->format('n');

            // decrease user point only if model's been created in current month
            if (Carbon::parse($model->created_at)->format('n') == $curMonth)
            {
                // fetch last user point entry
                $lastPoint = UserPoint::latest()->first();

                /* if last point entry exists, check if model's creation time is less than last user point's time
                / which ensures that user has already got 5 marks for uploading the image
                */
                if ($lastPoint && Carbon::parse($model->created_at)->lt($lastPoint->created_at))
                {
                    // fetch point entry of that user where point is greater than 5
                    $lastUserPoint = UserPoint::whereMonth('created_at', $curMonth)
                        ->where('user_id', $model->added_by)
                        ->where('point', '>=', 5)
                        ->first();

                    // if entry exists, deduct 5 points from the user
                    if ($lastUserPoint)
                    {
                        $lastUserPoint->point -= 5;
                        // save the model quietly not to trigger any update events
                        $lastUserPoint->saveQuietly();
                    }
                }
            }
        });
    }
}
