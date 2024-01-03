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
            $message = $model->badge_id == 4 ? 'Congratulations! 🎉 ✨ You have become the Extravaganza Overlord this month.'
                : 'Yayy! 🎉 You have earned a new badge this month.';

            $model->user->notify(new UserNotification(
                'pages/accounts/notification',
                $message,
                'Badge',
                $model->badge->image_url));
        });
    }
}
