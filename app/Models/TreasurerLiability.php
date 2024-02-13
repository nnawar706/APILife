<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use App\Notifications\UserNotification;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class TreasurerLiability extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => 'boolean'
    ];

//    protected $hidden = ['created_at', 'updated_at'];

    protected static $recordEvents = ['updated'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['treasurer_id','user_id','amount','status'])
            ->useLogName('Treasurer Liability')
            ->logAll();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return "A treasurer liability data has been {$eventName}";
    }

    public function treasurer()
    {
        return $this->belongsTo(Treasurer::class, 'treasurer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function boot()
    {
        parent::boot();

        static::updated(function ($model) {
            $model->user->notify(
                new UserNotification(
                    'pages/payments',
                    'Your payment is settled for a treasure hunt.',
                    $model->treasurer->treasurer->id,
                    $model->treasurer->treasurer->name,
                    $model->treasurer->treasurer->photo_url
                )
            );
        });
    }
}
