<?php

namespace App\Models;

use App\Notifications\UserNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Treasurer extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];

    protected $hidden = ['updated_at'];

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
            if (auth()->user()->id != $model->user_id)
            {
                $model->treasurer->notify(new UserNotification(
                    'pages/payments',
                    auth()->user()->name . ' selected you as a treasurer. 👑',
                    auth()->user()->name,
                    auth()->user()->photo_url
                ));
            }
        });

        static::updated(function ($model) {
            if ($model->completion_status == 1)
            {
                $model->treasurer->notify(new UserNotification(
                    '',
                    'Thank you for completing a treasure hunt sincerely. 🔥',
                    null,
                    null
                ));
            }
        });
    }
}
