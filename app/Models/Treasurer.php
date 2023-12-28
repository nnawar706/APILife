<?php

namespace App\Models;

use App\Notifications\UserNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Treasurer extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = [
        'completion_status' => 'boolean'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id','completion_status'])
            ->useLogName('Treasurer')
            ->logAll();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return "A treasurer detail has been {$eventName}";
    }

    public function treasurer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function events()
    {
        return $this->hasMany(TreasurerEvent::class, 'treasurer_id');
    }

    public function liabilities()
    {
        return $this->hasMany(TreasurerLiability::class, 'treasurer_id');
    }

    public static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            Cache::forget('treasurers');


            if (auth()->user()->id != $model->user_id)
            {
                $model->treasurer->notify(new UserNotification(
                    '/',
                    auth()->user()->name . ' selected you as a treasurer.',
                    auth()->user()->name,
                    auth()->user()->photo_url
                ));
            }
        });

        static::updated(function ($model) {
            Cache::forget('treasurers');

            if ($model->completion_status == 1)
            {
                $model->treasurer->notify(new UserNotification(
                    '/',
                    'Thank you for completing a treasure hunt sincerely.',
                    null,
                    null
                ));
            }
        });
    }
}
