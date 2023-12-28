<?php

namespace App\Models;

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
            $message = $model->badge_id == 4 ? 'Congratulations! 🎉 ✨ You have earned the highest adventure badge this month.'
                : 'Yayy! 🎉 You have earned a new adventure badge this month.';

            sendNotification([$model->user], '/', $message);
        });
    }
}
