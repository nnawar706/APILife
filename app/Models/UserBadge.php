<?php

namespace App\Models;

use App\Notifications\UserNotification;
use Illuminate\Database\Eloquent\Model;

class UserBadge extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['updated_at'];

    protected $casts = [
        'created_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function badge()
    {
        return $this->belongsTo(Badge::class);
    }

    public static function boot ()
    {
        parent::boot();

        static::created(function ($model) {
            $message = $model->badge_id == 5 ? 'Congratulations! 🎉 ✨ You have become the '. $model->badge->name .' this month.'
                : 'Yayy! 🎉 You have earned a new badge this month.';

            $model->user->notify(new UserNotification(
                'pages/accounts/notification',
                $message,
                null,
                'Badge',
                $model->badge->image_url));
        });
    }
}
